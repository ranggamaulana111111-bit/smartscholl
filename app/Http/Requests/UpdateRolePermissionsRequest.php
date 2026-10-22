<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('permissions.manage');
    }

    public function rules(): array
    {
        return [
            'grants' => ['nullable', 'array'],
            'grants.*' => ['array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'grants' => 'matriks hak akses',
        ];
    }

    /**
     * Grants yang sudah dibersihkan: hanya peran dan nama izin yang dikenal.
     * Peran Super Admin dibuang di sini, bukan hanya disembunyikan di tampilan.
     *
     * @return array<string, array<int, string>>
     */
    public function grants(): array
    {
        $known = permissionNames();
        $clean = [];

        foreach ((array) $this->input('grants', []) as $role => $names) {
            if (! in_array($role, ['admin_sekolah', 'guru', 'siswa', 'orang_tua'], true)) {
                continue;
            }

            $clean[$role] = array_values(array_unique(array_filter(
                (array) $names,
                fn ($name): bool => is_string($name) && in_array($name, $known, true)
            )));
        }

        return $clean;
    }
}
