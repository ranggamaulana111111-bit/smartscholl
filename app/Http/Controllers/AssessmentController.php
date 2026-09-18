<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessmentGradeRequest;
use App\Http\Requests\AssessmentRequest;
use App\Http\Requests\ImportGradesRequest;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Assessment::with(['subject', 'rombel'])
            ->latest();

        if ($request->has('subject_id') && $request->input('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->has('category') && $request->input('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($user->isGuru()) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->isSiswa()) {
            $student = Student::where('user_id', $user->id)->first();

            if ($student) {
                $query->where(function ($q) use ($student) {
                    $q->where('rombel_id', $student->rombel_id);
                    $q->orWhereHas('grades', fn ($gq) => $gq->where('student_id', $student->id));
                });
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        $assessments = $query->paginate(20)->withQueryString();

        return view('assessments.index', compact('assessments'));
    }

    public function create(): View
    {
        $user = auth()->user();

        if ($user->isGuru()) {
            $schedules = Schedule::where('user_id', $user->id)
                ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                ->with(['subject', 'rombel'])
                ->get();

            $subjects = $schedules->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
            $rombels = $schedules->pluck('rombel')->filter()->unique('id')->sortBy('name')->values();
        } else {
            $subjects = Subject::where('is_active', true)->orderBy('name')->get();
            $rombels = Rombel::with('academicYear')
                ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                ->orderBy('name')
                ->get();
        }

        return view('assessments.create', compact('subjects', 'rombels'));
    }

    public function store(AssessmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['academic_year_id'])) {
            $data['academic_year_id'] = AcademicYear::where('is_active', true)->value('id');
        }

        if (auth()->user()->isGuru()) {
            $data['teacher_id'] = auth()->id();
        }

        $assessment = Assessment::create($data);

        log_audit('create', $assessment);

        return to_route('assessments.show', $assessment)->with('success', 'Penilaian berhasil dibuat.');
    }

    public function storeGrades(AssessmentGradeRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessment($assessment);

        $data = $request->validated();

        $studentIds = array_flip(Student::where('rombel_id', $assessment->rombel_id)->pluck('id')->all());

        $records = [];

        DB::transaction(function () use ($data, $assessment, $studentIds, &$records) {
            foreach ($data['scores'] as $studentId => $score) {
                $studentId = (int) $studentId;

                if (! isset($studentIds[$studentId])) {
                    continue;
                }

                if (is_null($score) || $score === '') {
                    AssessmentGrade::where('student_id', $studentId)
                        ->where('assessment_id', $assessment->id)
                        ->delete();

                    continue;
                }

                $grade = AssessmentGrade::updateOrCreate(
                    ['student_id' => $studentId, 'assessment_id' => $assessment->id],
                    [
                        'score' => $score,
                        'note' => $data['notes'][$studentId] ?? null,
                    ]
                );

                $records[] = ['assessment_id' => $assessment->id, 'student_id' => $studentId];
            }
        });

        log_audit('update_grades', $assessment, null, ['count' => count($records)]);

        return back()->with('success', 'Nilai siswa berhasil disimpan.');
    }

    public function export(Assessment $assessment): Response
    {
        $this->authorizeAssessment($assessment);
        $grades = AssessmentGrade::with('student')
            ->where('assessment_id', $assessment->id)
            ->orderBy('student_id')
            ->get();

        $filename = 'nilai-'.$assessment->category.'-'.Str::slug($assessment->title).'-'.now()->format('Ymd').'.csv';

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['NISN', 'Nama', 'Nilai', 'Catatan', 'Capaian']);
        foreach ($grades as $grade) {
            fputcsv($handle, [
                $grade->student->nisn ?? '',
                $grade->student->name ?? '',
                $grade->score,
                $grade->note ?? '',
                deskripsiCapaian((float) $grade->score),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        log_audit('export_grades', $assessment);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function import(ImportGradesRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessment($assessment);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Tidak dapat membaca berkas CSV.');
        }

        $header = null;
        $count = 0;
        $skipped = [];

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_map(fn ($cell) => trim((string) $cell), $row);

            if ($header === null) {
                $header = array_map(fn (string $cell) => strtolower($cell), $row);

                continue;
            }

            $data = array_combine($header, $row);

            $nisn = $data['nisn'] ?? $data['nis'] ?? '';

            if ($nisn === '') {
                continue;
            }

            $score = (float) ($data['nilai'] ?? $data['score'] ?? 0);

            if ($score <= 0 || $score > $assessment->max_score) {
                $skipped[] = $nisn;

                continue;
            }

            $student = Student::where('nisn', $nisn)->first();

            if (! $student) {
                $skipped[] = $nisn;

                continue;
            }

            AssessmentGrade::updateOrCreate(
                ['assessment_id' => $assessment->id, 'student_id' => $student->id],
                ['score' => $score, 'note' => $data['catatan'] ?? $data['note'] ?? null]
            );

            $count++;
        }

        fclose($handle);

        log_audit('import_grades', $assessment, null, ['count' => $count, 'skipped' => $skipped]);

        $message = "$count baris nilai berhasil diimpor.";

        if ($skipped !== []) {
            $message .= ' Bagian dilewati: '.implode(', ', array_slice($skipped, 0, 5)).(count($skipped) > 5 ? ', …' : '');

            return to_route('assessments.show', $assessment)->with('error', $message);
        }

        return to_route('assessments.show', $assessment)->with('success', $message);
    }

    public function template(Assessment $assessment): Response
    {
        $this->authorizeAssessment($assessment);

        $students = Student::with('rombel')
            ->where('rombel_id', $assessment->rombel_id)
            ->orderBy('name')
            ->get();

        $filename = 'template-nilai-'.Str::slug($assessment->title).'.csv';

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['NISN', 'Nama', 'Nilai', 'Catatan']);
        foreach ($students as $student) {
            fputcsv($handle, [$student->nisn, $student->name, '', '']);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function show(Assessment $assessment): View
    {
        $this->authorizeAssessment($assessment);

        $assessment->load(['subject', 'rombel']);

        $students = Student::with('rombel')
            ->where('rombel_id', $assessment->rombel_id)
            ->orderBy('name')
            ->get();

        $grades = AssessmentGrade::with('student')
            ->where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('student_id');

        $user = auth()->user();

        if ($user->isSiswa()) {
            $student = Student::where('user_id', $user->id)->first();

            $ownsGrade = $student ? $grades->has($student->id) : false;

            if (! $student || (! $ownsGrade && $assessment->rombel_id !== $student->rombel_id)) {
                abort(403, 'Anda hanya dapat melihat penilaian pada rombel Anda sendiri.');
            }

            $grades = $grades->filter(fn (AssessmentGrade $grade) => $grade->student_id === $student->id);
            $students = $students->filter(fn (Student $row) => $row->id === $student->id);
        }

        $stats = [
            'count' => $grades->count(),
            'avg' => $grades->count() > 0 ? round($grades->avg('score'), 2) : 0,
            'min' => $grades->min('score'),
            'max' => $grades->max('score'),
        ];

        $canGrade = $user->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']);

        return view('assessments.show', compact('assessment', 'students', 'grades', 'stats', 'canGrade'));
    }

    public function edit(Assessment $assessment): View
    {
        $this->authorizeAssessment($assessment);

        return view('assessments.edit', compact('assessment'));
    }

    public function update(AssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessment($assessment);

        $assessment->update($request->validated());

        return to_route('assessments.show', $assessment)->with('success', 'Penilaian berhasil diperbarui.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessment($assessment);

        $assessment->delete();

        return to_route('assessments.index')->with('success', 'Penilaian berhasil dihapus.');
    }

    private function authorizeAssessment(Assessment $assessment): void
    {
        $user = auth()->user();

        if ($user->isGuru() && $assessment->teacher_id !== $user->id) {
            abort(403, 'Anda hanya dapat mengelola penilaian milik Anda sendiri.');
        }
    }
}
