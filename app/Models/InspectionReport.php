<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionReport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'visit_date' => 'date',
        'inspector_signature_date' => 'date',
        'teacher_acknowledged_at' => 'datetime',
        'evaluation_criteria' => 'array',
        'pedagogical_score' => 'decimal:2',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function dailyLog(): BelongsTo
    {
        return $this->belongsTo(DailyLog::class);
    }

    public function curriculumResource(): BelongsTo
    {
        return $this->belongsTo(CurriculumResource::class);
    }

    public function getAppreciationLabelAttribute(): string
    {
        return match ($this->pedagogical_appreciation) {
            'excellent' => __('ممتاز'),
            'very_good' => __('جيد جداً'),
            'good' => __('جيد'),
            'acceptable' => __('مقبول'),
            'needs_improvement' => __('يحتاج إلى متابعة'),
            default => $this->pedagogical_appreciation ?? __('غير محدد'),
        };
    }

    public function getInspectorRoleLabelAttribute(): string
    {
        return match ($this->inspector_role) {
            'principal' => __('مدير المؤسسة'),
            default => __('مفتش التربية الوطنية'),
        };
    }
}
