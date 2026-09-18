<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::with(['schedules.teacher', 'schedules.rombel'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $subjects->getCollection()->transform(function (Subject $subject) {
            $teacherNames = $subject->schedules->pluck('teacher.name')->filter()->unique()->values();

            if ($teacherNames->isEmpty()) {
                $teacherNames = Teacher::where('subject', $subject->name)->pluck('name')->values();
            }

            $subject->setAttribute('teacher_names', $teacherNames->all());
            $subject->setAttribute('rombel_names', $subject->schedules->pluck('rombel.name')->filter()->unique()->values()->all());

            return $subject;
        });

        return view('subjects.index', compact('subjects'));
    }

    public function show(Subject $subject): View
    {
        $subject->load(['schedules.teacher', 'schedules.rombel']);

        $teacherNames = $subject->schedules->pluck('teacher.name')->filter()->unique()->values();

        if ($teacherNames->isEmpty()) {
            $teacherNames = Teacher::where('subject', $subject->name)->pluck('name')->values();
        }

        $rombelNames = $subject->schedules->pluck('rombel.name')->filter()->unique()->values();

        $schedules = $subject->schedules->sortBy(fn ($s) => [((int) $s->day_of_week + 6) % 7, $s->start_time?->format('H:i')]);

        return view('subjects.show', compact('subject', 'teacherNames', 'rombelNames', 'schedules'));
    }

    public function create(): View
    {
        return view('subjects.create');
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());

        log_audit('create', $subject);

        return to_route('subjects.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(Subject $subject): View
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $old = $subject->only(['name', 'code', 'is_active']);
        $subject->update($request->validated());

        log_audit('update', $subject, $old, $subject->only(['name', 'code', 'is_active']));

        return to_route('subjects.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        log_audit('delete', $subject);

        return to_route('subjects.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
