<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tombol status di roster langsung.
 *
 * Roster berasal dari polling halaman yang sudah terbuka, jadi id siswa di sini
 * bisa saja milik rombel lain bila halaman dibiarkan terbuka lama dan rombel
 * siswa berubah. Validasi di gate menjaga supaya id itu tetap harus anggota
 * rombel jadwal yang sedang diisi.
 */
class MarkLessonStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where('rombel_id', $this->route('schedule')?->rombel_id),
            ],
            'status' => ['required', 'string', Rule::in(Attendance::STATUSES)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Pilih siswa yang absennya akan diubah.',
            'student_id.exists' => 'Siswa ini bukan anggota rombel jadwal yang sedang diisi.',
            'status.in' => 'Status kehadiran tidak dikenal.',
        ];
    }
}