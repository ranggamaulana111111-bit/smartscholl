<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Pembacaan rekap absensi harian.
 *
 * Semua penghitungan dihitung per siswa, bukan per baris absensi. Satu siswa
 * bisa punya beberapa baris pada satu hari, misalnya hadir di empat pelajaran,
 * sehingga menghitung baris membuat jumlah hadir melebihi jumlah siswa.
 *
 * Yang dihitung sebagai "sudah punya catatan" hanya absensi bertipe `lesson`.
 * Scan gerbang bukan bukti kehadiran di kelas: siswa yang membolos satu
 * pelajaran tetap boleh scan di pagar, dan rekap guru harus tetap menunjukkan
 * missing-nya, bukan menutupinya.
 */
class AttendanceRecap
{
    /** Urutan status dari yang paling ringan ke paling serius. */
    private const SERIOUSNESS = ['alpha', 'izin', 'sakit'];

    /**
     * Rekap harian per rombel, dihitung dengan jumlah query tetap.
     *
     * @return array{
     *     date: Carbon,
     *     date_label: string,
     *     day_label: string,
     *     is_school_day: bool,
     *     rombels: Collection<int, array<string, mixed>>,
     *     totals: array<string, int|float>,
     *     unrecorded: Collection<int, array<string, mixed>>,
     *     unrecorded_total: int
     * }
     */
    public function daily(string $date, ?int $rombelId = null, ?User $viewer = null): array
    {
        $tanggal = Carbon::parse($date);
        $rombels = $this->visibleRombels($viewer, $rombelId);

        $studentsByRombel = Student::query()
            ->whereIn('rombel_id', $rombels->pluck('id'))
            ->orderBy('name')
            ->get()
            ->groupBy('rombel_id');

        $statusesByStudent = $this->statusesByStudent($tanggal, $rombels->pluck('id')->all());

        $rows = $rombels->map(function (Rombel $rombel) use ($studentsByRombel, $statusesByStudent): array {
            $students = $studentsByRombel->get($rombel->id, collect());
            $counts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
            $missing = [];

            foreach ($students as $student) {
                $statuses = $statusesByStudent[$student->id] ?? [];

                if ($statuses === []) {
                    $missing[] = $student;

                    continue;
                }

                $counts[$this->dailyStatus($statuses)]++;
            }

            $total = $students->count();
            $recorded = $total - count($missing);

            return [
                'rombel' => $rombel,
                'name' => $rombel->name,
                'students' => $total,
                'recorded' => $recorded,
                'unrecorded' => count($missing),
                ...$counts,
                'rate' => $total > 0 ? round($recorded / $total * 100, 1) : 0.0,
                'missing' => $missing,
            ];
        })->values();

        $totals = $this->sumTotals($rows);

        $unrecorded = $rows
            ->flatMap(fn (array $row) => array_map(
                fn (Student $student) => ['student' => $student, 'rombel_name' => $row['name']],
                $row['missing']
            ))
            ->values();

        return [
            'date' => $tanggal,
            'date_label' => $tanggal->translatedFormat('d M Y'),
            'day_label' => hariName((int) $tanggal->isoWeekday()),
            'is_school_day' => $this->isSchoolDay($tanggal),
            'rombels' => $rows,
            'totals' => $totals,
            'unrecorded' => $unrecorded,
            'unrecorded_total' => $totals['unrecorded'],
        ];
    }

    /**
     * Baris rekap dalam bentuk datar untuk diekspor ke CSV.
     *
     * @return array<int, array<int, string|int|float>>
     */
    public function csvRows(string $date, ?int $rombelId, ?User $viewer): array
    {
        $rekap = $this->daily($date, $rombelId, $viewer);

        $rows = [[
            'Rombel', 'Jumlah Siswa', 'Sudah Absen', 'Belum Absen',
            'Hadir', 'Sakit', 'Izin', 'Alpa', 'Persentase (%)',
        ]];

        foreach ($rekap['rombels'] as $row) {
            $rows[] = [
                $row['name'],
                $row['students'],
                $row['recorded'],
                $row['unrecorded'],
                $row['hadir'],
                $row['sakit'],
                $row['izin'],
                $row['alpha'],
                $row['rate'],
            ];
        }

        $totals = $rekap['totals'];

        $rows[] = [];
        $rows[] = ['TOTAL SEKOLAH'];
        $rows[] = ['Jumlah Siswa', $totals['students']];
        $rows[] = ['Sudah Absen', $totals['recorded']];
        $rows[] = ['Belum Absen', $totals['unrecorded']];
        $rows[] = ['Hadir', $totals['hadir']];
        $rows[] = ['Sakit', $totals['sakit']];
        $rows[] = ['Izin', $totals['izin']];
        $rows[] = ['Alpa', $totals['alpha']];
        $rows[] = ['Persentase (%)', $totals['rate']];

        $rows[] = [];
        $rows[] = ['DAFTAR SISWA BELUM ABSEN PADA '.$rekap['day_label'].' '.$rekap['date_label']];
        $rows[] = ['Rombel', 'NISN', 'Nama'];

        foreach ($rekap['unrecorded'] as $row) {
            $rows[] = [$row['rombel_name'], $row['student']->nisn, $row['student']->name];
        }

        return $rows;
    }

    /**
     * Status absensi pelajaran hari ini untuk setiap siswa, dikelompokkan per
     * siswa. Nilai bisa berisi lebih dari satu status kalau siswa punya catatan
     * berbeda di beberapa pelajaran.
     *
     * @param  array<int, int>  $rombelIds
     * @return array<int, array<int, string>>
     */
    private function statusesByStudent(Carbon $date, array $rombelIds): array
    {
        if ($rombelIds === []) {
            return [];
        }

        return Attendance::query()
            ->where('type', 'lesson')
            ->whereDate('date', $date->toDateString())
            ->whereIn('schedule_id', Schedule::query()->whereIn('rombel_id', $rombelIds)->select('id'))
            ->pluck('status', 'student_id')
            ->groupBy('student_id')
            ->map(fn ($statuses) => array_values($statuses->all()))
            ->all();
    }

    /**
     * Satu status utama untuk siswa pada hari itu.
     *
     * Kalau seorang siswa izin di satu pelajaran dan tidak masuk di pelajaran
     * lain, rekap harian harus menyebut alpa, bukan izin. Status paling serius
     * menang supaya rekap punya satu jawaban yang bisa ditindaklanjuti.
     *
     * @param  array<int, string>  $statuses
     */
    private function dailyStatus(array $statuses): string
    {
        foreach (self::SERIOUSNESS as $serious) {
            if (in_array($serious, $statuses, true)) {
                return $serious;
            }
        }

        return Attendance::HADIR;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int|float>
     */
    private function sumTotals(Collection $rows): array
    {
        $totals = [
            'students' => 0,
            'recorded' => 0,
            'unrecorded' => 0,
            'hadir' => 0,
            'sakit' => 0,
            'izin' => 0,
            'alpha' => 0,
            'rate' => 0.0,
        ];

        foreach ($rows as $row) {
            foreach (['students', 'recorded', 'unrecorded', 'hadir', 'sakit', 'izin', 'alpha'] as $key) {
                $totals[$key] += $row[$key];
            }
        }

        $totals['rate'] = $totals['students'] > 0
            ? round($totals['recorded'] / $totals['students'] * 100, 1)
            : 0.0;

        return $totals;
    }

    /**
     * Rombel yang boleh dibuka oleh pengguna saat ini.
     *
     * Guru hanya melihat rombel binaannya, sama seperti aturan di halaman kartu
     * QR. Rombel di luar itu disaring dari kueri, bukan dijawab 403, supaya
     * URL yang dibagikan guru tidak membocorkan nama rombel orang lain.
     *
     * @return Collection<int, Rombel>
     */
    public function visibleRombels(?User $viewer, ?int $rombelId = null): Collection
    {
        return Rombel::query()
            ->when($viewer?->isGuru(), function ($query) use ($viewer): void {
                $teacherId = (int) ($viewer->teacher?->id ?? 0);

                $query->where(function ($inner) use ($teacherId, $viewer): void {
                    $inner
                        ->when(
                            $teacherId,
                            fn ($q) => $q->where('homeroom_teacher_id', $teacherId),
                            fn ($q) => $q->whereRaw('1 = 0')
                        )
                        ->orWhereHas('schedules', fn ($q) => $q->where('user_id', $viewer->id));
                });
            })
            ->when($rombelId, fn ($query) => $query->whereKey($rombelId))
            ->orderBy('name')
            ->get();
    }

    /**
     * Akhir pekan tidak pernah menjadi hari sekolah, sehingga rekap hari itu
     * memang kosong. Ditandai eksplisit supaya halaman bisa menjelaskan
     * kekosongannya, bukan terlihat seperti data hilang.
     */
    public function isSchoolDay(Carbon $date): bool
    {
        return ! in_array((int) $date->isoWeekday(), [6, 7], true);
    }
}