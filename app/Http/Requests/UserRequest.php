<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->actor();

        if (! $actor) {
            return false;
        }

        // Super Admin selalu lolos. Admin Sekolah tidak cukup hanya cocok role:
        // ia harus memegang izin users.manage supaya pencabutan izin di matriks
        // benar-benar menutup akses, bukan sekadar menyembunyikan UI.
        return $actor->isSuperAdmin()
            || ($actor->hasPermission('users.manage') && $actor->hasAnyRole(['admin_sekolah']));
    }

    /**
     * Role milik target yang tidak boleh diubah oleh actor saat ini. Contohnya
     * Admin Sekolah yang mengedit akunnya sendiri, atau akun Admin Sekolah lain.
     */
    private function lockedRole(): ?string
    {
        $actor = $this->actor();
        $target = $this->route('user');

        if (! $actor || $actor->isSuperAdmin() || ! $target instanceof User) {
            return null;
        }

        return in_array($target->role, $actor->assignableRoles(), true) ? null : $target->role;
    }

    protected function prepareForValidation(): void
    {
        $actor = $this->actor();

        if (! $actor || $actor->isSuperAdmin()) {
            return;
        }

        // Admin Sekolah tidak pernah boleh memindahkan pengguna lintas tenant.
        $this->merge(['tenant_id' => $actor->tenant_id]);

        if (($locked = $this->lockedRole()) !== null) {
            $this->merge(['role' => $locked]);
        }
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $isUpdate = $target instanceof User;

        $assignable = array_values(array_filter(array_merge(
            $this->actor()?->assignableRoles() ?? [],
            array_filter([$this->lockedRole()]),
        )));

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($isUpdate ? $target->id : null),
            ],
            'role' => ['required', Rule::in($assignable)],
            'tenant_id' => [
                'nullable',
                Rule::exists('tenants', 'id'),
                Rule::requiredIf(fn (): bool => $this->input('role') !== 'super_admin'),
            ],
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => 'sekolah',
            'name' => 'nama lengkap',
            'email' => 'email',
            'role' => 'peran',
            'password' => 'kata sandi',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Pilih sekolah untuk peran ini. Admin platform boleh tanpa sekolah.',
            'role.in' => 'Peran yang dipilih tidak boleh Anda tetapkan.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }

    /**
     * Data siap simpan. Password yang tidak diisi pada form edit tidak menimpa
     * hash lama, dan tenant_id selalu mengikuti kewenangan actor.
     */
    public function payload(): array
    {
        $data = [
            'name' => $this->string('name')->trim()->toString(),
            'email' => $this->string('email')->trim()->lower()->toString(),
            'role' => $this->string('role')->toString(),
            'tenant_id' => $this->input('tenant_id') ?: null,
        ];

        if ($this->filled('password')) {
            $data['password'] = $this->string('password')->toString();
        }

        return $data;
    }

    public function changedPassword(): bool
    {
        return $this->filled('password');
    }

    private function actor(): ?User
    {
        $user = $this->user();

        return $user instanceof User ? $user : null;
    }
}
