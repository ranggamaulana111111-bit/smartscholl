<?php

namespace App\Http\Controllers;

use App\Http\Requests\RombelRequest;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RombelController extends Controller
{
    public function index(): View
    {
        $rombels = Rombel::with(['academicYear', 'homeroomTeacher'])
            ->withCount('students')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('rombels.index', compact('rombels'));
    }

    public function create(): View
    {
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $teachers = User::whereIn('role', ['guru', 'admin_sekolah'])->orderBy('name')->get();

        return view('rombels.create', compact('academicYears', 'teachers'));
    }

    public function show(Rombel $rombel): View
    {
        $rombel->load([
            'academicYear',
            'homeroomTeacher',
            'students' => fn ($q) => $q->orderBy('name'),
        ]);

        $schedules = Schedule::where('rombel_id', $rombel->id)
            ->with(['subject', 'teacher'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('rombels.show', compact('rombel', 'schedules'));
    }

    public function store(RombelRequest $request): RedirectResponse
    {
        Rombel::create($request->validated());

        return to_route('rombels.index')->with('success', 'Rombel berhasil ditambahkan.');
    }

    public function edit(Rombel $rombel): View
    {
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $teachers = User::whereIn('role', ['guru', 'admin_sekolah'])->orderBy('name')->get();

        return view('rombels.edit', compact('rombel', 'academicYears', 'teachers'));
    }

    public function update(RombelRequest $request, Rombel $rombel): RedirectResponse
    {
        $rombel->update($request->validated());

        return to_route('rombels.index')->with('success', 'Rombel berhasil diperbarui.');
    }

    public function destroy(Rombel $rombel): RedirectResponse
    {
        $rombel->delete();

        return to_route('rombels.index')->with('success', 'Rombel berhasil dihapus.');
    }
}
