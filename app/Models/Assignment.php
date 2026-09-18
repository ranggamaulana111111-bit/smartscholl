<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'teacher_id',
        'rombel_id',
        'subject_id',
        'title',
        'description',
        'deadline_at',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function getDeadlineLabelAttribute(): string
    {
        return $this->deadline_at->translatedFormat('d M Y H:i');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline_at->isPast();
    }
}
