<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherRequest;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        $teachers = Teacher::with('subject')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('teachers.index', compact('teachers'));
    }

    public function show(Teacher $teacher): View
    {
        $schedules = $teacher->schedules()
            ->with(['subject', 'rombel'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('teachers.show', compact('teacher', 'schedules'));
    }

    public function create(): View
    {
        return view('teachers.create', [
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        $teacher = Teacher::create($request->validated());

        log_audit('create', $teacher);

        return to_route('teachers.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function edit(Teacher $teacher): View
    {
        return view('teachers.edit', [
            'teacher' => $teacher,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(TeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validated();
        $old = $teacher->only(['user_id', 'nuptk', 'nip', 'name', 'subject_id', 'subject_text', 'employment_status', 'address', 'phone']);

        if ((int) $teacher->subject_id !== (int) $data['subject_id'] && $teacher->schedules()->exists()) {
            return to_route('teachers.index')->withErrors([
                'subject_id' => 'Mata pelajaran utama tidak dapat diubah selama guru masih memiliki jadwal mengajar.',
            ]);
        }

        $teacher->update($data);

        log_audit('update', $teacher, $old, $teacher->only(['user_id', 'nuptk', 'nip', 'name', 'subject_id', 'subject_text', 'employment_status', 'address', 'phone']));

        return to_route('teachers.index')->with('success', 'Guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        if ($teacher->schedules()->exists()) {
            return to_route('teachers.index')->withErrors(
                'Guru tidak dapat dihapus karena masih memiliki jadwal mengajar. Pindahkan atau hapus jadwalnya terlebih dahulu.'
            );
        }

        $teacher->delete();

        log_audit('delete', $teacher);

        return to_route('teachers.index')->with('success', 'Guru berhasil dihapus.');
    }
}
