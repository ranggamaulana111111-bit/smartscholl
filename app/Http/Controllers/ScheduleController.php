<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $year = AcademicYear::where('is_active', true)->first();
        $user = auth()->user();

        $schedules = Schedule::with(['subject', 'rombel', 'teacher'])
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->when($user->isGuru(), fn ($q) => $q->whereHas(
                'teacher',
                fn ($teacherQuery) => $teacherQuery->where('user_id', $user->id)
            ))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(30)
            ->withQueryString();

        return view('schedules.index', compact('schedules', 'year'));
    }

    public function create(): View
    {
        $year = AcademicYear::where('is_active', true)->first();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $rombels = Rombel::when($year, fn ($query) => $query->where('academic_year_id', $year->id))->orderBy('name')->get();
        $teachers = Teacher::with('subject')
            ->whereHas('subject', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('schedules.create', compact('subjects', 'rombels', 'teachers', 'year'));
    }

    public function store(ScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $teacher = Teacher::findOrFail($data['teacher_id']);
        $data['user_id'] = $teacher->user_id;
        $data['tenant_id'] = $teacher->tenant_id;
        $data['academic_year_id'] = $data['academic_year_id'] ?? AcademicYear::where('is_active', true)
            ->where('tenant_id', $teacher->tenant_id)
            ->value('id');

        $schedule = Schedule::create($data);

        log_audit('create', $schedule);

        return to_route('schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule): View
    {
        $year = AcademicYear::where('is_active', true)->first();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $rombels = Rombel::when($year, fn ($query) => $query->where('academic_year_id', $year->id))->orderBy('name')->get();
        $teachers = Teacher::with('subject')
            ->whereHas('subject', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('schedules.edit', compact('schedule', 'subjects', 'rombels', 'teachers', 'year'));
    }

    public function update(ScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $old = $schedule->only(['teacher_id', 'subject_id', 'rombel_id', 'user_id', 'day_of_week', 'start_time', 'end_time']);
        $data = $request->validated();
        $teacher = Teacher::findOrFail($data['teacher_id']);
        $data['user_id'] = $teacher->user_id;
        $data['tenant_id'] = $teacher->tenant_id;
        $data['academic_year_id'] = $data['academic_year_id'] ?? $schedule->academic_year_id;

        $schedule->update($data);

        log_audit('update', $schedule, $old, $schedule->only(['teacher_id', 'subject_id', 'rombel_id', 'user_id', 'day_of_week', 'start_time', 'end_time']));

        return to_route('schedules.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        if ($schedule->journals()->exists() || $schedule->attendances()->where('type', 'lesson')->exists()) {
            return back()->withErrors('Jadwal tidak dapat dihapus karena masih memiliki jurnal atau catatan absensi pelajaran.');
        }

        $schedule->delete();

        log_audit('delete', $schedule);

        return to_route('schedules.index')->with('success', 'Jadwal berhasil dihapus.');
    }
}
