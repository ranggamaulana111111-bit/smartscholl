<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use BelongsToTenant, HasFactory;

    public const HADIR = 'hadir';

    public const SAKIT = 'sakit';

    public const IZIN = 'izin';

    public const ALPHA = 'alpha';

    /** @var array<int, string> */
    public const STATUSES = [self::HADIR, self::SAKIT, self::IZIN, self::ALPHA];

    /** @var array<int, string> */
    public const TYPES = ['gate_in', 'gate_out', 'lesson'];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        'gate_in' => 'Masuk Gerbang',
        'gate_out' => 'Pulang Gerbang',
        'lesson' => 'Hadir Pelajaran',
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::HADIR => 'Hadir',
        self::SAKIT => 'Sakit',
        self::IZIN => 'Izin',
        self::ALPHA => 'Alpa',
    ];

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'schedule_id',
        'student_id',
        'recorded_by',
        'type',
        'status',
        'date',
        'time',
        'late_minutes',
        'source',
        'note',
        'dedupe_key',
    ];

    protected $attributes = [
        'source' => 'scan',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'late_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Attendance $attendance): void {
            if (empty($attendance->dedupe_key)) {
                $attendance->dedupe_key = self::buildDedupeKey(
                    $attendance->student_id,
                    $attendance->type,
                    $attendance->schedule_id,
                    $attendance->date,
                );
            }
        });
    }

    public static function buildDedupeKey(
        int|string $studentId,
        string $type,
        int|string|null $scheduleId,
        \DateTimeInterface|string $date,
    ): string {
        return $studentId.':'.$type.':'.($scheduleId ?? 0).':'.Carbon::parse($date)->toDateString();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(AttendanceScanEvent::class);
    }

    public function isPresent(): bool
    {
        return $this->status === self::HADIR;
    }

    /**
     * Status yang menandai kehadiran lengkap di sekolah, bukan sekadar hadir
     * di gerbang. Absensi pelajaran memakai ini, jadi kelas yang punya catatan
     * di setiap jadwal bisa dibedakan dari siswa yang cuma lewat gerbang.
     */
    public function countsAsSchoolDay(): bool
    {
        return $this->type === 'lesson' && $this->status !== self::ALPHA;
    }
}
