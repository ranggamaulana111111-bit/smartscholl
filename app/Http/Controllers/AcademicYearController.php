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
        AcademicYear::create($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return to_route('academic-years.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        return view('academic-years.edit', compact('academicYear'));
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return to_route('academic-years.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->delete();

        return to_route('academic-years.index')->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
