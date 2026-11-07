<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolInternalRegulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'regulation_code',
        'title',
        'version',
        'adoption_board_session_id',
        'adoption_date',
        'directorate_approval_date',
        'key_rules_summary',
        'full_content_sections',
        'is_current_active',
        'signed_undertakings_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'adoption_date' => 'date',
            'directorate_approval_date' => 'date',
            'full_content_sections' => 'array',
            'is_current_active' => 'boolean',
            'signed_undertakings_count' => 'integer',
        ];
    }

    public function boardSession(): BelongsTo
    {
        return $this->belongsTo(SchoolBoardSession::class, 'adoption_board_session_id');
    }
}

