<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RombelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']);
    }

    public function rules(): array
    {
        $rombelId = $this->route('rombel')?->id;
        $tenantId = currentTenantId();

        return [
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rombels')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('academic_year_id', $this->input('academic_year_id')))->ignore($rombelId),
            ],
            'grade_level' => ['required', 'string', 'max:10'],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'name.required' => 'Nama rombel wajib diisi.',
            'name.unique' => 'Nama rombel sudah ada di tahun ajaran ini.',
            'grade_level.required' => 'Tingkat kelas wajib diisi.',
        ];
    }

    /**
     * Validasi bahwa wali kelas adalah guru milik tenant yang sama.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $homeroomTeacherId = $this->input('homeroom_teacher_id');
            if ($homeroomTeacherId && ! User::where('id', $homeroomTeacherId)
                ->where('tenant_id', currentTenantId())
                ->whereIn('role', ['guru', 'admin_sekolah'])
                ->exists()) {
                $validator->errors()->add('homeroom_teacher_id', 'Wali kelas harus guru di sekolah ini.');
            }
        });
    }
}
