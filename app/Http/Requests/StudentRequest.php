<?php

namespace App\Http\Requests;

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

        return [
            'nisn' => [
                'required',
                'digits:10',
                Rule::unique('students')->where(fn ($q) => $q->where('tenant_id', currentTenantId()))->ignore($studentId),
            ],
            'nis' => ['nullable', 'max:20'],
            'rfid_uid' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students')->where(fn ($q) => $q->where('tenant_id', currentTenantId()))->ignore($studentId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'rombel_id' => ['nullable', 'exists:rombels,id'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
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
        ];
    }
}
