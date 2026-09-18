<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    private const DAY_LABELS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    public function index(): View
    {
        $year = AcademicYear::where('is_active', true)->first();

        $schedules = Schedule::with(['subject', 'rombel', 'teacher'])
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
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
        $rombels = Rombel::when($year, fn ($q) => $q->where('academic_year_id', $year->id))->orderBy('name')->get();
        $teachers = User::where('role', 'guru')->orderBy('name')->get();

        return view('schedules.create', compact('subjects', 'rombels', 'teachers', 'year'));
    }

    public function store(ScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['academic_year_id'] = $data['academic_year_id'] ?? AcademicYear::where('is_active', true)->value('id');

        $schedule = Schedule::create($data);

        log_audit('create', $schedule);

        return to_route('schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule): View
    {
        $year = AcademicYear::where('is_active', true)->first();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $rombels = Rombel::when($year, fn ($q) => $q->where('academic_year_id', $year->id))->orderBy('name')->get();
        $teachers = User::where('role', 'guru')->orderBy('name')->get();

        return view('schedules.edit', compact('schedule', 'subjects', 'rombels', 'teachers', 'year'));
    }

    public function update(ScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $old = $schedule->only(['subject_id', 'rombel_id', 'user_id', 'day_of_week', 'start_time', 'end_time']);

        $schedule->update($request->validated());

        log_audit('update', $schedule, $old, $schedule->only(['subject_id', 'rombel_id', 'user_id', 'day_of_week', 'start_time', 'end_time']));

        return to_route('schedules.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $schedule->delete();

        log_audit('delete', $schedule);

        return to_route('schedules.index')->with('success', 'Jadwal berhasil dihapus.');
    }
}
