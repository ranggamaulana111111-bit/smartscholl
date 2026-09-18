<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->input('date') ?: now()->toDateString();
        $user = auth()->user();

        $scopeStudent = fn ($query) => $query->when(
            $user->hasRole('guru'),
            fn ($q) => $q->whereHas('rombel', fn ($rq) => $rq->where('homeroom_teacher_id', $user->id))
        );

        $totalStudents = $scopeStudent(Student::query())->count();

        $byStatus = Attendance::whereDate('date', $date)
            ->when(
                $user->hasRole('guru'),
                fn ($q) => $q->whereHas('student.rombel', fn ($rq) => $rq->where('homeroom_teacher_id', $user->id))
            )
            ->get()
            ->groupBy('type')
            ->map(
                fn ($rows) => [
                    'hadir' => $rows->where('status', 'hadir')->count(),
                    'sakit' => $rows->where('status', 'sakit')->count(),
                    'izin' => $rows->where('status', 'izin')->count(),
                    'alpha' => $rows->where('status', 'alpha')->count(),
                ]
            );

        $attendances = Attendance::with(['student.rombel', 'recordedBy'])
            ->whereDate('date', $date)
            ->when(
                $user->hasRole('guru'),
                fn ($q) => $q->whereHas('student.rombel', fn ($rq) => $rq->where('homeroom_teacher_id', $user->id))
            )
            ->latest('time')
            ->paginate(20)
            ->withQueryString();

        return view('attendance.index', compact('date', 'totalStudents', 'byStatus', 'attendances'));
    }

    public function scan(Request $request): View
    {
        return view('attendance.scan', [
            'mode' => $this->validMode($request->input('mode', 'gate_in')),
            'schedules' => $this->schedulesForDay(now()->toDateString()),
        ]);
    }

    public function record(Request $request): RedirectResponse|JsonResponse
    {
        $mode = $this->validMode($request->input('mode', 'gate_in'));
        $payload = trim((string) $request->input('payload'));

        if ($payload === '') {
            return $this->scanResult($request->wantsJson(), 'error', 'Payload kosong. Pindai kartu QR/RFID atau masukkan NISN.');
        }

        $scheduleId = $request->filled('schedule_id') ? (int) $request->input('schedule_id') : null;

        $outcome = $this->recordOne($payload, $mode, $scheduleId);

        return $this->scanResult($request->wantsJson(), $outcome['outcome'], $outcome['message']);
    }

    public function recordBulk(Request $request): RedirectResponse|JsonResponse
    {
        $mode = $this->validMode($request->input('mode', 'gate_in'));
        $raw = trim((string) $request->input('payloads'));
        $json = $request->wantsJson();
        $scheduleId = $request->filled('schedule_id') ? (int) $request->input('schedule_id') : null;

        if ($raw === '') {
            return $this->scanResult($json, 'error', 'Tidak ada kode untuk diproses.');
        }

        $success = 0;
        $info = 0;
        $errors = [];

        foreach ($this->parsePayloads($raw) as $payload) {
            $outcome = $this->recordOne($payload, $mode, $scheduleId);

            match ($outcome['outcome']) {
                'success' => $success++,
                'info' => $info++,
                default => $errors[] = $outcome['message'],
            };
        }

        $message = "Tercatat $success siswa.".(($info > 0) ? " $info sudah tercatat sebelumnya." : '').((count($errors) > 0) ? ' '.count($errors).' kode gagal diproses.' : '');

        if ($json) {
            return response()->json([
                'ok' => true,
                'message' => trim($message),
                'success' => $success,
                'info' => $info,
                'errors' => array_values(array_unique($errors)),
            ]);
        }

        return back()->with('success', trim($message));
    }

    public function qrCodes(): View
    {
        $students = Student::with('rombel')->orderBy('name')->get();

        return view('attendance.qrcodes', [
            'students' => $students->map(fn (Student $student) => [
                'student' => $student,
                'qr' => $this->qrSvg($student->qrcodePayload()),
            ]),
        ]);
    }

    public function qrCode(Student $student): View
    {
        return view('attendance.qrcode', [
            'student' => $student,
            'qr' => $this->qrSvg($student->qrcodePayload()),
        ]);
    }

    public function manual(): View
    {
        $user = auth()->user();

        $students = Student::with('rombel')
            ->when($user->hasRole('guru'), function ($query) use ($user): void {
                $query->whereHas('rombel', fn ($q) => $q->where('homeroom_teacher_id', $user->id));
            })
            ->orderBy('name')
            ->get();

        $schedules = Schedule::with(['subject', 'rombel', 'teacher'])
            ->when($user->hasRole('guru'), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('attendance.manual', compact('students', 'schedules'));
    }

    public function storeManual(ManualAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $student = Student::findOrFail($data['student_id']);

        $this->authorizeManualFor($student);

        $scheduleId = $request->filled('schedule_id') ? (int) $request->input('schedule_id') : null;
        $guard = ['schedule' => null];

        if ($data['type'] === 'lesson') {
            $guard = $this->guardLessonSchedule($scheduleId, $student, $data['date']);

            if ($guard['error'] !== null) {
                return back()->with('error', $guard['error'])->withInput();
            }
        }

        $existing = Attendance::where('student_id', $data['student_id'])
            ->where('type', $data['type'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            DB::transaction(function () use ($existing, $data, $guard): void {
                $existing->update([
                    'status' => $data['status'],
                    'time' => now()->format('H:i:s'),
                    'source' => 'manual',
                    'note' => $data['note'] ?? null,
                    'schedule_id' => $data['type'] === 'lesson' ? ($guard['schedule']->id ?? null) : $existing->schedule_id,
                ]);
            });

            log_audit('update', $existing);

            return to_route('attendance.manual')->with('success', 'Data absensi diperbarui.');
        }

        $record = Attendance::create([
            'student_id' => $data['student_id'],
            'academic_year_id' => AcademicYear::where('is_active', true)->value('id'),
            'schedule_id' => $data['type'] === 'lesson' ? ($guard['schedule']->id ?? null) : null,
            'recorded_by' => auth()->id(),
            'type' => $data['type'],
            'status' => $data['status'],
            'date' => $data['date'],
            'time' => now()->format('H:i:s'),
            'source' => 'manual',
            'note' => $data['note'] ?? null,
        ]);

        log_audit('create', $record);

        return to_route('attendance.manual')->with('success', 'Data absensi tersimpan.');
    }

    private function validMode(string $mode): string
    {
        return in_array($mode, ['gate_in', 'gate_out', 'lesson'], true) ? $mode : 'gate_in';
    }

    private function recordOne(string $payload, string $mode, ?int $scheduleId = null): array
    {
        $student = $this->resolveStudent($payload);

        if (! $student) {
            return ['outcome' => 'error', 'message' => "Kode \"$payload\" tidak dikenali di sekolah ini."];
        }

        $schedule = null;

        if ($mode === 'lesson') {
            $guard = $this->guardLessonSchedule($scheduleId, $student, now()->toDateString());

            if ($guard['error'] !== null) {
                return ['outcome' => 'error', 'message' => $guard['error']];
            }

            $schedule = $guard['schedule'];
        }

        $existing = Attendance::where('student_id', $student->id)
            ->where('type', $mode)
            ->whereDate('date', now())
            ->first();

        if ($existing) {
            return [
                'outcome' => 'info',
                'message' => $student->name.' sudah tercatat '.$this->modeLabel($mode).' pukul '.$existing->time,
            ];
        }

        $note = null;

        if ($mode === 'gate_out') {
            $hasGateIn = Attendance::where('student_id', $student->id)
                ->where('type', 'gate_in')
                ->whereDate('date', now())
                ->exists();

            if (! $hasGateIn) {
                $note = 'Pulang tanpa catatan masuk hari ini';
            }
        }

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'academic_year_id' => AcademicYear::where('is_active', true)->value('id'),
            'schedule_id' => $schedule?->id,
            'recorded_by' => auth()->id(),
            'type' => $mode,
            'status' => 'hadir',
            'date' => now()->toDateString(),
            'time' => now()->format('H:i:s'),
            'source' => 'scan',
            'note' => $note,
        ]);

        return [
            'outcome' => 'success',
            'message' => $student->name.' (NISN '.$student->nisn.') tercatat '.$this->modeLabel($mode).' pukul '.$attendance->time,
        ];
    }

    private function parsePayloads(string $raw): array
    {
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $payloads = array_map(fn ($p) => trim((string) $p), $decoded);
        } else {
            $payloads = preg_split('/[\r\n,]+/', $raw) ?: [];
        }

        return array_values(array_unique(array_filter($payloads, fn ($p) => $p !== '')));
    }

    private function scanResult(bool $json, string $outcome, string $message): RedirectResponse|JsonResponse
    {
        if ($json) {
            if ($outcome === 'error') {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return response()->json(['ok' => true, 'info' => $outcome === 'info', 'message' => $message]);
        }

        return back()->with($outcome, $message);
    }

    private function authorizeManualFor(Student $student): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin_sekolah'])) {
            return;
        }

        abort_unless(
            $user->hasRole('guru') && $student->rombel?->homeroom_teacher_id === $user->id,
            403,
            'Anda hanya dapat mengisi absen siswa di rombel binaan Anda.'
        );
    }

    private function guardLessonSchedule(?int $scheduleId, Student $student, string $date): array
    {
        if (is_null($scheduleId)) {
            return ['schedule' => null, 'error' => 'Untuk hadir pelajaran, wajib pilih jadwal pelajaran.'];
        }

        $schedule = Schedule::with('rombel')->find($scheduleId);

        if (! $schedule) {
            return ['schedule' => null, 'error' => 'Jadwal pelajaran tidak ditemukan.'];
        }

        $user = auth()->user();

        if ($user->hasRole('guru') && $schedule->user_id !== $user->id) {
            return ['schedule' => null, 'error' => 'Anda hanya dapat mengisi hadir pelajaran untuk jadwal Anda sendiri.'];
        }

        if ($student->rombel_id !== $schedule->rombel_id) {
            return ['schedule' => null, 'error' => 'Siswa tidak terdaftar di rombel jadwal pilihan.'];
        }

        if ((int) $schedule->day_of_week !== (int) Carbon::parse($date)->isoWeekday()) {
            return ['schedule' => null, 'error' => 'Jadwal tidak berlangsung pada tanggal '.Carbon::parse($date)->translatedFormat('D, d M Y').'.'];
        }

        return ['schedule' => $schedule, 'error' => null];
    }

    private function schedulesForDay(string $date): Collection
    {
        $user = auth()->user();

        return Schedule::with(['subject', 'rombel', 'teacher'])
            ->where('day_of_week', (int) Carbon::parse($date)->isoWeekday())
            ->when($user->hasRole('guru'), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('start_time')
            ->get();
    }

    private function resolveStudent(string $payload): ?Student
    {
        if (preg_match('/^SS:(\d{10})$/', $payload, $matches)) {
            return Student::where('nisn', $matches[1])->first();
        }

        if (preg_match('/^SS:([A-Za-z0-9]{8,64})$/', $payload, $matches)) {
            return Student::where('qr_token', $matches[1])->first();
        }

        if (preg_match('/^\d{10}$/', $payload)) {
            return Student::where('nisn', $payload)->first();
        }

        if (preg_match('/^[A-Za-z0-9]{4,50}$/', $payload)) {
            return Student::where('rfid_uid', strtoupper($payload))->first();
        }

        return null;
    }

    private function modeLabel(string $mode): string
    {
        return match ($mode) {
            'gate_in' => 'Masuk Gerbang',
            'gate_out' => 'Pulang Gerbang',
            'lesson' => 'Hadir Pelajaran',
        };
    }

    private function qrSvg(string $data): string
    {
        return (new Builder(
            writer: new SvgWriter,
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 140,
            margin: 4,
        ))->build()->getString();
    }
}
