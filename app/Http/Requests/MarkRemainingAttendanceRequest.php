<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkRemainingAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);
    }

    public function rules(): array
    {
        return [
            // 'hadir' sengaja tidak diterima: menandai sisa sebagai hadir
            // membatalkan gunanya scan, dan menutupi siswa yang tidak hadir.
            'status' => ['required', Rule::in(['sakit', 'izin', 'alpha'])],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status untuk siswa yang belum tercatat.',
            'status.in' => 'Status yang bisa dipakai untuk menandai sisa adalah sakit, izin, atau alpa.',
        ];
    }
}
