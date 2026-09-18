<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\AssessmentGrade;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\EarlyWarningLog;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\StudentRombelHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    private const CATEGORY_WEIGHTS = ['tugas' => 20, 'formatif' => 30, 'uts' => 25, 'uas' => 25];

    public function index(): View
    {
        $students = Student::with('rombel')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('students.index', compact('students'));
    }

    public function create(): View
    {
        $rombels = Rombel::orderBy('name')->get();

        return view('students.create', compact('rombels'));
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $student = DB::transaction(function () use ($request) {
            $student = Student::create($request->validated());

            if ($student->rombel_id) {
                $this->openRombelHistory($student);
            }

            return $student;
        });

        log_audit('create', $student);

        return to_route('students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        $this->authorizeView($student);

        $student->load(['rombel', 'rombel.academicYear', 'user', 'rombelHistories.rombel']);

        $year = $student->rombel?->academicYear;
        $attendance = $this->attendanceSummary($student);

        $recentAttendance = Attendance::with('recordedBy')
            ->where('student_id', $student->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->latest('date')
            ->limit(10)
            ->get();

        $bySubject = AssessmentGrade::with(['assessment.subject'])
            ->where('student_id', $student->id)
            ->when($year, fn ($q) => $q->whereHas('assessment', fn ($aq) => $aq->where('academic_year_id', $year->id)))
            ->get()
            ->groupBy(fn ($grade) => $grade->assessment->subject_id)
            ->map(function ($subjectGrades) {
                return [
                    'name' => $subjectGrades->first()->assessment->subject->name ?? '-',
                    'avg' => round($subjectGrades->avg('score'), 2),
                ];
            })
            ->values();

        return view('students.show', compact('student', 'attendance', 'recentAttendance', 'bySubject'));
    }

    public function edit(Student $student): View
    {
        $rombels = Rombel::orderBy('name')->get();

        return view('students.edit', compact('student', 'rombels'));
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $old = $student->only(['nisn', 'name', 'rombel_id']);

        DB::transaction(function () use ($request, $student) {
            $previousRombelId = $student->rombel_id;
            $student->update($request->validated());

            if ($student->rombel_id !== $previousRombelId) {
                if ($previousRombelId) {
                    $this->closeRombelHistory($previousRombelId, $student->id);
                }

                if ($student->rombel_id) {
                    $this->openRombelHistory($student);
                }
            }
        });

        log_audit('update', $student, $old, $student->only(['nisn', 'name', 'rombel_id']));

        return to_route('students.index')->with('success', 'Siswa berhasil diperbarui.');
    }

    public function regenerateQr(Student $student): RedirectResponse
    {
        $old = ['qr_token' => $student->qr_token];
        $student->regenerateQrToken();

        log_audit('update', $student, $old, ['qr_token' => $student->qr_token]);

        return back()->with('success', 'Token QR '.$student->name.' berhasil dibuat ulang.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorizeView($student);

        $dependents = [
            'Absensi' => fn () => $student->attendances()->count(),
            'Nilai' => fn () => $student->assessmentGrades()->count(),
            'Tugas' => fn () => $student->assignmentSubmissions()->count(),
            'Peringatan EWS' => fn () => $student->earlyWarningLogs()->count(),
            'Relasi orang tua' => fn () => $student->parentLinks()->count(),
            'Riwayat rombel' => fn () => $student->rombelHistories()->count(),
        ];

        $inUse = collect($dependents)->filter(fn ($count) => $count() > 0);

        if ($inUse->isNotEmpty()) {
            $labels = $inUse->map(fn ($count, $label) => "$label: {$count()}")->implode(', ');

            return back()->withErrors("Siswa tidak dapat dihapus karena masih memiliki data terkait: {$labels}.");
        }

        $student->delete();

        log_audit('delete', $student);

        return to_route('students.index')->with('success', 'Siswa berhasil dihapus.');
    }

    public function progress(Student $student): View
    {
        $this->authorizeView($student);

        $student->load(['rombel', 'rombel.academicYear']);
        $year = $student->rombel?->academicYear;

        $grades = AssessmentGrade::with(['assessment.subject'])
            ->where('student_id', $student->id)
            ->when($year, fn ($q) => $q->whereHas('assessment', fn ($aq) => $aq->where('academic_year_id', $year->id)))
            ->orderBy('created_at')
            ->get();

        $bySubject = $grades->groupBy(fn ($g) => $g->assessment->subject_id)
            ->map(function ($subjectGrades) {
                $subject = $subjectGrades->first()->assessment->subject;

                $byCategory = $subjectGrades->groupBy('assessment.category')
                    ->map(
                        fn ($categoryGrades) => [
                            'avg' => round($categoryGrades->avg('score'), 2),
                            'count' => $categoryGrades->count(),
                        ]
                    );

                return [
                    'name' => $subject->name ?? '-',
                    'categories' => $byCategory,
                    'avg' => round($subjectGrades->avg('score'), 2),
                ];
            })
            ->values();

        $attendance = $this->attendanceSummary($student);

        $recentAttendance = Attendance::with('recordedBy')
            ->where('student_id', $student->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->latest('date')
            ->limit(15)
            ->get();

        $ewsLogs = EarlyWarningLog::where('student_id', $student->id)
            ->when($year, fn ($q) => $q->whereBetween('trigger_date', [$year->start_date, $year->end_date]))
            ->latest()
            ->get();

        $assignments = Assignment::with(['subject', 'submissions' => fn ($q) => $q->where('student_id', $student->id)])
            ->where('rombel_id', $student->rombel_id)
            ->orderByDesc('deadline_at')
            ->get()
            ->map(function (Assignment $assignment) {
                $submission = $assignment->submissions->first();

                return [
                    'assignment' => $assignment,
                    'submitted' => ! is_null($submission),
                    'submitted_at' => $submission?->submitted_at,
                ];
            });

        $scoreTrend = AssessmentGrade::query()
            ->with('assessment.academicYear')
            ->where('student_id', $student->id)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($g) => $g->assessment->academic_year_id)
            ->map(function ($rows): array {
                $academicYear = $rows->first()->assessment->academicYear;

                return [
                    'year' => $academicYear?->name ?? '-',
                    'semester' => $academicYear?->semester ?? '',
                    'avg' => round($rows->avg('score'), 2),
                    'count' => $rows->count(),
                ];
            })
            ->sortBy(fn ($row) => $row['year'])
            ->values();

        return view('students.progress', compact('student', 'bySubject', 'attendance', 'recentAttendance', 'ewsLogs', 'assignments', 'scoreTrend'));
    }

    public function rapor(Student $student): View
    {
        $this->authorizeView($student);

        $student->load(['rombel', 'rombel.academicYear']);
        $year = $student->rombel?->academicYear;

        $grades = AssessmentGrade::with(['assessment.subject'])
            ->where('student_id', $student->id)
            ->when($year, fn ($q) => $q->whereHas('assessment', fn ($aq) => $aq->where('academic_year_id', $year->id)))
            ->get();

        $rows = $grades->groupBy(fn ($g) => $g->assessment->subject_id)
            ->map(function ($subjectGrades) {
                $subject = $subjectGrades->first()->assessment->subject;
                $byCategory = $subjectGrades->groupBy('assessment.category');

                $aver = [];
                $weighted = [];
                $coveredWeight = 0;

                foreach (array_keys(self::CATEGORY_WEIGHTS) as $category) {
                    $categoryGrades = $byCategory->get($category);

                    if ($categoryGrades) {
                        $score = round($categoryGrades->avg('score'), 2);
                        $weight = $categoryGrades->first()->assessment->weight_percentage ?? self::CATEGORY_WEIGHTS[$category];
                        $aver[$category] = $score;
                        $weighted[$category] = $score * $weight / 100;
                        $coveredWeight += $weight;
                    }
                }

                $finalScore = $coveredWeight > 0 ? round(array_sum($weighted) * 100 / $coveredWeight, 2) : null;

                return [
                    'name' => $subject->name ?? '-',
                    'aver' => $aver,
                    'final_score' => $finalScore,
                ];
            })
            ->values();

        $attendance = $this->attendanceSummary($student);

        return view('students.rapor', compact('student', 'rows', 'attendance'));
    }

    private function authorizeView(Student $student): void
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->hasRole('admin_sekolah')) {
            return;
        }

        if ($user->hasRole('guru')) {
            abort_unless(
                $student->rombel?->homeroom_teacher_id === $user->id,
                403,
                'Anda hanya dapat mengakses data siswa di rombel binaan Anda.'
            );

            return;
        }

        if ($user->hasRole('siswa')) {
            abort_unless($student->user_id === $user->id, 403, 'Anda tidak berhak mengakses data siswa ini.');

            return;
        }

        if ($user->hasRole('orang_tua')) {
            $linked = $student->parentLinks()->where('user_id', $user->id)->exists();

            abort_unless($linked, 403, 'Anda tidak berhak mengakses data siswa ini.');

            return;
        }

        abort(403);
    }

    /**
     * Ringkasan kehadiran hanya menghitung absensi pelajaran (type = lesson).
     * Baris gerbang (gate_in/gate_out) adalah peristiwa masuk/pulang, bukan status
     * kehadiran sesi, sehingga tidak boleh dicampur atau dihitung ganda.
     */
    private function attendanceSummary(Student $student): array
    {
        $year = $student->rombel?->academicYear;

        $rows = Attendance::where('student_id', $student->id)
            ->where('type', 'lesson')
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id));

        $counts = (clone $rows)->get()->groupBy('status')
            ->map(fn ($group) => $group->count());

        return [
            'hadir' => $counts['hadir'] ?? 0,
            'sakit' => $counts['sakit'] ?? 0,
            'izin' => $counts['izin'] ?? 0,
            'alpha' => $counts['alpha'] ?? 0,
            'total' => $rows->count(),
        ];
    }

    private function openRombelHistory(Student $student): void
    {
        $today = today()->toDateString();

        $student->rombelHistories()
            ->whereNull('left_at')
            ->update(['left_at' => $today]);

        $student->rombelHistories()->create([
            'rombel_id' => $student->rombel_id,
            'entered_at' => $today,
        ]);
    }

    private function closeRombelHistory(int $rombelId, int $studentId): void
    {
        StudentRombelHistory::where('student_id', $studentId)
            ->where('rombel_id', $rombelId)
            ->whereNull('left_at')
            ->update(['left_at' => today()->toDateString()]);
    }
}
