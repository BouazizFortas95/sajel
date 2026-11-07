<?php

namespace App\Models;

use App\Enums\SportsCompetitionLevel;
use App\Enums\SportsMatchResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsCompetitionEvent extends Model
{
    use HasFactory;

    protected $table = 'sports_competitions_events';

    protected $fillable = [
        'event_code',
        'academic_year',
        'discipline_id',
        'event_name',
        'competition_level',
        'event_date',
        'event_time',
        'location_venue',
        'host_institution',
        'opponent_team_name',
        'our_score',
        'opponent_score',
        'match_result',
        'ranking_achieved',
        'fair_play_score',
        'chaperones_list',
        'transport_arranged',
        'participation_notes',
    ];

    protected function casts(): array
    {
        return [
            'competition_level' => SportsCompetitionLevel::class,
            'match_result' => SportsMatchResult::class,
            'event_date' => 'date',
            'our_score' => 'integer',
            'opponent_score' => 'integer',
            'fair_play_score' => 'integer',
            'transport_arranged' => 'boolean',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(SportsCulturalDiscipline::class, 'discipline_id');
    }
}
