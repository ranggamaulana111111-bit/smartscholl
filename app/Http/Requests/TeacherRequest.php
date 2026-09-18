<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']);
    }

    public function rules(): array
    {
        $teacherId = $this->route('teacher')?->id;

        return [
            'nuptk' => [
                'nullable',
                'digits:16',
                Rule::unique('teachers')->where(fn ($q) => $q->where('tenant_id', currentTenantId()))->ignore($teacherId),
            ],
            'nip' => ['nullable', 'max:18'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:100'],
            'employment_status' => ['required', Rule::in(['gty', 'ptt', 'asn'])],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nuptk.digits' => 'NUPTK harus 16 digit angka.',
            'nuptk.unique' => 'NUPTK sudah terdaftar.',
            'name.required' => 'Nama guru wajib diisi.',
            'employment_status.in' => 'Status kepegawaian tidak valid.',
        ];
    }
}
