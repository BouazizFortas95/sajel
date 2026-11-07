<?php

namespace App\Models;

use App\Enums\SafetyIncidentSeverity;
use App\Enums\SafetyIncidentStatus;
use App\Enums\SafetyIncidentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSafetyIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'incident_number',
        'incident_type',
        'severity_level',
        'incident_date',
        'location',
        'victim_type',
        'student_id',
        'user_id',
        'victim_name',
        'circumstances_description',
        'bodily_injuries_description',
        'immediate_care_administered',
        'hospital_evacuation',
        'hospital_name',
        'medical_certificate_ref',
        'temporary_incapacity_days',
        'witnesses_statements',
        'supervising_personnel_at_time',
        'insurance_declared',
        'declaration_date',
        'directorate_notified',
        'status',
        'investigating_officer_name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'datetime',
            'declaration_date' => 'date',
            'incident_type' => SafetyIncidentType::class,
            'severity_level' => SafetyIncidentSeverity::class,
            'status' => SafetyIncidentStatus::class,
            'hospital_evacuation' => 'boolean',
            'insurance_declared' => 'boolean',
            'directorate_notified' => 'boolean',
            'temporary_incapacity_days' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
