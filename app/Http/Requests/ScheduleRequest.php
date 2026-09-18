<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']);
    }

    public function rules(): array
    {
        $tenantId = currentTenantId();

        return [
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                        ->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'guru')
                        ->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                        ->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'rombel_id' => [
                'required',
                'integer',
                Rule::exists('rombels', 'id')->where(
                    fn ($query) => $query->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'academic_year_id.exists' => 'Tahun ajaran aktif tidak ditemukan pada sekolah ini.',
            'user_id.required' => 'Guru wajib dipilih.',
            'user_id.exists' => 'Guru yang dipilih tidak terdaftar pada sekolah ini.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'subject_id.exists' => 'Mata pelajaran aktif tidak ditemukan pada sekolah ini.',
            'rombel_id.required' => 'Rombel wajib dipilih.',
            'rombel_id.exists' => 'Rombel tidak ditemukan pada sekolah ini.',
            'day_of_week.required' => 'Hari wajib dipilih.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tenantId = currentTenantId();
            $academicYearId = $this->input('academic_year_id');
            $day = $this->input('day_of_week');
            $start = ($this->input('start_time') ?? '').':00';
            $end = ($this->input('end_time') ?? '').':00';
            $userId = $this->input('user_id');
            $rombelId = $this->input('rombel_id');

            $year = AcademicYear::find($academicYearId);
            $teacher = User::find($userId);
            $subject = Subject::find($this->input('subject_id'));
            $rombel = Rombel::find($rombelId);

            $tenantIds = collect([$year, $teacher, $subject, $rombel])
                ->filter()
                ->pluck('tenant_id')
                ->unique()
                ->values();

            if ($tenantIds->count() > 1 || ($tenantId && $tenantIds->isNotEmpty() && $tenantIds->first() !== $tenantId)) {
                $validator->errors()->add('rombel_id', 'Data yang dipilih berasal dari sekolah yang berbeda.');

                return;
            }

            if ($year && $rombel && (int) $rombel->academic_year_id !== (int) $year->id) {
                $validator->errors()->add('rombel_id', 'Rombel tidak terdaftar pada tahun ajaran yang dipilih.');
            }

            $conflict = Schedule::query()
                ->where('academic_year_id', $academicYearId)
                ->where('day_of_week', $day)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('rombel_id', $rombelId))
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->when($this->route('schedule'), fn ($q) => $q->where('id', '!=', $this->route('schedule')->id))
                ->exists();

            if ($conflict) {
                $validator->errors()->add('start_time', 'Jadwal bentrok dengan jam mengajar guru atau kelas lain pada rentang waktu tersebut.');
            }
        });
    }
}
