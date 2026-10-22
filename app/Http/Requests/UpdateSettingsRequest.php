<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $group = (string) $this->input('group');
        $roles = config("school-settings.$group.roles", ['super_admin', 'admin_sekolah']);

        if (! $user->hasAnyRole($roles)) {
            return false;
        }

        $permission = (string) config("school-settings.$group.permission", 'settings.school');

        return $user->hasPermission($permission);
    }

    public function rules(): array
    {
        $group = (string) $this->input('group');
        $fields = config("school-settings.$group.fields", []);

        $rules = [];

        foreach ($fields as $name => $cfg) {
            $rules[$name] = $cfg['type'] === 'toggle'
                ? ['nullable', 'boolean']
                : $cfg['rules'];
        }

        if ((bool) config("school-settings.$group.tenant_scoped", false)) {
            $rules['tenant'] = [
                'nullable',
                'uuid',
                Rule::exists('tenants', 'id')->where(fn ($q) => $q->where('status', 'active')),
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $group = (string) $this->input('group');
        $fields = config("school-settings.$group.fields", []);

        return collect($fields)->map(fn ($cfg) => $cfg['label'])
            ->put('tenant', 'sekolah')
            ->all();
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'size' => ':attribute harus tepat :size karakter.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'url' => ':attribute harus berupa URL yang valid.',
            'in' => ':attribute tidak valid.',
            'boolean' => ':attribute harus bernilai benar atau salah.',
            'tenant.exists' => 'Sekolah yang dipilih tidak ditemukan atau sedang tidak aktif.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ((string) $this->input('group') !== 'penilaian') {
                return;
            }

            $sum = collect(['bobot_tugas', 'bobot_formatif', 'bobot_uts', 'bobot_uas'])
                ->sum(fn (string $name): int => (int) $this->input($name));

            if ($sum !== 100) {
                $label = collect($this->attributes())->get('bobot_tugas', 'Total bobot');
                $validator->errors()->add('bobot_tugas', $label.' harus berjumlah tepat 100 (saat ini '.$sum.').');
            }
        });

        $validator->after(function ($validator): void {
            $group = (string) $this->input('group');

            if (! (bool) config("school-settings.$group.tenant_scoped", false)) {
                return;
            }

            if (currentTenantId() || $this->filled('tenant')) {
                return;
            }

            $validator->errors()->add(
                'tenant',
                'Pilih sekolah yang pengaturannya akan disimpan. Pengaturan per sekolah tidak boleh ditulis tanpa sekolah tujuan yang eksplisit.'
            );
        });
    }
}
