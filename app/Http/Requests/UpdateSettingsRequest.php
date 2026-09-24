<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = (string) $this->input('group');
        $roles = config("school-settings.$group.roles", ['super_admin', 'admin_sekolah']);

        return auth()->user()->hasAnyRole($roles);
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

        return $rules;
    }

    public function attributes(): array
    {
        $group = (string) $this->input('group');
        $fields = config("school-settings.$group.fields", []);

        return collect($fields)->map(fn ($cfg) => $cfg['label'])->all();
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
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ((string) $this->input('group') !== 'penilaian') {
                return;
            }

            $sum = collect(['bobot_tugas', 'bobot_formatif', 'bobot_uts', 'bobot_uas'])
                ->sum(fn (string $name): int => (int) $this->input($name));

            if ($sum !== 100) {
                $label = Arr::get($this->attributes(), 'bobot_tugas', 'Total bobot');
                $validator->errors()->add('bobot_tugas', $label.' harus berjumlah tepat 100 (saat ini '.$sum.').');
            }
        });
    }
}
