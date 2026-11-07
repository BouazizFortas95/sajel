<?php

namespace App\Models;

use App\Enums\PacingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumPacingAudit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'trimester' => 'integer',
        'audit_date' => 'date',
        'expected_resources_count' => 'integer',
        'completed_resources_count' => 'integer',
        'execution_percentage' => 'float',
        'pacing_status' => PacingStatus::class,
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function auditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by');
    }
}

