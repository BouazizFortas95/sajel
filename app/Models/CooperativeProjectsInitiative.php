<?php

namespace App\Models;

use App\Enums\CooperativeProjectStatus;
use App\Enums\CooperativeProjectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeProjectsInitiative extends Model
{
    use HasFactory;

    protected $table = 'cooperative_projects_initiatives';

    protected $fillable = [
        'project_code',
        'academic_year',
        'school_cooperative_id',
        'title',
        'project_type',
        'target_audience',
        'start_date',
        'end_date',
        'budget_allocated',
        'budget_spent',
        'status',
        'coordinator_user_id',
        'student_leads_summary',
        'achievements_notes',
    ];

    protected function casts(): array
    {
        return [
            'project_type' => CooperativeProjectType::class,
            'status' => CooperativeProjectStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'budget_allocated' => 'decimal:2',
            'budget_spent' => 'decimal:2',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(SchoolCooperative::class, 'school_cooperative_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }
}
