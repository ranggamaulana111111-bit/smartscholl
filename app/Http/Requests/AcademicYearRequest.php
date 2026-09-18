<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']);
    }

    public function rules(): array
    {
        $yearId = $this->route('academic_year')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('academic_years')->where(fn ($q) => $q->where('tenant_id', currentTenantId()))->ignore($yearId),
            ],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tahun ajaran wajib diisi.',
            'name.unique' => 'Tahun ajaran sudah ada.',
            'semester.required' => 'Semester wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('is_active')) {
                $exists = DB::table('academic_years')
                    ->where('tenant_id', currentTenantId())
                    ->where('is_active', true)
                    ->when($this->route('academic_year'), fn ($q) => $q->where('id', '!=', $this->route('academic_year')->id))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('is_active', 'Sudah ada tahun ajaran aktif. Nonaktifkan dulu sebelum mengaktifkan yang lain.');
                }
            }
        });
    }
}
