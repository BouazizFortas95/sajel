<?php

namespace App\Models;

use App\Enums\SchoolBoardSessionStatus;
use App\Enums\SchoolBoardSessionTerm;
use App\Enums\SchoolBoardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SchoolBoardSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'session_code',
        'board_type',
        'session_term',
        'session_date',
        'session_time',
        'meeting_place',
        'president_user_id',
        'president_name',
        'rapporteur_user_id',
        'rapporteur_name',
        'quorum_required',
        'members_present_count',
        'is_quorum_reached',
        'agenda_items',
        'deliberation_details',
        'decisions_summary',
        'status',
        'approval_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'approval_date' => 'date',
            'board_type' => SchoolBoardType::class,
            'session_term' => SchoolBoardSessionTerm::class,
            'status' => SchoolBoardSessionStatus::class,
            'is_quorum_reached' => 'boolean',
            'quorum_required' => 'integer',
            'members_present_count' => 'integer',
        ];
    }

    public function president(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_user_id');
    }

    public function rapporteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rapporteur_user_id');
    }

    public function schoolProject(): HasOne
    {
        return $this->hasOne(SchoolProject::class, 'board_session_id');
    }

    public function internalRegulations(): HasMany
    {
        return $this->hasMany(SchoolInternalRegulation::class, 'adoption_board_session_id');
    }
}
