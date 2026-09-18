<?php

namespace App\Http\Requests;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']);
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'user_id' => ['required', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'rombel_id' => ['required', 'exists:rombels,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'user_id.required' => 'Guru wajib dipilih.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'rombel_id.required' => 'Rombel wajib dipilih.',
            'day_of_week.required' => 'Hari wajib dipilih.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $academicYearId = $this->input('academic_year_id');
            $day = $this->input('day_of_week');
            $start = ($this->input('start_time') ?? '').':00';
            $end = ($this->input('end_time') ?? '').':00';
            $userId = $this->input('user_id');
            $rombelId = $this->input('rombel_id');

            $conflict = Schedule::query()
                ->where('academic_year_id', $academicYearId)
                ->where('day_of_week', $day)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('rombel_id', $rombelId))
                ->when($this->route('schedule'), fn ($q) => $q->where('id', '!=', $this->route('schedule')->id))
                ->exists();

            if ($conflict) {
                $validator->errors()->add('start_time', 'Jadwal bentrok dengan jam mengajar guru atau kelas lain pada rentang waktu tersebut.');
            }
        });
    }
}
