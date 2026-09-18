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
    }
}
