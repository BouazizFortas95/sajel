<?php

namespace App\Models;

use App\Enums\ParentsMeetingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentsAssociationMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'parents_association_id',
        'academic_year',
        'meeting_code',
        'meeting_type',
        'meeting_date',
        'attendees_count',
        'agenda_topics',
        'deliberation_narrative',
        'recommendations_and_actions',
        'principal_visa_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'meeting_type' => ParentsMeetingType::class,
            'attendees_count' => 'integer',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(ParentsAssociation::class, 'parents_association_id');
    }
}
