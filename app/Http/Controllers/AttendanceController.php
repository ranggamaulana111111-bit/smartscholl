<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualAttendanceRequest;
use App\Http\Requests\MarkLessonStudentRequest;
use App\Http\Requests\MarkRemainingAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceScanEvent;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\AttendanceRecap;
use App\Services\AttendanceRecorder;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceRecorder $recorder,
        private readonly AttendanceRecap $recap,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->input('date') ?: now()->toDateString();
        $user = auth()->user();

        $rombels = $this->recap->visibleRombels($user);
        $rombelIds = $rombels->pluck('id');

        $totalStudents = Student::whereIn('rombel_id', $rombelIds)->count();

        $attendances = Attendance::with(['student.rombel', 'recordedBy'])
            ->whereDate('date', $date)
            ->whereIn('student_id', fn ($query) => $this->studentIdsQuery($rombelIds, $query))
            ->latest('time')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('attendance.index', [
            'date' => $date,
            'totalStudents' => $totalStudents,
            'attendances' => $attendances,
            'summary' => $this->recap->daily($date, null, $user)['totals'],
            'gate' => $this->gateCounts($date, $rombelIds),
            'rombels' => $rombels,
            'statusLabels' => Attendance::STATUS_LABELS,
            'typeLabels' => Attendance::TYPE_LABELS,
        ]);
    }

    /**
     * Rekap harian per rombel plus daftar siswa yang belum punya catatan.
     */
    public function recap(Request $request): View
    {
        $date = $this->validatedDate($request);
        $rombelId = $request->integer('rombel') ?: null;
        $rekap = $this->recap->daily($date, $rombelId, auth()->user());

        return view('attendance.recap', [
            'date' => $date,
            'rombelId' => $rombelId,
            'rekap' => $rekap,
            'rombels' => $this->recap->visibleRombels(auth()->user()),
        ]);
    }

    /**
     * Unduh rekap yang sama seperti halaman recap, dalam bentuk CSV.
     */
    public function exportRecap(Request $request): Response
    {
        $date = $this->validatedDate($request);
        $rombelId = $request->integer('rombel') ?: null;

        $csv = $this->toCsv($this->recap->csvRows($date, $rombelId, auth()->user()));

        log_audit('export', null, null, [
            'export' => 'attendance_recap',
            'date' => $date,
            'rombel_id' => $rombelId,
        ], entityType: 'AttendanceRecap');

        return response((string) $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekap-absensi-'.$date.'.csv"',
        ]);
    }

    /**
     * Jejak setiap kode yang dipindai, termasuk yang ditolak.
     *
     * Halaman ini biasanya dibuka setelah ada kecurigaan, jadi bentuk
     * pertanyaannya "kartu mana yang dipakai di luar kelasnya" dan
     * "kapanbuketiap kartu dipakai", bukan nama lengkap sebagai kata kunci.
     */
    public function scanEvents(Request $request): View
    {
        $date = $this->validatedDate($request);

        $outcome = $request->string('outcome')->toString();
        $type = $request->string('type')->toString();
        $studentId = $request->integer('student') ?: null;

        abort_unless(
            $outcome === '' || in_array($outcome, AttendanceScanEvent::OUTCOMES, true),
            422,
            'Jenis hasil pemindaian tidak dikenal.'
        );

        abort_unless(
            $type === '' || in_array($type, Attendance::TYPES, true),
            422,
            'Jenis absensi tidak dikenal.'
        );

        $events = AttendanceScanEvent::with(['student.rombel', 'schedule.subject', 'recordedBy'])
            ->when($outcome, fn ($query) => $query->where('outcome', $outcome))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($studentId, fn ($query) => $query->where('student_id', $studentId))
            ->whereBetween('created_at', [
                $date.' 00:00:00',
                $date.' 23:59:59',
            ])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $counts = AttendanceScanEvent::query()
            ->whereBetween('created_at', [$date.' 00:00:00', $date.' 23:59:59'])
            ->selectRaw('outcome, count(*) as total')
            ->groupBy('outcome')
            ->pluck('total', 'outcome');

        return view('attendance.scan-events', [
            'date' => $date,
            'dateLabel' => Carbon::parse($date)->translatedFormat('d M Y'),
            'outcome' => $outcome,
            'type' => $type,
            'studentId' => $studentId,
            'events' => $events,
            'outcomeMeta' => AttendanceScanEvent::OUTCOME_META,
            'typeLabels' => Attendance::TYPE_LABELS,
            'counts' => collect(AttendanceScanEvent::OUTCOME_META)
                ->mapWithKeys(fn (array $meta, string $key) => [$key => (int) ($counts[$key] ?? 0)])
                ->all(),
            'total' => (int) $counts->sum(),
        ]);
    }

    public function scan(Request $request): View
    {
        return view('attendance.scan', [
            'mode' => $this->validType($request->input('mode', 'gate_in')),
            'schedules' => $this->schedulesForDay(now()->toDateString()),
        ]);
    }

    public function record(Request $request): RedirectResponse|JsonResponse
    {
        $result = $this->recorder->recordScan(
            payload: (string) $request->input('payload', ''),
            type: $this->validType($request->input('mode', 'gate_in')),
            scheduleId: $request->filled('schedule_id') ? (int) $request->input('schedule_id') : null,
        );

        return $this->scanResponse($request->wantsJson(), $result);
    }

    public function recordBulk(Request $request): RedirectResponse|JsonResponse
    {
        $type = $this->validType($request->input('mode', 'gate_in'));
        $raw = trim((string) $request->input('payloads', ''));
        $scheduleId = $request->filled('schedule_id') ? (int) $request->input('schedule_id') : null;
        $json = $request->wantsJson();

        if ($raw === '') {
            return $this->scanResponse($json, ['ok' => false, 'outcome' => 'error', 'message' => 'Tidak ada kode untuk diproses.'], true);
        }

        $success = 0;
        $duplicate = 0;
        $errors = [];

        foreach ($this->parsePayloads($raw) as $payload) {
            $result = $this->recorder->recordScan($payload, $type, $scheduleId);

            match ($result['outcome']) {
                'success' => $success++,
                'duplicate' => $duplicate++,
                default => $result['ok'] ? $duplicate++ : $errors[] = $result['message'],
            };
        }

        $message = trim(sprintf(
            'Tercatat %d siswa.%s%s',
            $success,
            $duplicate > 0 ? ' '.$duplicate.' sudah tercatat sebelumnya.' : '',
            $errors !== [] ? ' '.count($errors).' kode gagal diproses.' : '',
        ));

        if ($json) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'success' => $success,
                'duplicate' => $duplicate,
                'errors' => array_values(array_unique($errors)),
            ]);
        }

        return back()->with('success', $message);
    }

    public function qrCodes(Request $request): View
    {
        $rombels = $this->recap->visibleRombels(auth()->user());

        $selected = $request->input('rombel');
        $selected = $rombels->contains('id', (int) $selected) ? (int) $selected : null;

        $students = Student::with('rombel')
            ->whereIn('rombel_id', $rombels->pluck('id'))
            ->when($selected, fn ($query) => $query->where('rombel_id', $selected))
            ->orderBy('name')
            ->get();

        return view('attendance.qrcodes', [
            'rombels' => $rombels,
            'selectedRombel' => $selected,
            'students' => $students->map(fn (Student $student) => [
                'student' => $student,
                'qr' => $this->qrSvg($student->qrcodePayload()),
            ]),
        ]);
    }

    public function qrCode(Student $student): View
    {
        abort_if(
            ! $this->recap->visibleRombels(auth()->user())->contains('id', $student->rombel_id),
            403,
            'Kartu QR siswa ini di luar kelas yang Anda ampu.'
        );

        return view('attendance.qrcode', [
            'student' => $student,
            'qr' => $this->qrSvg($student->qrcodePayload()),
        ]);
    }

    public function scanLesson(Schedule $schedule): View
    {
        $this->authorizeSchedule($schedule);

        $date = now()->toDateString();
        $roster = $this->lessonRoster($schedule, $date);

        return view('attendance.scan-lesson', [
            'schedule' => $schedule,
            'date' => $date,
            'roster' => $roster,
            'summary' => $this->rosterSummary($roster),
            'window' => $this->recorder->scheduleWindow($schedule),
            'statuses' => Attendance::STATUS_LABELS,
        ]);
    }

    public function scanLessonStatus(Schedule $schedule): JsonResponse
    {
        $this->authorizeSchedule($schedule);

        $roster = $this->lessonRoster($schedule, now()->toDateString());

        return response()->json([
            'roster' => $roster,
            'summary' => $this->rosterSummary($roster),
        ]);
    }

    /**
     * Ubah status satu siswa dari roster langsung, tanpa pindah halaman.
     */
    public function markStudent(MarkLessonStudentRequest $request, Schedule $schedule): JsonResponse
    {
        $this->authorizeSchedule($schedule);

        $student = Student::findOrFail($request->integer('student_id'));
        $result = $this->recorder->updateLessonStatus(
            schedule: $schedule,
            student: $student,
            status: $request->string('status')->toString(),
            note: $request->input('note'),
        );

        $roster = $this->lessonRoster($schedule, now()->toDateString());

        return response()->json([
            'ok' => $result['ok'],
            'outcome' => $result['outcome'],
            'message' => $result['message'],
            'roster' => $roster,
            'summary' => $this->rosterSummary($roster),
        ], $result['ok'] ? 200 : 422);
    }

    public function markRemaining(MarkRemainingAttendanceRequest $request, Schedule $schedule): RedirectResponse
    {
        $this->authorizeSchedule($schedule);

        if (! ($window = $this->recorder->scheduleWindow($schedule))['open']) {
            return back()->with('error', $window['error']);
        }

        $result = $this->recorder->markRemaining(
            schedule: $schedule,
            status: $request->string('status')->toString(),
            note: $request->input('note'),
        );

        return back()->with(
            $result['outcome'] === 'error' ? 'error' : ($result['outcome'] === 'info' ? 'info' : 'success'),
            $result['message']
        );
    }

    public function manual(): View
    {
        $user = auth()->user();

        $schedules = Schedule::with(['subject', 'rombel', 'teacher'])
            ->when($user->isGuru(), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('attendance.manual', compact('schedules'));
    }

    public function manualMeetings(Schedule $schedule): View
    {
        $this->authorizeSchedule($schedule);

        return view('attendance.manual-meetings', [
            'schedule' => $schedule,
            'meetings' => $this->buildMeetings($schedule),
        ]);
    }

    public function manualSchedule(Schedule $schedule, Request $request): View
    {
        $this->authorizeSchedule($schedule);

        $date = $request->input('date') ?: now()->toDateString();

        $students = Student::with('rombel')
            ->where('rombel_id', $schedule->rombel_id)
            ->orderBy('name')
            ->get();

        $monthly = $this->statusCountsByStudent(
            $schedule,
            now()->startOfMonth()->toDateString(),
            $students
        );

        $semesterStart = $schedule->academicYear?->start_date
            ? Carbon::parse($schedule->academicYear->start_date)->toDateString()
            : now()->startOfYear()->toDateString();

        $semester = $this->statusCountsByStudent($schedule, $semesterStart, $students);

        return view('attendance.manual-schedule', [
            'schedule' => $schedule,
            'students' => $students,
            'monthly' => $monthly,
            'semester' => $semester,
            'date' => $date,
            'pertemuan' => $this->meetingNumberFor($schedule, $date),
        ]);
    }

    public function storeManual(ManualAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $student = Student::findOrFail($data['student_id']);

        $this->authorizeManualFor($student);

        $scheduleId = $request->filled('schedule_id') ? (int) $request->input('schedule_id') : null;
        $schedule = null;

        if ($data['type'] === 'lesson') {
            $guard = $this->recorder->guardLessonSchedule($scheduleId, $student, $data['date']);

            if ($guard['error'] !== null) {
                return back()->with('error', $guard['error'])->withInput();
            }

            $schedule = $guard['schedule'];
        }

        $result = $this->recorder->recordManual(
            student: $student,
            type: $data['type'],
            status: $data['status'],
            date: $data['date'],
            schedule: $schedule,
            note: $data['note'] ?? null,
        );

        return redirect($this->manualReturnTarget($schedule, $data['date']))
            ->with($result['outcome'] === 'error' ? 'error' : 'success', $result['message']);
    }

    /**
     * Jumlah siswa yang tercatat lewat gerbang, dipisah masuk dan pulang.
     *
     * Dihitung dari jumlah siswa berbeda per jenis, bukan jumlah baris, supaya
     * kartu "masuk" tidak lebih besar dari jumlah siswa yang ada. Scan ulang
     * kartu yang sama tidak menambah angka.
     *
     * @param  Collection<int, int>  $rombelIds
 * @return array{gate_in:int, gate_out:int, gate_out_without_gate_in:int}
     */
    private function gateCounts(string $date, Collection $rombelIds): array
    {
        $rows = Attendance::query()
            ->whereDate('date', $date)
            ->whereIn('type', ['gate_in', 'gate_out'])
            ->whereIn('student_id', fn ($query) => $this->studentIdsQuery($rombelIds, $query))
            ->selectRaw('type, count(distinct student_id) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $gateOutWithoutIn = Attendance::query()
            ->whereDate('date', $date)
            ->where('type', 'gate_out')
            ->whereNotIn('student_id', fn ($query) => $query
                ->select('student_id')
                ->from('attendances')
                ->whereDate('date', $date)
                ->where('type', 'gate_in')
                ->whereIn('student_id', fn ($inner) => $this->studentIdsQuery($rombelIds, $inner)))
            ->whereIn('student_id', fn ($query) => $this->studentIdsQuery($rombelIds, $query))
            ->distinct()
            ->count('student_id');

        return [
            'gate_in' => (int) ($rows['gate_in'] ?? 0),
            'gate_out' => (int) ($rows['gate_out'] ?? 0),
            'gate_out_without_gate_in' => $gateOutWithoutIn,
        ];
    }

    /**
     * @return array<int, array{hadir:int, sakit:int, izin:int, alpha:int}>
     */
    private function statusCountsByStudent(Schedule $schedule, string $since, Collection $students): array
    {
        $counts = Attendance::query()
            ->where('schedule_id', $schedule->id)
            ->where('type', 'lesson')
            ->whereDate('date', '>=', $since)
            ->selectRaw('student_id, status, count(*) as total')
            ->groupBy('student_id', 'status')
            ->get();

        $empty = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];

        return $students->mapWithKeys(function (Student $student) use ($counts, $empty): array {
            $row = $empty;

            foreach ($counts->where('student_id', $student->id) as $line) {
                if (isset($row[$line->status])) {
                    $row[$line->status] = (int) $line->total;
                }
            }

            return [$student->id => $row];
        })->all();
    }

    private function validatedDate(Request $request): string
    {
        $date = (string) $request->input('date', now()->toDateString());

        return Carbon::hasFormat($date, 'Y-m-d') ? $date : now()->toDateString();
    }

    private function validType(string $type): string
    {
        return in_array($type, Attendance::TYPES, true) ? $type : 'gate_in';
    }

    /**
     * @param  array{ok:bool, outcome:string, message:string, attendance:null}  $result
     */
    private function scanResponse(bool $json, array $result, bool $emptyBulk = false): RedirectResponse|JsonResponse
    {
        if ($json) {
            return response()->json(
                ['ok' => $result['ok'], 'info' => $result['ok'] && $result['outcome'] !== 'success', 'message' => $result['message']],
                $result['ok'] ? 200 : 422,
            );
        }

        $key = match (true) {
            $emptyBulk => 'error',
            ! $result['ok'] => 'error',
            $result['outcome'] === 'success' => 'success',
            default => 'info',
        };

        return back()->with($key, $result['message']);
    }

    /**
     * @return array<int, string>
     */
    private function parsePayloads(string $raw): array
    {
        $decoded = json_decode($raw, true);

        $payloads = is_array($decoded)
            ? array_map(fn ($item) => trim((string) $item), $decoded)
            : (preg_split('/[\r\n,]+/', $raw) ?: []);

        return array_values(array_unique(array_filter($payloads, fn (string $p) => $p !== '')));
    }

    private function authorizeManualFor(Student $student): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin_sekolah'])) {
            return;
        }

        abort_unless(
            $user->isGuru()
                && $user->teacher?->id
                && (int) $student->rombel?->homeroom_teacher_id === $user->teacher->id,
            403,
            'Anda hanya dapat mengisi absen siswa di rombel binaan Anda.'
        );
    }

    /**
     * Halaman kembali setelah guru mengubah status di roster manual.
     * Absensi pelajaran dikembalikan ke kelas yang sedang diisi pada tanggal
     * yang sama, supaya guru tidak kehilangan tempat.
     */
    private function manualReturnTarget(?Schedule $schedule, string $date): string
    {
        return $schedule
            ? route('attendance.manualSchedule', [$schedule->id, 'date' => $date])
            : route('attendance.manual');
    }

    private function authorizeSchedule(Schedule $schedule): void
    {
        $user = auth()->user();

        abort_if(
            $user->isGuru() && (int) $schedule->user_id !== (int) $user->id,
            403,
            'Anda hanya dapat mengisi absensi untuk jadwal Anda sendiri.'
        );
    }

    /**
     * Daftar siswa rombel beserta status absensi pelajaran pada satu tanggal.
     * Satu sumber untuk render awal halaman scan dan endpoint polling, supaya
     * keduanya tidak pernah menampilkan roster yang berbeda.
     *
     * @return array<int, array{id:int, name:string, nisn:string, status:?string, time:?string, source:?string, late_minutes:?int}>
     */
    private function lessonRoster(Schedule $schedule, string $date): array
    {
        $recorded = Attendance::where('schedule_id', $schedule->id)
            ->where('type', 'lesson')
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        return Student::with('rombel')
            ->where('rombel_id', $schedule->rombel_id)
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) use ($recorded): array {
                $attendance = $recorded->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'status' => $attendance?->status,
                    'time' => $attendance?->time,
                    'source' => $attendance?->source,
                    'late_minutes' => $attendance?->late_minutes,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array{status:?string}>  $roster
     * @return array{total:int, recorded:int, remaining:int, hadir:int, sakit:int, izin:int, alpha:int, late:int}
     */
    private function rosterSummary(array $roster): array
    {
        $recorded = array_filter($roster, fn (array $row) => $row['status'] !== null);

        return [
            'total' => count($roster),
            'recorded' => count($recorded),
            'remaining' => count($roster) - count($recorded),
            'hadir' => count(array_filter($recorded, fn (array $row) => $row['status'] === 'hadir')),
            'sakit' => count(array_filter($recorded, fn (array $row) => $row['status'] === 'sakit')),
            'izin' => count(array_filter($recorded, fn (array $row) => $row['status'] === 'izin')),
            'alpha' => count(array_filter($recorded, fn (array $row) => $row['status'] === 'alpha')),
            'late' => count(array_filter($recorded, fn (array $row) => ($row['late_minutes'] ?? 0) > 0)),
        ];
    }

    /**
     * Deret pertemuan sebuah jadwal dari kalender semester tahun ajaran.
     * Setiap tanggal dalam rentang start_date-end_date yang jatuh pada
     * day_of_week jadwal dihitung sebagai satu pertemuan bernomor.
     *
     * @return array<int, array{number:int, date:Carbon, is_today:bool, filled:bool, total:int}>
     */
    private function buildMeetings(Schedule $schedule): array
    {
        [$start, $end] = $this->semesterBounds($schedule);

        $filledCounts = Attendance::where('schedule_id', $schedule->id)
            ->where('type', 'lesson')
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $meetings = [];
        $number = 1;
        $today = now()->toDateString();

        foreach ($this->meetingDates($schedule, $start, $end) as $cursor) {
            $meetings[] = [
                'number' => $number,
                'date' => $cursor,
                'is_today' => $cursor->toDateString() === $today,
                'filled' => ($filledCounts[$cursor->toDateString()] ?? 0) > 0,
                'total' => (int) ($filledCounts[$cursor->toDateString()] ?? 0),
            ];

            $number++;
        }

        return $meetings;
    }

    private function meetingNumberFor(Schedule $schedule, string $date): ?int
    {
        [$start, $end] = $this->semesterBounds($schedule);
        $target = (int) $schedule->day_of_week;

        if ((int) Carbon::parse($date)->isoWeekday() !== $target) {
            return null;
        }

        $number = 1;

        foreach ($this->meetingDates($schedule, $start, $end) as $cursor) {
            if ($cursor->toDateString() === $date) {
                return $number;
            }

            $number++;
        }

        return null;
    }

    /**
     * @return array{0:Carbon, 1:Carbon}
     */
    private function semesterBounds(Schedule $schedule): array
    {
        return [
            $schedule->academicYear?->start_date
                ? Carbon::parse($schedule->academicYear->start_date)
                : now()->copy()->startOfYear(),
            $schedule->academicYear?->end_date
                ? Carbon::parse($schedule->academicYear->end_date)
                : now()->copy()->endOfYear(),
        ];
    }

    /**
     * Tanggal-tanggal pertemuan jadwal, dihitung dari hari pertama yang cocok
     * dengan day_of_week lalu maju tujuh hari. Satu perulangan dipakai ulang
     * oleh buildMeetings dan meetingNumberFor supaya nomor pertemuan pada
     * kalender selalu sama dengan nomor yang tertulis di absensi manual.
     *
     * @return \Generator<int, Carbon>
     */
    private function meetingDates(Schedule $schedule, Carbon $start, Carbon $end): \Generator
    {
        $target = (int) $schedule->day_of_week;
        $cursor = $start->copy()->addDays((($target - (int) $start->isoWeekday() + 7) % 7));

        while ($cursor->lte($end)) {
            yield $cursor->copy();

            $cursor->addDays(7);
        }
    }

    /**
     * @return Collection<int, Schedule>
     */
    private function schedulesForDay(string $date): Collection
    {
        $user = auth()->user();

        return Schedule::with(['subject', 'rombel', 'teacher'])
            ->where('day_of_week', (int) Carbon::parse($date)->isoWeekday())
            ->when($user->isGuru(), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Subquery id siswa milik sekumpulan rombel.
     *
     * Disimpan sebagai subquery, bukan daftar id hasil `pluck`, supaya daftar
     * absensi panjang tidak memuat dua kali id siswa yang sama di memori.
     *
     * @param  Collection<int, int>|array<int, int>  $rombelIds
     */
    private function studentIdsQuery(Collection|array $rombelIds, QueryBuilder $query): void
    {
        $query->select('id')
            ->from('students')
            ->whereIn('rombel_id', $rombelIds->all());
    }

    /**
     * @param  array<int, array<int, string|int|float>>  $rows
     */
    private function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
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
