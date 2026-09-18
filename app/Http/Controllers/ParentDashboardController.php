<?php

namespace App\Http\Controllers;

use App\Models\AssessmentGrade;
use App\Models\Attendance;
use App\Models\EarlyWarningLog;
use App\Models\StudentParent;
use Illuminate\View\View;

class ParentDashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $parentLinks = StudentParent::where('user_id', $user->id)
            ->with(['student.rombel.academicYear'])
            ->get();

        $children = $parentLinks->pluck('student')->filter();
        $studentIds = $children->pluck('id')->all();

        $recentAttendance = Attendance::whereIn('student_id', $studentIds)
            ->with('student')
            ->whereBetween('date', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->latest('date')
            ->latest('time')
            ->get();

        $yearByStudent = $children->mapWithKeys(
            fn ($child) => [$child->id => $child->rombel?->academicYear]
        )->filter();

        $avgScores = AssessmentGrade::whereIn('student_id', $studentIds)
            ->whereHas('assessment', fn ($query) => $query->whereIn('academic_year_id', $yearByStudent->pluck('id')->all()))
            ->with('assessment')
            ->get()
            ->filter(fn ($grade) => ($grade->assessment?->academic_year_id) === ($yearByStudent[$grade->student_id]->id ?? null))
            ->groupBy('student_id')
            ->map(function ($grades) {
                $byCategory = $grades->groupBy(fn ($g) => $g->assessment?->category);

                return [
                    'tugas' => $byCategory->get('tugas', collect())->avg('score'),
                    'formatif' => $byCategory->get('formatif', collect())->avg('score'),
                    'uts' => $byCategory->get('uts', collect())->avg('score'),
                    'uas' => $byCategory->get('uas', collect())->avg('score'),
                    'overall' => $grades->avg('score'),
                ];
            });

        $warnings = EarlyWarningLog::whereIn('student_id', $studentIds)
            ->with('student')
            ->where('is_resolved', false)
            ->latest('trigger_date')
            ->get()
            ->filter(function ($warning) use ($yearByStudent) {
                $year = $yearByStudent[$warning->student_id] ?? null;

                if (! $year) {
                    return false;
                }

                $trigger = $warning->trigger_date?->toDateString();

                return $trigger !== null
                    && $trigger >= $year->start_date->toDateString()
                    && $trigger <= $year->end_date->toDateString();
            })
            ->values();

        return view('parent.dashboard', compact('children', 'recentAttendance', 'avgScores', 'warnings'));
    }
}
