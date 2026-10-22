<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris untuk setiap kode yang dipindai, termasuk yang ditolak.
 *
 * Append-only: tidak ada updated_at karena baris yang sudah tercatat tidak
 * boleh berubah. Koreksi atas absensi yang sudah jadi ada di tabel
 * attendances dan tercatat sebagai audit log, bukan dengan mengedit riwayat
 * pemindaian.
 */
class AttendanceScanEvent extends Model
{
    use BelongsToTenant, HasFactory;

    public const RECORDED = 'recorded';

    public const DUPLICATE = 'duplicate';

    public const UNKNOWN_CODE = 'unknown_code';

    public const EMPTY_PAYLOAD = 'empty_payload';

    public const STUDENT_NOT_IN_CLASS = 'student_not_in_class';

    public const OUTSIDE_WINDOW = 'outside_window';

    /** @var array<int, string> */
    public const OUTCOMES = [
        self::RECORDED,
        self::DUPLICATE,
        self::UNKNOWN_CODE,
        self::EMPTY_PAYLOAD,
        self::STUDENT_NOT_IN_CLASS,
        self::OUTSIDE_WINDOW,
    ];

    /** Outcome yang bukan penutupan proses, melainkan penolakan. */
    public const REJECTED_OUTCOMES = [
        self::UNKNOWN_CODE,
        self::EMPTY_PAYLOAD,
        self::STUDENT_NOT_IN_CLASS,
        self::OUTSIDE_WINDOW,
    ];

    /**
     * Label dan warna badge untuk setiap hasil pemindaian.
     *
     * Dipakai di halaman review, jadi disimpan di model agarcontroller dan
     * view memakai daftar yang sama. Menambah outcome baru cukup di satu
     * tempat, tidak ada lagi daftar label yang harus disinkronkan terpisah.
     *
     * @var array<string, array{label:string, tone:string}>
     */
    public const OUTCOME_META = [
        self::RECORDED => ['label' => 'Tercatat', 'tone' => 'badge-success'],
        self::DUPLICATE => ['label' => 'Sudah ada', 'tone' => 'badge-pending'],
        self::STUDENT_NOT_IN_CLASS => ['label' => 'Rombel tidak cocok', 'tone' => 'badge-warning'],
        self::OUTSIDE_WINDOW => ['label' => 'Di luar jam', 'tone' => 'badge-warning'],
        self::UNKNOWN_CODE => ['label' => 'Kode asing', 'tone' => 'badge-danger'],
        self::EMPTY_PAYLOAD => ['label' => 'Kosong', 'tone' => 'badge-pending'],
    ];

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'attendance_id',
        'student_id',
        'schedule_id',
        'recorded_by',
        'type',
        'outcome',
        'code_hash',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isRejected(): bool
    {
        return in_array($this->outcome, self::REJECTED_OUTCOMES, true);
    }

    /**
     * Hash stabil untuk satu kode pindai.
     *
     * SHA-256 dipakai, bukan plain random token, supaya kode yang sama dari
     * scanner berbeda menghasilkan hash yang sama. Itu yang membuat pola
     * "satu kartu dipakai di banyak tempat" bisa dicari tanpa menyimpan kode
     * aslinya di tabel audit.
     */
    public static function hashCode(string $payload): ?string
    {
        $payload = trim($payload);

        return $payload === '' ? null : hash('sha256', $payload);
    }
}
