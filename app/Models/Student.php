<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Student extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'rombel_id',
        'user_id',
        'nisn',
        'qr_token',
        'rfid_uid',
        'nis',
        'name',
        'gender',
        'birth_date',
        'birth_place',
        'address',
        'phone',
    ];

    protected static function booted(): void
    {
        static::creating(function (Student $student): void {
            if (empty($student->qr_token)) {
                $student->qr_token = Str::random(32);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function qrcodePayload(): string
    {
        return 'SS:'.($this->qr_token ?: $this->nisn);
    }

    public function regenerateQrToken(): string
    {
        $this->qr_token = Str::random(32);
        $this->save();

        return $this->qr_token;
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function rombelHistories(): HasMany
    {
        return $this->hasMany(StudentRombelHistory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function assessmentGrades(): HasMany
    {
        return $this->hasMany(AssessmentGrade::class);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function earlyWarningLogs(): HasMany
    {
        return $this->hasMany(EarlyWarningLog::class);
    }

    public function parentLinks(): HasMany
    {
        return $this->hasMany(StudentParent::class);
    }
}
