<?php

namespace App\Models;

use App\Enums\SolidarityCampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolSolidarityCampaign extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'campaign_year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'grant_amount' => 'decimal:2',
        'status' => SolidarityCampaignStatus::class,
        'allocated_budget' => 'decimal:2',
        'approved_beneficiaries_count' => 'integer',
        'total_disbursed_amount' => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(StudentSolidarityBeneficiary::class, 'campaign_id');
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(SchoolSolidarityCommitteeMember::class, 'campaign_id');
    }

    public function aidDistributions(): HasMany
    {
        return $this->hasMany(SchoolSolidarityAidDistribution::class, 'campaign_id');
    }
}
