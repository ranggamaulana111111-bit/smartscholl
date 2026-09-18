<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'subject_id',
        'rombel_id',
        'teacher_id',
        'category',
        'title',
        'description',
        'date',
        'max_score',
        'weight_percentage',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'max_score' => 'integer',
            'weight_percentage' => 'integer',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(AssessmentGrade::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabel($this->category);
    }

    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'tugas' => 'Tugas',
            'formatif' => 'Formatif / UH',
            'uts' => 'UTS',
            'uas' => 'UAS',
            default => ucfirst($category),
        };
    }

    public static function instanceCategories(): array
    {
        return [
            'tugas' => 'Tugas',
            'formatif' => 'Formatif',
            'uts' => 'UTS',
            'uas' => 'UAS',
        ];
    }
}
