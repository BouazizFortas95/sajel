<?php

namespace App\Models;

use App\Enums\EcoActionCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcoGreenClubAction extends Model
{
    use HasFactory;

    protected $table = 'eco_green_club_actions';

    protected $fillable = [
        'action_code',
        'academic_year',
        'action_title',
        'action_category',
        'execution_date',
        'participants_count',
        'trees_planted_count',
        'waste_collected_kg',
        'supervising_teacher_id',
        'location_details',
        'partner_organization',
        'impact_summary',
    ];

    protected function casts(): array
    {
        return [
            'action_category' => EcoActionCategory::class,
            'execution_date' => 'date',
            'participants_count' => 'integer',
            'trees_planted_count' => 'integer',
            'waste_collected_kg' => 'decimal:2',
        ];
    }

    public function supervisingTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervising_teacher_id');
    }
}
