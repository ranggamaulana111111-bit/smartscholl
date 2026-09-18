<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\Rombel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::withCount('rombels')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('academic-years.index', compact('academicYears'));
    }

    public function create(): View
    {
        return view('academic-years.create');
    }

    public function show(AcademicYear $academicYear): View
    {
        $rombels = Rombel::where('academic_year_id', $academicYear->id)
            ->with('homeroomTeacher')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $totalStudents = $rombels->sum('students_count');

        return view('academic-years.show', compact('academicYear', 'rombels', 'totalStudents'));
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        $academicYear = AcademicYear::create($request->validated() + ['is_active' => $request->boolean('is_active')]);

        log_audit('create', $academicYear);

        return to_route('academic-years.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        return view('academic-years.edit', compact('academicYear'));
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $old = $academicYear->only(['name', 'semester', 'start_date', 'end_date', 'is_active']);

        $academicYear->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        log_audit('update', $academicYear, $old, $academicYear->only(['name', 'semester', 'start_date', 'end_date', 'is_active']));

        return to_route('academic-years.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $dependents = [
            'Rombel' => fn () => $academicYear->rombels()->count(),
            'Jadwal' => fn () => $academicYear->schedules()->count(),
            'Jurnal pembelajaran' => fn () => $academicYear->journals()->count(),
            'Penilaian' => fn () => $academicYear->assessments()->count(),
        ];

        $inUse = collect($dependents)->filter(fn ($count) => $count() > 0);

        if ($inUse->isNotEmpty()) {
            $labels = $inUse->map(fn ($count, $label) => "$label: {$count()}")->implode(', ');

            return back()->withErrors("Tahun ajaran tidak dapat dihapus karena masih memiliki data terkait: {$labels}.");
        }

        $academicYear->delete();

        log_audit('delete', $academicYear);

        return to_route('academic-years.index')->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
