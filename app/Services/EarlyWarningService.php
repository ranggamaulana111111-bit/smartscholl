<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AssessmentGrade;
use App\Models\Attendance;
use App\Models\EarlyWarningLog;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EarlyWarningService
{
    private const ABSENCE_STREAK_DAYS = 3;

    private const ATTENDANCE_RATE_THRESHOLD = 80; // percent

    private const BELOW_KKM_SCORE = 75;

    public function runCheck(?string $tenantId = null): Collection
    {
        $academicYear = AcademicYear::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->first();

        $students = Student::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereHas('rombel', fn ($q) => $q->where('academic_year_id', $academicYear?->id))
            ->get();

        $logs = collect();

        foreach ($students as $student) {
            $logs = $logs->merge($this->checkAbsenceStreak($student, $academicYear))
                ->merge($this->checkAttendanceRate($student, $academicYear))
                ->merge($this->checkLowScore($student, $academicYear));
        }

        return $logs;
    }

    public function checkAbsenceStreak(Student $student, ?AcademicYear $academicYear): Collection
    {
        $logs = collect();

        $alphaDates = Attendance::query()
            ->where('student_id', $student->id)
            ->where('type', 'lesson')
            ->where('status', 'alpha')
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->latest('date')
            ->get()
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date))
            ->unique(fn ($date) => $date->toDateString())
            ->sortByDesc(fn ($date) => $date->timestamp)
            ->values();

        if ($alphaDates->isEmpty()) {
            return $logs;
        }

        $cursor = $alphaDates->first()->copy();
        $streak = 0;

        while (true) {
            while ($cursor->isWeekend()) {
                $cursor->subDay();
            }

            $hasAlpha = $alphaDates->contains(fn ($date) => $date->isSameDay($cursor));

            if (! $hasAlpha || $cursor->lt(now()->subDays(30))) {
                break;
            }

            $streak++;

            if ($streak >= self::ABSENCE_STREAK_DAYS) {
                break;
            }

            $cursor->subDay();
        }

        if ($streak >= self::ABSENCE_STREAK_DAYS) {
            $logs->push($this->createLog($student, 'absence_streak', sprintf(
                'Siswa tidak hadir tanpa keterangan %d hari sekolah berturut-turut (terakhir %s).',
                $streak,
                $alphaDates->first()->toDateString()
            )));
        }

        return $logs;
    }

    public function checkAttendanceRate(Student $student, ?AcademicYear $academicYear): Collection
    {
        $logs = collect();

        $scope = fn ($q) => $q
            ->when($academicYear, fn ($yq) => $yq->where('academic_year_id', $academicYear->id));

        $total = $scope(Attendance::query()
            ->where('student_id', $student->id)
            ->where('type', 'lesson')
            ->where('status', '!=', 'alpha'))
            ->count();

        $alpha = $scope(Attendance::query()
            ->where('student_id', $student->id)
            ->where('type', 'lesson')
            ->where('status', 'alpha'))
            ->count();

        $all = $total + $alpha;

        if ($all === 0) {
            return $logs;
        }

        $rate = round(($total / $all) * 100);

        if ($rate < self::ATTENDANCE_RATE_THRESHOLD) {
            $logs->push($this->createLog($student, 'attendance_rate', sprintf(
                'Presensi kumulatif siswa %.0f%% (di bawah ambang batas %d%%).',
                $rate,
                self::ATTENDANCE_RATE_THRESHOLD
            )));
        }

        return $logs;
    }

    public function checkLowScore(Student $student, ?AcademicYear $academicYear): Collection
    {
        $logs = collect();

        $lowGrades = AssessmentGrade::query()
            ->where('student_id', $student->id)
            ->with(['assessment.subject'])
            ->whereHas(
                'assessment',
                fn ($q) => $q
                    ->whereIn('category', ['formatif', 'uts', 'uas'])
                    ->when($academicYear, fn ($yq) => $yq->where('academic_year_id', $academicYear->id))
            )
            ->where('score', '<', self::BELOW_KKM_SCORE)
            ->whereBetween('created_at', [now()->subMonths(2), now()])
            ->latest()
            ->get();

        foreach ($lowGrades as $grade) {
            $logs->push($this->createLog($student, 'low_score', sprintf(
                'Rata-rata nilai %s di bawah KKM (%s: %.2f).',
                $grade->assessment?->subject?->name ?? 'mapel',
                $grade->assessment?->title ?? '-',
                $grade->score
            )));
        }

        return $logs;
    }

    private function createLog(Student $student, string $type, string $description): EarlyWarningLog
    {
        $unresolved = EarlyWarningLog::query()
            ->where('student_id', $student->id)
            ->where('type', $type)
            ->where('is_resolved', false)
            ->latest()
            ->first();

        if ($unresolved) {
            return $unresolved;
        }

        return EarlyWarningLog::firstOrCreate(
            [
                'student_id' => $student->id,
                'type' => $type,
                'trigger_date' => now()->toDateString(),
            ],
            [
                'tenant_id' => currentTenantId(),
                'description' => $description,
            ]
        );
    }
}
