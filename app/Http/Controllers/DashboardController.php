<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssessmentGrade;
use App\Models\Attendance;
use App\Models\EarlyWarningLog;
use App\Models\Journal;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        [$stats, $tenants, $recentUsers] = match ($user->role) {
            'super_admin' => [$this->superAdminStats(), $this->tenantOverview(), []],
            'admin_sekolah' => [$this->adminSekolahStats(), [], $this->recentTenantUsers()],
            'guru' => [$this->guruStats(), [], []],
            'siswa' => [$this->siswaStats(), [], []],
            'orang_tua' => [$this->orangTuaStats(), [], []],
            default => [[], [], []],
        };

        $recentAttendances = match (true) {
            $user->isAdminSekolah() => Attendance::with(['student'])
                ->whereDate('date', now())
                ->latest('time')
                ->limit(8)
                ->get(),
            $user->isGuru() => Attendance::with(['student'])
                ->whereDate('date', now())
                ->whereHas('student.rombel', fn ($q) => $q->where('homeroom_teacher_id', $user->id))
                ->latest('time')
                ->limit(8)
                ->get(),
            default => collect(),
        };

        $pendingJournals = $user->hasAnyRole(['admin_sekolah', 'guru'])
            ? $this->pendingJournals()
            : collect();

        return view('dashboard', compact('user', 'stats', 'tenants', 'recentUsers', 'recentAttendances', 'pendingJournals'));
    }

    private function superAdminStats(): array
    {
        return [
            'total_tenants' => Tenant::count(),
            'total_users' => User::count(),
            'total_students' => User::where('role', 'siswa')->count(),
            'total_teachers' => User::where('role', 'guru')->count(),
        ];
    }

    private function tenantOverview(): array
    {
        return Tenant::orderBy('name')
            ->withCount('users')
            ->get()
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'status' => $tenant->status,
                'user_count' => $tenant->users_count,
            ])
            ->all();
    }

    private function adminSekolahStats(): array
    {
        $today = Attendance::whereDate('date', now());
        $todayScheduleCount = Schedule::query()
            ->where('day_of_week', now()->dayOfWeekIso)
            ->count();
        $teachingToday = Schedule::query()
            ->where('day_of_week', now()->dayOfWeekIso)
            ->distinct('user_id')
            ->count('user_id');
        $teachersTotal = Teacher::count();

        return [
            'total_users' => User::count(),
            'total_students' => Student::count(),
            'total_teachers' => $teachersTotal,
            'total_rombels' => Rombel::count(),
            'active_academic_year' => AcademicYear::where('is_active', true)->value('name'),
            'today_present' => (clone $today)->where('status', 'hadir')->distinct('student_id')->count('student_id'),
            'today_absent' => Student::count() - (clone $today)->where('status', 'hadir')->distinct('student_id')->count('student_id'),
            'pending_journals' => Journal::whereDate('date', now())->where('status', 'draft')->count(),
            'unresolved_ews' => EarlyWarningLog::where('is_resolved', false)->count(),
            'today_schedules' => $todayScheduleCount,
            'teachers_teaching_today' => $teachingToday,
            'teachers_idle_today' => max(0, $teachersTotal - $teachingToday),
        ];
    }

    private function recentTenantUsers(): array
    {
        return User::latest()
            ->limit(5)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ])
            ->all();
    }

    private function guruStats(): array
    {
        $userId = auth()->id();
        $today = now()->toDateString();

        $todaySchedule = Schedule::with(['subject', 'rombel'])
            ->where('user_id', $userId)
            ->where('day_of_week', now()->dayOfWeekIso)
            ->orderBy('start_time')
            ->get();

        $myJournals = Journal::where('user_id', $userId);
        $totalJournals = (clone $myJournals)->count();
        $draftJournals = (clone $myJournals)->where('status', 'draft')->count();

        $myStudents = Student::whereHas('rombel', function ($q) use ($userId) {
            $q->where('homeroom_teacher_id', $userId);
        })->count();

        return [
            'today_schedule' => $todaySchedule,
            'total_journals' => $totalJournals,
            'draft_journals' => $draftJournals,
            'homeroom_students' => $myStudents,
        ];
    }

    private function siswaStats(): array
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (! $student) {
            return ['avg_score' => 0, 'present_today' => false, 'absence_count' => 0];
        }

        $avgScore = AssessmentGrade::where('student_id', $student->id)->avg('score');

        $presentToday = Attendance::where('student_id', $student->id)
            ->whereDate('date', now())
            ->where('status', 'hadir')
            ->exists();

        $absenceCount = Attendance::where('student_id', $student->id)
            ->whereDate('date', '>=', now()->subMonth())
            ->where('status', 'alpha')
            ->count();

        return [
            'avg_score' => round($avgScore ?? 0, 1),
            'present_today' => $presentToday,
            'absence_count' => $absenceCount,
        ];
    }

    private function orangTuaStats(): array
    {
        $childrenCount = StudentParent::where('user_id', auth()->id())->count();

        $warningCount = EarlyWarningLog::where('is_resolved', false)
            ->whereHas('student', function ($q) {
                $q->whereHas('parentLinks', fn ($pq) => $pq->where('user_id', auth()->id()));
            })
            ->count();

        return [
            'children_count' => $childrenCount,
            'warning_count' => $warningCount,
        ];
    }

    private function pendingJournals(): Collection
    {
        $user = auth()->user();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $today = now()->toDateString();

        return Schedule::with(['subject', 'rombel', 'teacher'])
            ->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))
            ->when($user->isGuru(), fn ($query) => $query->where('user_id', $user->id))
            ->where('day_of_week', now()->dayOfWeekIso)
            ->whereNotIn('id', function ($query) use ($today) {
                $query->select('schedule_id')
                    ->from('journals')
                    ->whereDate('date', $today)
                    ->whereNotNull('schedule_id');
            })
            ->orderBy('start_time')
            ->get();
    }
}
