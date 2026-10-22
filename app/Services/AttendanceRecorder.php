<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceScanEvent;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat yang menulis baris `attendances`.
 *
 * Controller menentukan Policy (boleh atau tidak), service menentukan Aturan
 * (kapan boleh, jam berapa, duplikat bagaimana). Semua jalur tulis, baik scan
 * kamera, scanner USB, tombol status guru, maupun penandaan sisa kelas,
 * melewati sini supaya tidak ada satu pun yang bisa melewati penjagaan jam
 * pelajaran, perhitungan keterlambatan, atau jejak audit pemindaian.
 *
 * Bentuk kembalian seragam supaya pemanggil tidak perlu tahu jalan mana yang
 * dipakai: `['ok' => bool, 'outcome' => string, 'message' => string,
 * 'attendance' => ?Attendance]`.
 */
class AttendanceRecorder
{
    /** Nama setting yang menyimpan toleransi keterlambatan, dalam menit. */
    private const LATE_TOLERANCE_KEY = 'kehadiran.late_tolerance_minutes';

    /**
     * Catat satu hasil pindai kode siswa.
     *
     * Kode yang ditolak tetap dicatat ke `attendance_scan_events`. Tanpa itu,
     * pola penyalahgunaan yang paling jelas, yaitu satu kartu dipindai di
     * banyak kelas, justru tidak akan terlihat di mana pun.
     */
    public function recordScan(string $payload, string $type, ?int $scheduleId = null): array
    {
        $payload = trim($payload);

        if ($payload === '') {
            return $this->reject(
                AttendanceScanEvent::EMPTY_PAYLOAD,
                $type,
                $scheduleId,
                null,
                null,
                $payload,
                'Kode kosong. Pindai kartu QR atau RFID, atau masukkan NISN siswa.'
            );
        }

        $student = $this->resolveStudent($payload);

        if (! $student) {
            // Balasan untuk kode tak dikenal sengaja tidak memberi informasi
            // apa pun selain "tidak dikenali". Kalau pesan error membedakan
            // kode asing dari kode yang sudah terpakai, endpoint ini berubah
            // jadi alat untuk menebak NISN dan status kehadiran satu per satu.
            return $this->reject(
                AttendanceScanEvent::UNKNOWN_CODE,
                $type,
                $scheduleId,
                null,
                null,
                $payload,
                'Kode tidak dikenali. Pindai ulang kartu, atau minta admin sekolah memeriksa.'
            );
        }

        $date = now()->toDateString();
        $schedule = null;

        if ($type === 'lesson') {
            $guard = $this->guardLessonSchedule($scheduleId, $student, $date, enforceWindow: true);

            if ($guard['error'] !== null) {
                return $this->reject($guard['outcome'], $type, $scheduleId, $student, null, $payload, $guard['error']);
            }

            $schedule = $guard['schedule'];
        }

        if ($type !== 'lesson' && ($gateError = $this->guardGateScan($student)) !== null) {
            return $this->reject(
                AttendanceScanEvent::STUDENT_NOT_IN_CLASS,
                $type,
                null,
                $student,
                null,
                $payload,
                $gateError
            );
        }

        if ($existing = $this->existingFor($student->id, $type, $schedule?->id, $date)) {
            $this->logScanEvent(
                AttendanceScanEvent::DUPLICATE,
                $type,
                $schedule?->id,
                $student,
                $existing,
                $payload
            );

            return $this->note($student->name.' sudah tercatat '.Attendance::TYPE_LABELS[$type].' pukul '.$existing->time.'.');
        }

        $attendance = $this->insert(
            student: $student,
            type: $type,
            status: (string) setting('kehadiran.scan_default_status', Attendance::HADIR),
            date: $date,
            time: now()->format('H:i:s'),
            schedule: $schedule,
            source: 'scan',
            note: $this->gateOutNote($student, $date, $type)
        );

        if (! $attendance) {
            // Balapan antara dua scanner untuk kode yang sama. Barisnya milik
            // scan lain dan tetap sah, jadi pemindai ini tetap diberi kabar
            // berhasil, bukan error.
            if ($existing = $this->existingFor($student->id, $type, $schedule?->id, $date)) {
                return $this->note($student->name.' sudah tercatat '.Attendance::TYPE_LABELS[$type].' pukul '.$existing->time.'.');
            }

            return $this->fail('Pencatatan gagal disimpan. Pindai ulang atau periksa koneksi.');
        }

        $this->logScanEvent(
            AttendanceScanEvent::RECORDED,
            $type,
            $schedule?->id,
            $student,
            $attendance,
            $payload
        );

        log_audit('create', $attendance);

        $late = $attendance->late_minutes ? ', terlambat '.$attendance->late_minutes.' menit' : '';

        return $this->succeed(
            $student->name.' (NISN '.$student->nisn.') tercatat '
            .Attendance::TYPE_LABELS[$type].' pukul '.$attendance->time.$late.'.',
            $attendance
        );
    }

    /**
     * Catat atau perbarui absensi manual untuk satu siswa pada satu tanggal.
     *
     * Jalur ini boleh menulis tanggal lampau, karena dipakai untuk koreksi
     * dan pengisian susulan. Penjagaan jam pelajaran hanya berlaku untuk scan
     * langsung.
     *
     * @param  'manual'|'correction'  $source
     * @return array{ok:bool, outcome:string, message:string, attendance:?Attendance}
     */
    public function recordManual(
        Student $student,
        string $type,
        string $status,
        string $date,
        ?Schedule $schedule = null,
        ?string $note = null,
        string $source = 'manual',
    ): array {
        if ($type === 'lesson' && ! $schedule) {
            return $this->fail('Untuk hadir pelajaran, wajib pilih jadwal pelajaran.');
        }

        $time = now()->format('H:i:s');
        $existing = $this->existingFor($student->id, $type, $schedule?->id, $date);

        if ($existing) {
            $tracked = ['status', 'time', 'late_minutes', 'source', 'note'];
            $old = $existing->only($tracked);

            $existing->update([
                'status' => $status,
                'time' => $time,
                'late_minutes' => $this->lateMinutes($schedule, $time),
                'source' => $source,
                'note' => $note,
            ]);

            log_audit('update', $existing, $old, $existing->only($tracked));

            return $this->succeed(
                'Absensi '.$student->name.' diperbarui menjadi '.Attendance::STATUS_LABELS[$status].'.',
                $existing
            );
        }

        $attendance = $this->insert(
            student: $student,
            type: $type,
            status: $status,
            date: $date,
            time: $time,
            schedule: $schedule,
            source: $source,
            note: $note
        );

        if (! $attendance) {
            if ($existing = $this->existingFor($student->id, $type, $schedule?->id, $date)) {
                return $this->succeed(
                    'Absensi '.$student->name.' sudah tercatat pukul '.$existing->time.'.',
                    $existing
                );
            }

            return $this->fail('Absensi gagal disimpan. Muat ulang halaman lalu coba lagi.');
        }

        log_audit('create', $attendance);

        return $this->succeed('Absensi '.$student->name.' tersimpan.', $attendance);
    }

    /**
     * Ubah status satu siswa dari tombol di roster langsung.
     *
     * Harus idempoten:=status yang sama tidak mengubah apa pun, supaya
     * polling dan ketukan ganda tidak menumpuk audit.
     *
     * @return array{ok:bool, outcome:string, message:string, attendance:?Attendance}
     */
    public function updateLessonStatus(
        Schedule $schedule,
        Student $student,
        string $status,
        ?string $note = null,
    ): array {
        $date = now()->toDateString();
        $existing = $this->existingFor($student->id, 'lesson', $schedule->id, $date);

        if ($existing && $existing->status === $status) {
            return [
                'ok' => true,
                'outcome' => 'unchanged',
                'message' => $student->name.' sudah berstatus '.Attendance::STATUS_LABELS[$status].'.',
                'attendance' => $existing,
            ];
        }

        if ($existing) {
            $old = $existing->only(['status', 'note']);

            $existing->update([
                'status' => $status,
                'note' => $note,
                // Sumber tidak ditimpa: catatan hasil scan harus tetap bisa
                // dibedakan dari catatan yang diketik guru saat rekap.
                'source' => $existing->source,
            ]);

            log_audit('update', $existing, $old, $existing->only(['status', 'note']));

            return $this->succeed(
                $student->name.' diubah menjadi '.Attendance::STATUS_LABELS[$status].'.',
                $existing
            );
        }

        $attendance = $this->insert(
            student: $student,
            type: 'lesson',
            status: $status,
            date: $date,
            time: now()->format('H:i:s'),
            schedule: $schedule,
            source: 'manual',
            note: $note
        );

        if (! $attendance) {
            return $this->fail('Status gagal disimpan. Muat ulang daftar siswa lalu coba lagi.');
        }

        log_audit('create', $attendance);

        return $this->succeed(
            $student->name.' ditandai '.Attendance::STATUS_LABELS[$status].'.',
            $attendance
        );
    }

    /**
     * Tandai semua siswa rombel yang belum punya catatan pada pertemuan ini.
     *
     * Satu transaksi, satu entri audit. Tidak perlu satu audit per siswa karena
     * yang dicatat bukan koreksi terhadap satu baris, melainkan satu keputusan
     * guru: sisa kelas ini saya tangani dengan status ini.
     *
     * @return array{ok:bool, outcome:string, message:string, attendance:null, count?:int}
     */
    public function markRemaining(Schedule $schedule, string $status, ?string $note = null): array
    {
        $date = now()->toDateString();

        $pending = Student::query()
            ->where('rombel_id', $schedule->rombel_id)
            ->whereNotIn('id', fn ($query) => $this->recordedStudentIdsQuery($schedule->id, $date, $query))
            ->orderBy('name')
            ->pluck('id');

        if ($pending->isEmpty()) {
            return $this->note('Seluruh siswa rombel ini sudah punya catatan pada pertemuan hari ini.');
        }

        $now = now()->format('H:i:s');
        $late = $this->lateMinutes($schedule, $now);

        $written = DB::transaction(function () use ($pending, $schedule, $status, $note, $date, $now, $late): int {
            $count = 0;

            foreach ($pending as $studentId) {
                try {
                    Attendance::create([
                        'tenant_id' => $schedule->tenant_id,
                        'academic_year_id' => $schedule->academic_year_id,
                        'schedule_id' => $schedule->id,
                        'student_id' => $studentId,
                        'recorded_by' => auth()->id(),
                        'type' => 'lesson',
                        'status' => $status,
                        'date' => $date,
                        'time' => $now,
                        'late_minutes' => $late,
                        'source' => 'manual',
                        'note' => $note,
                    ]);

                    $count++;
                } catch (QueryException $exception) {
                    // Scan yang kebetulan berjalan bersamaan sudah menulis
                    // barisnya. Baris itu tetap berlaku, jadi dilewati.
                    if (! $this->isUniqueViolation($exception)) {
                        throw $exception;
                    }
                }
            }

            return $count;
        });

        if ($written === 0) {
            return $this->note('Seluruh siswa rombel ini sudah punya catatan pada pertemuan hari ini.');
        }

        log_audit(
            'mark_attendance_remaining',
            $schedule,
            ['pending' => $pending->count()],
            ['written' => $written, 'status' => $status, 'date' => $date, 'note' => $note]
        );

        return [
            'ok' => true,
            'outcome' => 'created',
            'count' => $written,
            'attendance' => null,
            'message' => $written.' siswa ditandai '.Attendance::STATUS_LABELS[$status]
                .' untuk pertemuan hari ini.',
        ];
    }

    /**
     * Menit keterlambatan yangreported di baris absensi.
     *
     * Nilai ini dihitung sekali, saat pencatatan, lalu disimpan. Kalau
     * dihitung ulang dari jadwal saat dibaca, mengubah toleransi lateinit di
     * pengaturan sekolah akan menulis ulang sejarah absensi tahun lalu.
     *
     * Dalam toleransi menghasilkan 0, bukan null: siswa hadir tepat waktu
     * tetap perlu angka nol supaya bisa dibedakan dari scan gerbang yang memang
     * tidak punya konsep jam mulai.
     */
    public function lateMinutes(?Schedule $schedule, string $time): ?int
    {
        if (! $schedule) {
            return null;
        }

        $tolerance = (int) setting(self::LATE_TOLERANCE_KEY, 10);
        $start = Carbon::parse($schedule->start_time);
        $arrival = Carbon::parse($time);

        $late = (int) $arrival->diffInMinutes($start, false);

        return $late > $tolerance ? $late : 0;
    }

    /**
     * Jendela pencatatan absensi pelajaran: dibuka
     * `lesson_scan_early_minutes` menit sebelum mulai, ditutup satu menit
     * setelah selesai.
     *
     * @return array{open:bool, error:?string, opens_at:Carbon, closes_at:Carbon, early_minutes:int}
     */
    public function scheduleWindow(Schedule $schedule, ?Carbon $now = null): array
    {
        $start = Carbon::parse($schedule->start_time);
        $end = Carbon::parse($schedule->end_time);
        $earlyMinutes = (int) setting('kehadiran.lesson_scan_early_minutes', 15);
        $moment = $now ?? now();

        $opensAt = $start->copy()->subMinutes($earlyMinutes);
        $closesAt = $end->copy()->addMinute();

        $open = $moment->gte($opensAt) && $moment->lte($closesAt);

        return [
            'open' => $open,
            'error' => $open
                ? null
                : 'Di luar jam pelajaran '.$start->format('H:i').'-'.$end->format('H:i')
                    .'. Jendela pencatatan dibuka '.$earlyMinutes.' menit sebelum mulai.',
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'early_minutes' => $earlyMinutes,
        ];
    }

    /**
     * Resolve kode pindai menjadi siswa.
     *
     * Empat format diterima: `SS:<token>`, `SS:<nisn>`, NISN telanjang, dan
     * UID RFID. Semua pencarian sudah dibatasi tenant oleh global scope Student.
     */
    public function resolveStudent(string $payload): ?Student
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

    /**
     * Validasi jadwal pelajaran sebelum mencatat.
     *
     * Empat hal diperiksa berurutan dari yang paling murah: jadwal ada, yang
     * memindai berhak atas jadwal itu, siswa memang anggota rombel jadwal,
     * dan akhirnya jadwalnya memang berlangsung di tanggal tersebut.
     *
     * @return array{schedule:?Schedule, error:?string, outcome:string}
     */
    public function guardLessonSchedule(
        ?int $scheduleId,
        Student $student,
        string $date,
        bool $enforceWindow = false,
    ): array {
        if (is_null($scheduleId)) {
            return [
                'schedule' => null,
                'error' => 'Untuk hadir pelajaran, wajib pilih jadwal pelajaran.',
                'outcome' => AttendanceScanEvent::EMPTY_PAYLOAD,
            ];
        }

        $schedule = Schedule::with('rombel')->find($scheduleId);

        if (! $schedule) {
            return [
                'schedule' => null,
                'error' => 'Jadwal pelajaran tidak ditemukan.',
                'outcome' => AttendanceScanEvent::UNKNOWN_CODE,
            ];
        }

        $user = auth()->user();

        if ($user?->isGuru() && (int) $schedule->user_id !== (int) $user->id) {
            return [
                'schedule' => null,
                'error' => 'Anda hanya dapat mengisi hadir pelajaran untuk jadwal Anda sendiri.',
                'outcome' => AttendanceScanEvent::STUDENT_NOT_IN_CLASS,
            ];
        }

        if ((int) $student->rombel_id !== (int) $schedule->rombel_id) {
            return [
                'schedule' => null,
                'error' => 'Siswa ini bukan anggota rombel '.($schedule->rombel?->name ?? 'jadwal pilihan').'.',
                'outcome' => AttendanceScanEvent::STUDENT_NOT_IN_CLASS,
            ];
        }

        $tanggal = Carbon::parse($date);

        if ((int) $schedule->day_of_week !== (int) $tanggal->isoWeekday()) {
            return [
                'schedule' => null,
                'error' => 'Jadwal tidak berlangsung pada tanggal '
                    .hariPendek((int) $tanggal->isoWeekday()).', '.$tanggal->format('d M Y').'.',
                'outcome' => AttendanceScanEvent::OUTSIDE_WINDOW,
            ];
        }

        if ($enforceWindow && ! ($window = $this->scheduleWindow($schedule))['open']) {
            return [
                'schedule' => null,
                'error' => $window['error'],
                'outcome' => AttendanceScanEvent::OUTSIDE_WINDOW,
            ];
        }

        return ['schedule' => $schedule, 'error' => null, 'outcome' => AttendanceScanEvent::RECORDED];
    }

    /**
     * Cegah guru piket menandai siswa dari kelas yang tidak menjadi tanggung
     * jawabnya.
     *
     * Scan gerbang memang dipakai di pintu masuk sekolah, jadi opsinya tetap
     * terbuka untuk semua siswa. Yang dibatasi adalah siapa yang boleh menekan
     * tombolnya: guru hanya untuk rombel yang ia bina atau ajar. Tanpa ini,
     * guru kelas 6 bisa menutup attendance siswa kelas 12 dari gerbang.
     * Admin sekolah dan Super Admin tetap bebas karena tugas mereka memang
     * mencakup seluruh rombel.
     */
    public function guardGateScan(Student $student): ?string
    {
        $user = auth()->user();

        if (! $user?->isGuru()) {
            return null;
        }

        $teacherId = (int) ($user->teacher?->id ?? 0);
        $rombelId = (int) $student->rombel_id;

        if ($teacherId === 0) {
            return 'Data guru Anda belum terhubung ke rombel manapun. Minta admin sekolah menautkannya.';
        }

        $allowed = Rombel::query()
            ->whereKey($rombelId)
            ->where(function ($query) use ($teacherId, $user): void {
                $query->where('homeroom_teacher_id', $teacherId)
                    ->orWhereHas('schedules', fn ($q) => $q->where('user_id', $user->id));
            })
            ->exists();

        return $allowed
            ? null
            : 'Siswa ini bukan dari kelas yang Anda ampu. Minta admin sekolah untuk memindai.';
    }

    /**
     * Catat satu baris attendances, atau null bila menabrak unique index.
     */
    private function insert(
        Student $student,
        string $type,
        string $status,
        string $date,
        string $time,
        ?Schedule $schedule,
        string $source,
        ?string $note,
    ): ?Attendance {
        try {
            return Attendance::create([
                'tenant_id' => $student->tenant_id,
                'academic_year_id' => $schedule?->academic_year_id ?? $this->activeAcademicYearId(),
                'schedule_id' => $type === 'lesson' ? $schedule?->id : null,
                'student_id' => $student->id,
                'recorded_by' => auth()->id(),
                'type' => $type,
                'status' => $status,
                'date' => $date,
                'time' => $time,
                'late_minutes' => $this->lateMinutes($schedule, $time),
                'source' => $source,
                'note' => $note,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                return null;
            }

            throw $exception;
        }
    }

    private function existingFor(int $studentId, string $type, ?int $scheduleId, string $date): ?Attendance
    {
        $query = Attendance::where('student_id', $studentId)->whereDate('date', $date);

        return $type === 'lesson'
            ? $query->where('schedule_id', $scheduleId)->first()
            : $query->where('type', $type)->first();
    }

    /**
     * Subquery id siswa yang sudah punya catatan pada jadwal dan tanggal ini.
     * Dipakai sebagai sumber `whereNotIn` supaya penandaan sisa kelas tidak
     * perlu memuat seluruh baris ke memori.
     */
    private function recordedStudentIdsQuery(int $scheduleId, string $date, QueryBuilder $query): void
    {
        $query->select('student_id')
            ->from('attendances')
            ->where('schedule_id', $scheduleId)
            ->where('type', 'lesson')
            ->whereDate('date', $date);
    }

    /**
     * Catat pulang yang tidak punya catatan masuk sebagai pembeda saat rekap.
     * Tanpa ini, siswa yang keluar pagar lalu masuk lagi terlihat sama dengan
     * siswa yang benar-benar tidak masuk.
     */
    private function gateOutNote(Student $student, string $date, string $type): ?string
    {
        if ($type !== 'gate_out') {
            return null;
        }

        $hasGateIn = Attendance::where('student_id', $student->id)
            ->where('type', 'gate_in')
            ->whereDate('date', $date)
            ->exists();

        return $hasGateIn
            ? null
            : setting('kehadiran.gate_out_without_gate_in_note', 'Pulang tanpa catatan masuk hari ini');
    }

    private function activeAcademicYearId(): ?int
    {
        return AcademicYear::where('is_active', true)->value('id');
    }

    /**
     * Tulis jejak pemindaian.
     *
     * Kegagalan di sini dilaporkan ke log aplikasi, bukan dilempar ke guru
     * yang sedang memindai. Absensi yang sudah sah tidak boleh hilang hanya
     * karena tabel audit sedang bermasalah.
     */
    private function logScanEvent(
        string $outcome,
        string $type,
        ?int $scheduleId,
        ?Student $student,
        ?Attendance $attendance,
        string $payload,
    ): void {
        try {
            AttendanceScanEvent::create([
                'tenant_id' => $student?->tenant_id ?? $attendance?->tenant_id,
                'attendance_id' => $attendance?->id,
                'student_id' => $student?->id,
                'schedule_id' => $scheduleId,
                'recorded_by' => auth()->id(),
                'type' => $type,
                'outcome' => $outcome,
                'code_hash' => AttendanceScanEvent::hashCode($payload),
                'ip_address' => request()->ip(),
            ]);
        } catch (QueryException $exception) {
            report($exception);
        }
    }

    /**
     * @return array{ok:false, outcome:string, message:string, attendance:null}
     */
    private function reject(
        string $outcome,
        string $type,
        ?int $scheduleId,
        ?Student $student,
        ?Attendance $attendance,
        string $payload,
        string $message,
    ): array {
        $this->logScanEvent($outcome, $type, $scheduleId, $student, $attendance, $payload);

        return ['ok' => false, 'outcome' => $outcome, 'message' => $message, 'attendance' => null];
    }

    /**
     * @return array{ok:true, outcome:string, message:string, attendance:?Attendance}
     */
    private function note(string $message): array
    {
        return ['ok' => true, 'outcome' => 'info', 'message' => $message, 'attendance' => null];
    }

    /**
     * @return array{ok:true, outcome:string, message:string, attendance:?Attendance}
     */
    private function succeed(string $message, ?Attendance $attendance = null): array
    {
        return ['ok' => true, 'outcome' => 'success', 'message' => $message, 'attendance' => $attendance];
    }

    /**
     * @return array{ok:false, outcome:string, message:string, attendance:null}
     */
    private function fail(string $message): array
    {
        return ['ok' => false, 'outcome' => 'error', 'message' => $message, 'attendance' => null];
    }

    /**
     * Bedakan pelanggaran unique dari error database lain, supaya error yang
     * bukan balapan duplikasi tetap terlihat, bukan tertelan diam-diam.
     */
    private function isUniqueViolation(QueryException $exception): bool
    {
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = $exception->getMessage();

        return $driverCode === 1062
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed');
    }
}