<?php

namespace App\Services;

use App\Enums\ChronicConditionType;
use App\Enums\EpsDispensationStatus;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentMedicalRecord;
use App\Services\Concerns\ConfiguresBrowsershot;
use App\Support\BrowsershotFactory;

class SchoolHealthService
{
    use ConfiguresBrowsershot;

    /**
     * Resolve academic year, defaulting to institutional settings or current calendar year.
     */
    public function resolveAcademicYear(?string $year = null): string
    {
        if (filled($year)) {
            return $year;
        }

        return SchoolSetting::currentAcademicYear();
    }

    /**
     * Get or create medical record for a student in an academic year.
     */
    public function getOrCreateRecord(Student $student, ?string $academicYear = null): StudentMedicalRecord
    {
        $year = $this->resolveAcademicYear($academicYear);
        $setting = SchoolSetting::current();

        return StudentMedicalRecord::firstOrCreate(
            [
                'student_id' => $student->id,
                'academic_year' => $year,
            ],
            [
                'uds_name' => $setting?->school_name ? 'وحدة الكشف والمتابعة — '.$setting->school_name : 'وحدة الكشف والمتابعة المدرسية (UDS)',
                'doctor_name' => 'طبيب الصحة المدرسية',
                'eps_dispensation_status' => EpsDispensationStatus::Fit,
                'vaccinations_up_to_date' => true,
            ]
        );
    }

    /**
     * Record systematic medical and biometrical screening for a student.
     */
    public function recordScreening(Student $student, array $data, ?string $academicYear = null): StudentMedicalRecord
    {
        $record = $this->getOrCreateRecord($student, $academicYear);

        $record->update([
            'screening_date' => $data['screening_date'] ?? now()->toDateString(),
            'doctor_name' => $data['doctor_name'] ?? $record->doctor_name,
            'uds_name' => $data['uds_name'] ?? $record->uds_name,
            'height_cm' => $data['height_cm'] ?? $record->height_cm,
            'weight_kg' => $data['weight_kg'] ?? $record->weight_kg,
            'blood_type' => $data['blood_type'] ?? $record->blood_type,
            'visual_acuity_right' => $data['visual_acuity_right'] ?? $record->visual_acuity_right,
            'visual_acuity_left' => $data['visual_acuity_left'] ?? $record->visual_acuity_left,
            'wears_glasses' => (bool) ($data['wears_glasses'] ?? $record->wears_glasses),
            'hearing_condition' => $data['hearing_condition'] ?? $record->hearing_condition,
            'dental_caries_count' => (int) ($data['dental_caries_count'] ?? $record->dental_caries_count),
            'dental_hygiene' => $data['dental_hygiene'] ?? $record->dental_hygiene,
            'general_clinical_notes' => $data['general_clinical_notes'] ?? $record->general_clinical_notes,
            'follow_up_required' => (bool) ($data['follow_up_required'] ?? $record->follow_up_required),
            'specialist_referral' => $data['specialist_referral'] ?? $record->specialist_referral,
        ]);

        return $record->fresh();
    }

    /**
     * Grant or update an official physical education medical exemption (EPS Dispensation).
     */
    public function grantEpsDispensation(
        Student $student,
        EpsDispensationStatus|string $status,
        array $data,
        ?string $academicYear = null
    ): StudentMedicalRecord {
        $record = $this->getOrCreateRecord($student, $academicYear);

        $statusValue = $status instanceof EpsDispensationStatus ? $status : EpsDispensationStatus::from($status);

        $record->update([
            'eps_dispensation_status' => $statusValue,
            'eps_dispensation_start_date' => $data['start_date'] ?? $data['eps_dispensation_start_date'] ?? now()->toDateString(),
            'eps_dispensation_end_date' => $data['end_date'] ?? $data['eps_dispensation_end_date'] ?? null,
            'eps_dispensation_reason' => $data['reason'] ?? $data['eps_dispensation_reason'] ?? null,
            'eps_medical_certificate_ref' => $data['certificate_ref'] ?? $data['eps_medical_certificate_ref'] ?? 'CERT-EPS-'.now()->format('Ymd').'-'.$student->id,
        ]);

        return $record->fresh();
    }

    /**
     * Update chronic condition details, PAI emergency protocol and medication flags.
     */
    public function updateChronicCondition(Student $student, array $data, ?string $academicYear = null): StudentMedicalRecord
    {
        $record = $this->getOrCreateRecord($student, $academicYear);

        $hasChronic = (bool) ($data['has_chronic_illness'] ?? false);
        $conditionType = $data['chronic_condition_type'] ?? ($hasChronic ? ChronicConditionType::Asthma->value : ChronicConditionType::None->value);

        $record->update([
            'has_chronic_illness' => $hasChronic,
            'chronic_condition_type' => $conditionType,
            'chronic_illness_details' => $data['chronic_illness_details'] ?? null,
            'emergency_protocol_notes' => $data['emergency_protocol_notes'] ?? null,
            'emergency_medication_at_school' => (bool) ($data['emergency_medication_at_school'] ?? false),
        ]);

        return $record->fresh();
    }

    /**
     * Compute institutional health overview KPIs and statistics for the UDS cockpit.
     *
     * @return array<string, mixed>
     */
    public function getHealthOverview(?string $academicYear = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);

        $totalStudents = Student::count();
        $records = StudentMedicalRecord::where('academic_year', $year)->get();

        $screenedCount = $records->whereNotNull('screening_date')->count();
        $coverageRate = $totalStudents > 0 ? round(($screenedCount / $totalStudents) * 100, 1) : 0.0;

        $chronicCount = $records->where('has_chronic_illness', true)->count();
        $emergencyMedCount = $records->where('emergency_medication_at_school', true)->count();

        $epsExemptCount = $records->filter(fn (StudentMedicalRecord $r) => $r->isExemptFromEps())->count();
        $epsExemptRate = $totalStudents > 0 ? round(($epsExemptCount / $totalStudents) * 100, 1) : 0.0;

        $cariesTotal = (int) $records->sum('dental_caries_count');
        $followUpCount = $records->where('follow_up_required', true)->count();
        $glassesCount = $records->where('wears_glasses', true)->count();

        // Chronic illness breakdown
        $chronicByType = [];
        foreach (ChronicConditionType::cases() as $case) {
            if ($case === ChronicConditionType::None) {
                continue;
            }
            $count = $records->where('chronic_condition_type', $case)->count();
            if ($count > 0 || in_array($case, [ChronicConditionType::Asthma, ChronicConditionType::Diabetes, ChronicConditionType::SevereAllergy])) {
                $chronicByType[$case->value] = [
                    'label' => $case->label(),
                    'icon' => $case->icon(),
                    'color' => $case->color(),
                    'count' => $count,
                ];
            }
        }

        // EPS breakdown
        $epsByStatus = [];
        foreach (EpsDispensationStatus::cases() as $status) {
            $epsByStatus[$status->value] = [
                'label' => $status->shortLabel(),
                'color' => $status->color(),
                'count' => $records->where('eps_dispensation_status', $status)->count(),
            ];
        }

        // Level breakdown
        $levelsData = [];
        $levels = Level::orderBy('id')->get();
        foreach ($levels as $lvl) {
            $studentIds = Student::whereHas('group.classroom', fn ($q) => $q->where('level_id', $lvl->id))->pluck('id');
            $lvlTotal = $studentIds->count();
            $lvlRecords = $records->whereIn('student_id', $studentIds);
            $lvlScreened = $lvlRecords->whereNotNull('screening_date')->count();
            $lvlChronic = $lvlRecords->where('has_chronic_illness', true)->count();
            $lvlEps = $lvlRecords->filter(fn (StudentMedicalRecord $r) => $r->isExemptFromEps())->count();

            $levelsData[$lvl->code ?? $lvl->name] = [
                'name' => $lvl->name,
                'total' => $lvlTotal,
                'screened' => $lvlScreened,
                'coverage_rate' => $lvlTotal > 0 ? round(($lvlScreened / $lvlTotal) * 100, 1) : 0.0,
                'chronic' => $lvlChronic,
                'eps_exempt' => $lvlEps,
            ];
        }

        return [
            'academic_year' => $year,
            'total_students' => $totalStudents,
            'screened_count' => $screenedCount,
            'coverage_rate' => $coverageRate,
            'chronic_count' => $chronicCount,
            'emergency_med_count' => $emergencyMedCount,
            'eps_exempt_count' => $epsExemptCount,
            'eps_exempt_rate' => $epsExemptRate,
            'caries_total' => $cariesTotal,
            'follow_up_count' => $followUpCount,
            'glasses_count' => $glassesCount,
            'chronic_by_type' => $chronicByType,
            'eps_by_status' => $epsByStatus,
            'level_breakdown' => $levelsData,
        ];
    }

    /**
     * Generate A4 Portrait PDF for Student Medical Dossier (UDS Card).
     */
    public function generateStudentMedicalDossierPdf(StudentMedicalRecord $record): string
    {
        $schoolSetting = SchoolSetting::first();
        $html = view('print.student-medical-dossier', [
            'record' => $record,
            'student' => $record->student,
            'schoolSetting' => $schoolSetting,
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(6, 6, 6, 6)
            ->pdf();
    }

    /**
     * Generate A4 Portrait PDF for Official Physical Education Medical Exemption Certificate.
     */
    public function generateEpsDispensationCertificatePdf(StudentMedicalRecord $record): string
    {
        $schoolSetting = SchoolSetting::first();
        $html = view('print.eps-medical-dispensation', [
            'record' => $record,
            'student' => $record->student,
            'schoolSetting' => $schoolSetting,
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(8, 8, 8, 8)
            ->pdf();
    }

    /**
     * Generate A4 Landscape PDF for Annual UDS Health & Screening Statistical Report.
     */
    public function generateAnnualUdsBilanPdf(?string $academicYear = null): string
    {
        $overview = $this->getHealthOverview($academicYear);
        $schoolSetting = SchoolSetting::first();
        $html = view('print.annual-uds-bilan', [
            'overview' => $overview,
            'schoolSetting' => $schoolSetting,
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape()
            ->showBackground()
            ->margins(4, 5, 4, 5)
            ->pdf();
    }
}
