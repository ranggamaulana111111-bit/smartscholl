<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'schedule_id' => ['nullable', 'exists:schedules,id'],
            'type' => ['required', Rule::in(['gate_in', 'gate_out', 'lesson'])],
            'status' => ['required', Rule::in(['hadir', 'sakit', 'izin', 'alpha'])],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa wajib dipilih.',
            'type.required' => 'Tipe absensi wajib dipilih.',
            'status.required' => 'Status wajib dipilih.',
            'date.required' => 'Tanggal wajib diisi.',
        ];
    }
}
