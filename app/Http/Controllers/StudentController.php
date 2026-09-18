<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\AssessmentGrade;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\EarlyWarningLog;
use App\Models\Rombel;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
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
        $student = Student::create($request->validated());

        log_audit('create', $student);

        return to_route('students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        $this->authorizeView($student);

        $student->load(['rombel', 'rombel.academicYear', 'user']);

        $attendance = $this->attendanceSummary($student);

        $recentAttendance = Attendance::with('recordedBy')
            ->where('student_id', $student->id)
            ->latest('date')
            ->limit(10)
            ->get();

        $bySubject = AssessmentGrade::with(['assessment.subject'])
            ->where('student_id', $student->id)
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
        $student->update($request->validated());

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

        $student->delete();

        log_audit('delete', $student);

        return to_route('students.index')->with('success', 'Siswa berhasil dihapus.');
    }

    public function progress(Student $student): View
    {
        $this->authorizeView($student);

        $student->load('rombel');

        $grades = AssessmentGrade::with(['assessment.subject'])
            ->where('student_id', $student->id)
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
            ->latest('date')
            ->limit(15)
            ->get();

        $ewsLogs = EarlyWarningLog::where('student_id', $student->id)
            ->latest()
            ->get();

        $assignments = Assignment::with(['subject'])
            ->where('rombel_id', $student->rombel_id)
            ->orderByDesc('deadline_at')
            ->get()
            ->map(function (Assignment $assignment) use ($student) {
                $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('student_id', $student->id)
                    ->first();

                return [
                    'assignment' => $assignment,
                    'submitted' => ! is_null($submission),
                    'submitted_at' => $submission?->submitted_at,
                ];
            });

        return view('students.progress', compact('student', 'bySubject', 'attendance', 'recentAttendance', 'ewsLogs', 'assignments'));
    }

    public function rapor(Student $student): View
    {
        $this->authorizeView($student);

        $student->load(['rombel', 'rombel.academicYear']);

        $grades = AssessmentGrade::with(['assessment.subject'])
            ->where('student_id', $student->id)
            ->get();

        $rows = $grades->groupBy(fn ($g) => $g->assessment->subject_id)
            ->map(function ($subjectGrades) {
                $subject = $subjectGrades->first()->assessment->subject;
                $byCategory = $subjectGrades->groupBy('assessment.category');

                $aver = [];
                $final = [];

                foreach (array_keys(self::CATEGORY_WEIGHTS) as $category) {
                    $categoryGrades = $byCategory->get($category);

                    if ($categoryGrades) {
                        $score = round($categoryGrades->avg('score'), 2);
                        $weight = $categoryGrades->first()->assessment->weight_percentage ?? self::CATEGORY_WEIGHTS[$category];
                        $aver[$category] = $score;
                        $final[$category] = $score * $weight / 100;
                    }
                }

                $finalScore = array_sum($final) !== 0.0 ? array_sum($final) : null;

                return [
                    'name' => $subject->name ?? '-',
                    'aver' => $aver,
                    'final_score' => $finalScore !== null ? round($finalScore, 2) : null,
                ];
            })
            ->values();

        $attendance = $this->attendanceSummary($student);

        return view('students.rapor', compact('student', 'rows', 'attendance'));
    }

    private function authorizeView(Student $student): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin_sekolah', 'guru'])) {
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

    private function attendanceSummary(Student $student): array
    {
        $rows = Attendance::where('student_id', $student->id);

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
}
