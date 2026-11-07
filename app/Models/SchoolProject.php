<?php

namespace App\Models;

use App\Enums\SchoolProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_code',
        'title',
        'start_year',
        'end_year',
        'strategic_axes',
        'quantitative_targets',
        'action_plan',
        'steering_committee_members',
        'board_session_id',
        'status',
        'directorate_validation_date',
        'completion_rate_percentage',
        'evaluation_notes',
    ];

    protected function casts(): array
    {
        return [
            'strategic_axes' => 'array',
            'quantitative_targets' => 'array',
            'steering_committee_members' => 'array',
            'directorate_validation_date' => 'date',
            'status' => SchoolProjectStatus::class,
            'completion_rate_percentage' => 'decimal:2',
        ];
    }

    public function boardSession(): BelongsTo
    {
        return $this->belongsTo(SchoolBoardSession::class, 'board_session_id');
    }
}

