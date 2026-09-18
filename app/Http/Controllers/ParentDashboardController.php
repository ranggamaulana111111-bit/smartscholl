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
            ->with('student.rombel')
            ->get();

        $children = $parentLinks->pluck('student');

        $recentAttendance = Attendance::whereIn('student_id', $children->pluck('id'))
            ->with('student')
            ->whereDate('date', now()->subWeek())
            ->latest('date')
            ->latest('time')
            ->get();

        $studentIds = $children->pluck('id')->toArray();
        $avgScores = AssessmentGrade::whereIn('student_id', $studentIds)
            ->with('assessment')
            ->get()
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
            ->get();

        return view('parent.dashboard', compact('children', 'recentAttendance', 'avgScores', 'warnings'));
    }
}
