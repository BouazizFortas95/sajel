<?php

namespace App\Models;

use App\Enums\SportsLicenseType;
use App\Enums\SportsMedicalAptitude;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSportsLicense extends Model
{
    use HasFactory;

    protected $table = 'student_sports_licenses';

    protected $fillable = [
        'license_number',
        'academic_year',
        'student_id',
        'discipline_id',
        'license_type',
        'medical_certificate_issued',
        'medical_certificate_date',
        'medical_examiner_name',
        'medical_aptitude_verdict',
        'parental_consent_signed',
        'insurance_covered',
        'fee_paid',
        'issue_date',
        'expiry_date',
        'status',
        'photo_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'license_type' => SportsLicenseType::class,
            'medical_aptitude_verdict' => SportsMedicalAptitude::class,
            'medical_certificate_issued' => 'boolean',
            'medical_certificate_date' => 'date',
            'parental_consent_signed' => 'boolean',
            'insurance_covered' => 'boolean',
            'fee_paid' => 'boolean',
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(SportsCulturalDiscipline::class, 'discipline_id');
    }

    public function isFullyEligible(): bool
    {
        return $this->status === 'Active'
            && $this->medical_aptitude_verdict === SportsMedicalAptitude::FitApt
            && $this->parental_consent_signed
            && $this->insurance_covered;
    }
}

