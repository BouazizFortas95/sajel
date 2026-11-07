<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyCommitteeMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'session_code',
        'meeting_date',
        'session_type',
        'meeting_chair',
        'members_present',
        'agenda_items',
        'deliberation_summary',
        'decisions_and_recommendations',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'members_present' => 'array',
        ];
    }
}
