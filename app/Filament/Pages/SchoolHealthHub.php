<?php

namespace App\Filament\Pages;

use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentMedicalRecord;
use App\Models\User;
use App\Services\SchoolHealthService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class SchoolHealthHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.school-health-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'screenings'; // 'screenings', 'chronic', 'eps', 'uds_bilan'

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    #[Url(as: 'level')]
    public ?int $selectedLevelId = null;

    #[Url(as: 'q')]
    public string $searchQuery = '';

    #[Url(as: 'status')]
    public ?string $selectedFilterStatus = null; // 'all', 'screened', 'unscreened', 'chronic', 'exempted'

    // Modal state: Screening Visit
    public bool $isScreeningModalOpen = false;

    public ?int $screeningStudentId = null;

    public ?string $screeningDate = null;

    public ?string $doctorName = 'طبيب الصحة المدرسية';

    public ?string $udsName = null;

    public ?float $heightCm = null;

    public ?float $weightKg = null;

    public string $bloodType = 'unknown';

    public string $visualAcuityRight = '10/10';

    public string $visualAcuityLeft = '10/10';

    public bool $wearsGlasses = false;

    public string $hearingCondition = 'normal';

    public int $dentalCariesCount = 0;

    public string $dentalHygiene = 'good';

    public ?string $generalClinicalNotes = null;

    public bool $followUpRequired = false;

    public ?string $specialistReferral = null;

    // Modal state: Physical Education (EPS) Dispensation
    public bool $isDispensationModalOpen = false;

    public ?int $dispensationStudentId = null;

    public string $epsStatus = 'annual';

    public ?string $epsStartDate = null;

    public ?string $epsEndDate = null;

    public ?string $epsReason = null;

    public ?string $epsCertificateRef = null;

    // Modal state: Chronic Condition & Emergency PAI Protocol
    public bool $isChronicModalOpen = false;

    public ?int $chronicStudentId = null;

    public bool $hasChronicIllness = true;

    public string $chronicConditionType = 'asthma';

    public ?string $chronicIllnessDetails = null;

    public ?string $emergencyProtocolNotes = null;

    public bool $emergencyMedicationAtSchool = false;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isAdmin() || $user->isEducationStaff());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('الصحة المدرسية ووحدة (UDS)');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public function getTitle(): string
    {
        return __('منظومة الصحة المدرسية ووحدة الكشف والمتابعة (UDS)');
    }

    public function mount(SchoolHealthService $service): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('-', '/', $setting->academic_year);
        }
        $this->screeningDate = now()->toDateString();
        $this->epsStartDate = now()->toDateString();
        $this->udsName = $setting?->school_name ? 'وحدة الكشف والمتابعة — '.$setting->school_name : 'وحدة الكشف والمتابعة المدرسية (UDS)';
    }

    public function openScreeningModal(?int $studentId = null): void
    {
        $this->resetScreeningForm();
        if ($studentId) {
            $this->screeningStudentId = $studentId;
            $record = StudentMedicalRecord::where('student_id', $studentId)
                ->where('academic_year', $this->academicYear)
                ->first();

            if ($record) {
                $this->screeningDate = $record->screening_date?->toDateString() ?? now()->toDateString();
                $this->doctorName = $record->doctor_name ?? 'طبيب الصحة المدرسية';
                $this->udsName = $record->uds_name ?? $this->udsName;
                $this->heightCm = $record->height_cm ? (float) $record->height_cm : null;
                $this->weightKg = $record->weight_kg ? (float) $record->weight_kg : null;
                $this->bloodType = $record->blood_type?->value ?? 'unknown';
                $this->visualAcuityRight = $record->visual_acuity_right ?? '10/10';
                $this->visualAcuityLeft = $record->visual_acuity_left ?? '10/10';
                $this->wearsGlasses = (bool) $record->wears_glasses;
                $this->hearingCondition = $record->hearing_condition?->value ?? 'normal';
                $this->dentalCariesCount = (int) $record->dental_caries_count;
                $this->dentalHygiene = $record->dental_hygiene?->value ?? 'good';
                $this->generalClinicalNotes = $record->general_clinical_notes;
                $this->followUpRequired = (bool) $record->follow_up_required;
                $this->specialistReferral = $record->specialist_referral;
            }
        }
        $this->isScreeningModalOpen = true;
    }

    public function saveScreening(SchoolHealthService $service): void
    {
        $this->validate([
            'screeningStudentId' => 'required|exists:students,id',
            'screeningDate' => 'required|date',
            'heightCm' => 'nullable|numeric|min:50|max:250',
            'weightKg' => 'nullable|numeric|min:15|max:200',
        ]);

        $student = Student::findOrFail($this->screeningStudentId);

        $service->recordScreening($student, [
            'screening_date' => $this->screeningDate,
            'doctor_name' => $this->doctorName,
            'uds_name' => $this->udsName,
            'height_cm' => $this->heightCm,
            'weight_kg' => $this->weightKg,
            'blood_type' => $this->bloodType,
            'visual_acuity_right' => $this->visualAcuityRight,
            'visual_acuity_left' => $this->visualAcuityLeft,
            'wears_glasses' => $this->wearsGlasses,
            'hearing_condition' => $this->hearingCondition,
            'dental_caries_count' => $this->dentalCariesCount,
            'dental_hygiene' => $this->dentalHygiene,
            'general_clinical_notes' => $this->generalClinicalNotes,
            'follow_up_required' => $this->followUpRequired,
            'specialist_referral' => $this->specialistReferral,
        ], $this->academicYear);

        $this->isScreeningModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل الفحص الطبي والمؤشرات البيومترية بنجاح'))
            ->success()
            ->send();
    }

    public function openDispensationModal(?int $studentId = null): void
    {
        $this->dispensationStudentId = $studentId;
        if ($studentId) {
            $record = StudentMedicalRecord::where('student_id', $studentId)
                ->where('academic_year', $this->academicYear)
                ->first();

            if ($record && $record->isExemptFromEps()) {
                $this->epsStatus = $record->eps_dispensation_status->value;
                $this->epsStartDate = $record->eps_dispensation_start_date?->toDateString() ?? now()->toDateString();
                $this->epsEndDate = $record->eps_dispensation_end_date?->toDateString();
                $this->epsReason = $record->eps_dispensation_reason;
                $this->epsCertificateRef = $record->eps_medical_certificate_ref;
            } else {
                $this->epsStatus = 'annual';
                $this->epsStartDate = now()->toDateString();
                $this->epsEndDate = null;
                $this->epsReason = null;
                $this->epsCertificateRef = 'CERT-EPS-'.now()->format('Ymd').'-'.$studentId;
            }
        }
        $this->isDispensationModalOpen = true;
    }

    public function saveDispensation(SchoolHealthService $service): void
    {
        $this->validate([
            'dispensationStudentId' => 'required|exists:students,id',
            'epsStatus' => 'required|string',
            'epsStartDate' => 'required|date',
            'epsEndDate' => 'nullable|date|after_or_equal:epsStartDate',
        ]);

        $student = Student::findOrFail($this->dispensationStudentId);

        $service->grantEpsDispensation($student, $this->epsStatus, [
            'eps_dispensation_start_date' => $this->epsStartDate,
            'eps_dispensation_end_date' => $this->epsEndDate,
            'eps_dispensation_reason' => $this->epsReason,
            'eps_medical_certificate_ref' => $this->epsCertificateRef ?: 'CERT-EPS-'.now()->format('Ymd').'-'.$student->id,
        ], $this->academicYear);

        $this->isDispensationModalOpen = false;

        Notification::make()
            ->title(__('تم توثيق إعفاء التربية البدنية والرياضية بنجاح'))
            ->success()
            ->send();
    }

    public function openChronicModal(?int $studentId = null): void
    {
        $this->chronicStudentId = $studentId;
        if ($studentId) {
            $record = StudentMedicalRecord::where('student_id', $studentId)
                ->where('academic_year', $this->academicYear)
                ->first();

            if ($record) {
                $this->hasChronicIllness = (bool) $record->has_chronic_illness;
                $this->chronicConditionType = $record->chronic_condition_type?->value ?? 'asthma';
                $this->chronicIllnessDetails = $record->chronic_illness_details;
                $this->emergencyProtocolNotes = $record->emergency_protocol_notes;
                $this->emergencyMedicationAtSchool = (bool) $record->emergency_medication_at_school;
            } else {
                $this->hasChronicIllness = true;
                $this->chronicConditionType = 'asthma';
                $this->chronicIllnessDetails = null;
                $this->emergencyProtocolNotes = null;
                $this->emergencyMedicationAtSchool = false;
            }
        }
        $this->isChronicModalOpen = true;
    }

    public function saveChronicCondition(SchoolHealthService $service): void
    {
        $this->validate([
            'chronicStudentId' => 'required|exists:students,id',
            'chronicConditionType' => 'required|string',
        ]);

        $student = Student::findOrFail($this->chronicStudentId);

        $service->updateChronicCondition($student, [
            'has_chronic_illness' => $this->hasChronicIllness,
            'chronic_condition_type' => $this->chronicConditionType,
            'chronic_illness_details' => $this->chronicIllnessDetails,
            'emergency_protocol_notes' => $this->emergencyProtocolNotes,
            'emergency_medication_at_school' => $this->emergencyMedicationAtSchool,
        ], $this->academicYear);

        $this->isChronicModalOpen = false;

        Notification::make()
            ->title(__('تم تحديث ملف الحالة الصحية وبروتوكول الطوارئ (PAI)'))
            ->success()
            ->send();
    }

    public function resetScreeningForm(): void
    {
        $this->screeningStudentId = null;
        $this->screeningDate = now()->toDateString();
        $this->heightCm = null;
        $this->weightKg = null;
        $this->bloodType = 'unknown';
        $this->visualAcuityRight = '10/10';
        $this->visualAcuityLeft = '10/10';
        $this->wearsGlasses = false;
        $this->hearingCondition = 'normal';
        $this->dentalCariesCount = 0;
        $this->dentalHygiene = 'good';
        $this->generalClinicalNotes = null;
        $this->followUpRequired = false;
        $this->specialistReferral = null;
    }

    public function getStudentsProperty()
    {
        $query = Student::with(['group.classroom.level', 'medicalRecords' => fn ($q) => $q->where('academic_year', $this->academicYear)]);

        if ($this->selectedLevelId) {
            $query->whereHas('group.classroom', fn ($q) => $q->where('level_id', $this->selectedLevelId));
        }

        if ($this->searchQuery) {
            $term = '%'.$this->searchQuery.'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('registration_number', 'like', $term);
            });
        }

        if ($this->selectedFilterStatus === 'screened') {
            $query->whereHas('medicalRecords', fn ($q) => $q->where('academic_year', $this->academicYear)->whereNotNull('screening_date'));
        } elseif ($this->selectedFilterStatus === 'unscreened') {
            $query->whereDoesntHave('medicalRecords', fn ($q) => $q->where('academic_year', $this->academicYear)->whereNotNull('screening_date'));
        } elseif ($this->selectedFilterStatus === 'chronic') {
            $query->whereHas('medicalRecords', fn ($q) => $q->where('academic_year', $this->academicYear)->where('has_chronic_illness', true));
        } elseif ($this->selectedFilterStatus === 'exempted') {
            $query->whereHas('medicalRecords', fn ($q) => $q->where('academic_year', $this->academicYear)->where('eps_dispensation_status', '!=', 'fit'));
        }

        return $query->paginate(15);
    }

    public function getChronicStudentsProperty()
    {
        return Student::with(['group.classroom.level', 'medicalRecords' => fn ($q) => $q->where('academic_year', $this->academicYear)])
            ->whereHas('medicalRecords', fn ($q) => $q->where('academic_year', $this->academicYear)->where('has_chronic_illness', true))
            ->get();
    }

    public function getEpsExemptStudentsProperty()
    {
        return Student::with(['group.classroom.level', 'medicalRecords' => fn ($q) => $q->where('academic_year', $this->academicYear)])
            ->whereHas('medicalRecords', fn ($q) => $q->where('academic_year', $this->academicYear)->where('eps_dispensation_status', '!=', 'fit'))
            ->get();
    }

    public function getLevelsProperty()
    {
        return Level::orderBy('id')->get();
    }
}
