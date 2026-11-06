<?php

namespace Database\Factories;

use App\Enums\BloodType;
use App\Enums\ChronicConditionType;
use App\Enums\DentalHygieneStatus;
use App\Enums\EpsDispensationStatus;
use App\Enums\HearingCondition;
use App\Models\Student;
use App\Models\StudentMedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentMedicalRecord>
 */
class StudentMedicalRecordFactory extends Factory
{
    protected $model = StudentMedicalRecord::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year' => '2026/2027',
            'screening_date' => now()->toDateString(),
            'doctor_name' => 'طبيب الصحة المدرسية (UDS)',
            'uds_name' => 'وحدة الكشف والمتابعة المدرسية',
            'blood_type' => fake()->randomElement(BloodType::cases()),
            'weight_kg' => fake()->randomFloat(1, 35, 65),
            'height_cm' => fake()->randomFloat(1, 140, 175),
            'visual_acuity_right' => '10/10',
            'visual_acuity_left' => '10/10',
            'wears_glasses' => false,
            'hearing_condition' => HearingCondition::Normal,
            'dental_hygiene' => DentalHygieneStatus::Good,
            'dental_caries_count' => 0,
            'has_chronic_illness' => false,
            'chronic_condition_type' => ChronicConditionType::None,
            'chronic_illness_details' => null,
            'emergency_protocol_notes' => null,
            'emergency_medication_at_school' => false,
            'vaccinations_up_to_date' => true,
            'vaccination_notes' => null,
            'eps_dispensation_status' => EpsDispensationStatus::Fit,
            'eps_dispensation_reason' => null,
            'eps_dispensation_start_date' => null,
            'eps_dispensation_end_date' => null,
            'eps_medical_certificate_ref' => null,
            'general_clinical_notes' => null,
            'follow_up_required' => false,
            'specialist_referral' => null,
        ];
    }
}
