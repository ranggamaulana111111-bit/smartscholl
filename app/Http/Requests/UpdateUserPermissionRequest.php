<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pengecualian per pengguna adalah bagian dari fitur matriks izin,
        // sehingga penandatanganannya adalah permissions.manage, bukan users.manage.
        return (bool) $this->user()?->hasPermission('permissions.manage');
    }

    public function rules(): array
    {
        return [
            'overrides' => ['nullable', 'array'],
            'overrides.*.permission' => [
                'required',
                Rule::in(permissionNames()),
                Rule::unique('user_permissions', 'permission_id')->where(
                    fn ($query) => $query->where('user_id', $this->route('user')->id)
                ),
            ],
            'overrides.*.effect' => ['required', Rule::in(['inherit', 'grant', 'deny'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'overrides' => 'pengecualian akses',
            'overrides.*.permission' => 'izin',
            'overrides.*.effect' => 'pengecualian',
        ];
    }

    public function messages(): array
    {
        return [
            'overrides.*.permission.unique' => 'Izin ini sudah memiliki pengecualian untuk akun tersebut.',
            'overrides.*.permission.in' => 'Izin tidak dikenal.',
            'overrides.*.effect.in' => 'Pengecualian harus "izin", "larang", atau "ikuti peran".',
        ];
    }

    /**
     * Entri pengecualian yang valid. Opsi "Ikuti peran" di form sengaja tidak
     * disimpan sebagai baris, jadi tidak ada override yang menyalin bawaan peran.
     *
     * @return array<int, array{permission_id: int, effect: string}>
     */
    public function overrides(): array
    {
        $ids = Permission::query()->pluck('id', 'name');
        $clean = [];

        foreach ((array) $this->input('overrides', []) as $entry) {
            $name = is_array($entry) ? ($entry['permission'] ?? null) : null;
            $effect = is_array($entry) ? ($entry['effect'] ?? null) : null;

            if (! is_string($name) || ! isset($ids[$name]) || ! in_array($effect, ['grant', 'deny'], true)) {
                continue;
            }

            $clean[] = [
                'permission_id' => $ids[$name],
                'effect' => $effect,
            ];
        }

        return $clean;
    }
}
