<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\SubmissionRequest;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $query = Assignment::with(['subject', 'rombel', 'teacher']);

        if ($user->hasRole('guru')) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->hasRole('siswa')) {
            $student = Student::where('user_id', $user->id)->first();

            if ($student) {
                $query->where('rombel_id', $student->rombel_id);
            } else {
                $query->whereRaw('0 = 1');
            }
        } elseif ($user->hasRole('orang_tua')) {
            $rombelIds = StudentParent::where('user_id', $user->id)
                ->with('student')
                ->get()
                ->pluck('student.rombel_id')
                ->filter();

            $query->whereIn('rombel_id', $rombelIds);
        }

        $assignments = $query->latest('deadline_at')->paginate(20)->withQueryString();

        $stats = [
            'open' => Assignment::where('deadline_at', '>=', now())->count(),
            'overdue' => Assignment::where('deadline_at', '<', now())->count(),
        ];

        return view('assignments.index', compact('assignments', 'stats'));
    }

    public function create(): View
    {
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $rombels = Rombel::orderBy('name')->get();

        return view('assignments.create', compact('subjects', 'rombels'));
    }

    public function store(AssignmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $attachment = $request->file('attachment');

        $assignment = DB::transaction(function () use ($data, $attachment) {
            return Assignment::create([
                'academic_year_id' => AcademicYear::where('is_active', true)->value('id'),
                'teacher_id' => auth()->id(),
                'rombel_id' => $data['rombel_id'],
                'subject_id' => $data['subject_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'deadline_at' => $data['deadline_at'],
                'attachment_path' => $attachment?->store('assignments', 'public'),
            ]);
        });

        log_audit('create', $assignment);

        return to_route('assignments.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function show(Assignment $assignment): View
    {
        $this->authorizeAccess($assignment);

        $assignment->load(['subject', 'rombel', 'teacher']);

        $user = auth()->user();
        $students = collect();
        $submissions = $assignment->submissions()->with('student')->get();

        if ($user->hasAnyRole(['super_admin', 'admin_sekolah'])) {
            $students = Student::with('rombel')->where('rombel_id', $assignment->rombel_id)->orderBy('name')->get();
        } elseif ($user->hasRole('guru')) {
            $students = Student::with('rombel')->where('rombel_id', $assignment->rombel_id)->orderBy('name')->get();
        }

        $mySubmission = null;
        if ($user->hasRole('siswa')) {
            $student = Student::where('user_id', $user->id)->first();
            $mySubmission = $student ? $assignment->submissions()->where('student_id', $student->id)->first() : null;
        }

        return view('assignments.show', compact('assignment', 'students', 'submissions', 'mySubmission'));
    }

    public function submit(SubmissionRequest $request, Assignment $assignment): RedirectResponse
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (! $student || $student->rombel_id !== $assignment->rombel_id) {
            return to_route('assignments.show', $assignment)->with('error', 'Anda tidak berhak mengumpulkan tugas ini.');
        }

        if ($assignment->is_overdue) {
            return to_route('assignments.show', $assignment)->with('error', 'Batas waktu pengumpulan telah lewat.');
        }

        $data = $request->validated();
        $attachment = $request->file('attachment');

        $submission = AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'note' => $data['note'] ?? null,
                'attachment_path' => $attachment ? $attachment->store('submissions', 'public') : null,
                'submitted_at' => now(),
            ]
        );

        log_audit('submit', $assignment, null, ['student_id' => $student->id, 'submission_id' => $submission->id]);

        return to_route('assignments.show', $assignment)->with('success', 'Tugas berhasil dikumpulkan.');
    }

    public function download(Assignment $assignment): Response
    {
        abort_unless($assignment->attachment_path && Storage::disk('public')->exists($assignment->attachment_path), 404);

        return Storage::disk('public')->download($assignment->attachment_path);
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $assignment->delete();

        log_audit('delete', $assignment);

        return to_route('assignments.index')->with('success', 'Tugas berhasil dihapus.');
    }

    private function authorizeAccess(Assignment $assignment): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin_sekolah', 'guru'])) {
            return;
        }

        if ($user->hasRole('siswa')) {
            $student = Student::where('user_id', $user->id)->first();
            abort_unless($student && $student->rombel_id === $assignment->rombel_id, 403, 'Anda tidak berhak mengakses tugas ini.');

            return;
        }

        if ($user->hasRole('orang_tua')) {
            $rombelIds = StudentParent::where('user_id', $user->id)
                ->with('student')
                ->get()
                ->pluck('student.rombel_id');

            abort_unless($rombelIds->contains($assignment->rombel_id), 403, 'Anda tidak berhak mengakses tugas ini.');

            return;
        }

        abort(403);
    }
}
