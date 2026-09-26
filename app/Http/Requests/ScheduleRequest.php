<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']);
    }

    protected function prepareForValidation(): void
    {
        $schedule = $this->route('schedule');

        if (! $this->filled('academic_year_id') && $schedule instanceof Schedule) {
            $this->merge(['academic_year_id' => $schedule->academic_year_id]);
        }
    }

    public function rules(): array
    {
        $tenantId = currentTenantId();

        return [
            'academic_year_id' => [
                'sometimes',
                'integer',
                Rule::exists('academic_years', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                        ->when($tenantId, fn ($scoped) => $scoped->where('tenant_id', $tenantId))
                ),
            ],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('teachers', 'id')->where(
                    fn ($query) => $query->when(
                        $tenantId,
                        fn ($scoped) => $scoped->where('tenant_id', $tenantId)
                    )
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
            'teacher_id.required' => 'Guru wajib dipilih.',
            'teacher_id.exists' => 'Guru yang dipilih tidak terdaftar pada sekolah ini.',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantId = currentTenantId();
            $teacherId = (int) $this->input('teacher_id');
            $teacher = Teacher::find($teacherId);

            if (! $teacher) {
                return;
            }

            $targetTenantId = $tenantId ?? $teacher->tenant_id;

            if (! $targetTenantId) {
                $validator->errors()->add('teacher_id', 'Guru harus memiliki sekolah.');

                return;
            }

            $academicYearId = $this->input('academic_year_id') ?: AcademicYear::query()
                ->where('is_active', true)
                ->where('tenant_id', $targetTenantId)
                ->value('id');

            if (! $academicYearId) {
                $validator->errors()->add('academic_year_id', 'Tahun ajaran aktif tidak ditemukan pada sekolah ini.');

                return;
            }

            $year = AcademicYear::query()
                ->where('tenant_id', $targetTenantId)
                ->find($academicYearId);
            $subject = Subject::query()
                ->where('tenant_id', $targetTenantId)
                ->find($this->input('subject_id'));
            $rombel = Rombel::query()
                ->where('tenant_id', $targetTenantId)
                ->find($this->input('rombel_id'));

            if (! $year) {
                $validator->errors()->add('academic_year_id', 'Tahun ajaran tidak ditemukan pada sekolah guru.');
            }

            if (! $subject) {
                $validator->errors()->add('subject_id', 'Mata pelajaran tidak ditemukan pada sekolah guru.');
            }

            if (! $rombel) {
                $validator->errors()->add('rombel_id', 'Rombel tidak ditemukan pada sekolah guru.');
            }

            if (! $year || ! $subject || ! $rombel) {
                return;
            }

            if ($teacher->subject_id === null) {
                $validator->errors()->add('subject_id', 'Mata pelajaran utama guru belum ditentukan.');
            } elseif ((int) $teacher->subject_id !== (int) $subject->id) {
                $validator->errors()->add('subject_id', 'Mata pelajaran tidak sesuai dengan mata pelajaran utama guru.');
            }

            if ($teacher->user_id) {
                $linkedUser = User::find($teacher->user_id);

                if (! $linkedUser || $linkedUser->role !== 'guru' || $linkedUser->tenant_id !== $teacher->tenant_id) {
                    $validator->errors()->add('teacher_id', 'Akun guru tidak valid atau bukan akun guru sekolah ini.');
                }
            }

            if ((int) $rombel->academic_year_id !== (int) $year->id) {
                $validator->errors()->add('rombel_id', 'Rombel tidak terdaftar pada tahun ajaran yang dipilih.');
            }

            $day = $this->input('day_of_week');
            $start = ($this->input('start_time') ?? '').':00';
            $end = ($this->input('end_time') ?? '').':00';
            $routeSchedule = $this->route('schedule');

            $conflict = Schedule::query()
                ->where('tenant_id', $targetTenantId)
                ->where('academic_year_id', $year->id)
                ->where('day_of_week', $day)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->where(fn ($query) => $query
                    ->where('teacher_id', $teacher->id)
                    ->when($teacher->user_id, fn ($teacherConflict) => $teacherConflict->orWhere('user_id', $teacher->user_id))
                    ->orWhere('rombel_id', $rombel->id)
                )
                ->when($routeSchedule instanceof Schedule, fn ($query) => $query->where('id', '!=', $routeSchedule->id))
                ->exists();

            if ($conflict) {
                $validator->errors()->add('start_time', 'Jadwal bentrok dengan jam mengajar guru atau kelas lain pada rentang waktu tersebut.');
            }
        });
    }
}
