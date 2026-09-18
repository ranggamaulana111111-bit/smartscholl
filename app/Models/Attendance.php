<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToTenant, HasFactory;

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
        'source',
        'note',
        'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
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
}
