<?php

namespace App\Models;

use App\Enums\SocialEligibilityCategory;
use App\Enums\SolidarityBeneficiaryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSolidarityBeneficiary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'eligibility_category' => SocialEligibilityCategory::class,
        'monthly_income' => 'decimal:2',
        'status' => SolidarityBeneficiaryStatus::class,
        'payment_date' => 'date',
        'submitted_documents' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SchoolSolidarityCampaign::class, 'campaign_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
