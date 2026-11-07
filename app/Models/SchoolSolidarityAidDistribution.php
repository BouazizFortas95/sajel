<?php

namespace App\Models;

use App\Enums\SolidarityAidType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSolidarityAidDistribution extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'aid_type' => SolidarityAidType::class,
        'distribution_date' => 'date',
        'estimated_value' => 'decimal:2',
        'received_by_guardian' => 'boolean',
        'guardian_signature_confirmed' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SchoolSolidarityCampaign::class, 'campaign_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
