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
        $tenantId = currentTenantId();

        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(
                    fn ($query) => $query->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'schedule_id' => [
                'nullable',
                'integer',
                Rule::exists('schedules', 'id')->where(
                    fn ($query) => $query->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
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
            'student_id.exists' => 'Siswa tidak ditemukan pada sekolah ini.',
            'schedule_id.exists' => 'Jadwal tidak ditemukan pada sekolah ini.',
            'type.required' => 'Tipe absensi wajib dipilih.',
            'status.required' => 'Status wajib dipilih.',
            'date.required' => 'Tanggal wajib diisi.',
        ];
    }
}
