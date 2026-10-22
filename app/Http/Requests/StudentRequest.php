<?php

namespace App\Http\Requests;

use App\Models\Rombel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']);
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('rfid_uid')) {
            $this->merge([
                'rfid_uid' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('rfid_uid'))),
            ]);
        }
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;
        $tenantId = currentTenantId();

        return [
            'nisn' => [
                'required',
                'digits:10',
                Rule::unique('students')->where(fn ($q) => $q->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId)))->ignore($studentId),
            ],
            'nis' => ['nullable', 'max:20'],
            'rfid_uid' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students')->where(fn ($q) => $q->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId)))->ignore($studentId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'rombel_id' => ['nullable', 'integer', Rule::exists('rombels', 'id')->where(fn ($q) => $q->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId)))],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'create_account' => ['nullable', 'boolean'],
            'account_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('student')?->user_id),
            ],
            'account_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.digits' => 'NISN harus 10 digit angka.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'rfid_uid.unique' => 'UID RFID sudah dipakai siswa lain.',
            'name.required' => 'Nama siswa wajib diisi.',
            'gender.in' => 'Jenis kelamin harus L atau P.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'account_email.email' => 'Format email akun tidak valid.',
            'account_email.unique' => 'Email akun sudah dipakai pengguna lain.',
            'account_password.min' => 'Kata sandi akun minimal 8 karakter.',
            'account_password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }

    public function attributes(): array
    {
        return [
            'account_email' => 'email akun',
            'account_password' => 'kata sandi akun',
        ];
    }

    /**
     * Data pokok siswa saja, tanpa field akun login.
     *
     * @return array<string, mixed>
     */
    public function studentPayload(): array
    {
        return $this->safe([
            'rombel_id', 'nisn', 'nis', 'rfid_uid', 'name',
            'gender', 'birth_date', 'birth_place', 'address', 'phone',
        ]);
    }

    /**
     * Fieldset akun hanya diproses bila dicentang dan pengolah memegang izin
     * users.manage, supaya form siswa tetap bisa disimpan tanpa hak kelola pengguna.
     */
    public function wantsAccount(): bool
    {
        return $this->boolean('create_account') && (bool) $this->user()?->hasPermission('users.manage');
    }

    public function accountEmail(): string
    {
        return $this->string('account_email')->trim()->lower()->toString();
    }

    public function accountPassword(): string
    {
        return $this->string('account_password')->toString();
    }

    /**
     * Rombel tujuan harus milik tenant yang sama dan berasal dari tahun ajaran aktif.
     * Rombel asal siswa dipertahankan saat edit agar siswa lama tidak terkunci.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rombelId = $this->input('rombel_id');

            if (is_null($rombelId)) {
                return;
            }

            $student = $this->route('student');

            if ($student && (int) $student->rombel_id === (int) $rombelId) {
                return;
            }

            $rombel = Rombel::find($rombelId);

            if (! $rombel?->academicYear?->is_active) {
                $validator->errors()->add('rombel_id', 'Siswa hanya dapat ditempatkan ke rombel dari tahun ajaran aktif.');
            }
        });

        $validator->after(function ($validator) {
            if (! $this->boolean('create_account')) {
                return;
            }

            if (! $this->user()?->hasPermission('users.manage')) {
                $validator->errors()->add('create_account', 'Anda tidak berwenang membuat akun login siswa.');

                return;
            }

            $student = $this->route('student');

            if ($student?->user && ! $student->user->isSiswa()) {
                $validator->errors()->add('create_account', 'Akun yang tertaut ke siswa ini bukan akun siswa, jadi email dan kata sandinya tidak bisa diubah dari sini.');

                return;
            }

            if (blank($this->input('account_email'))) {
                $validator->errors()->add('account_email', 'Email akun wajib diisi.');
            }

            if (blank($this->input('account_password')) && ! $student?->user) {
                $validator->errors()->add('account_password', 'Kata sandi wajib diisi untuk akun baru.');
            }
        });
    }
}
