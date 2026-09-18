<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherRequest;
use App\Models\Schedule;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        $teachers = Teacher::latest()
            ->paginate(15)
            ->withQueryString();

        return view('teachers.index', compact('teachers'));
    }

    public function show(Teacher $teacher): View
    {
        $schedules = Schedule::where('user_id', $teacher->user_id)
            ->with(['subject', 'rombel'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('teachers.show', compact('teacher', 'schedules'));
    }

    public function create(): View
    {
        return view('teachers.create');
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        Teacher::create($request->validated());

        return to_route('teachers.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function edit(Teacher $teacher): View
    {
        return view('teachers.edit', compact('teacher'));
    }

    public function update(TeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $teacher->update($request->validated());

        return to_route('teachers.index')->with('success', 'Guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return to_route('teachers.index')->with('success', 'Guru berhasil dihapus.');
    }
}
