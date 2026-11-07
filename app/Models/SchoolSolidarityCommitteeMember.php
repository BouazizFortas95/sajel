<?php

namespace App\Models;

use App\Enums\SolidarityCommitteeRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSolidarityCommitteeMember extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'committee_role' => SolidarityCommitteeRole::class,
        'attended_deliberation' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SchoolSolidarityCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
