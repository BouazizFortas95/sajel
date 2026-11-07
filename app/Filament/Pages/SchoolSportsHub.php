<?php

namespace App\Filament\Pages;

use App\Enums\AscsDisciplineCategory;
use App\Enums\AscsTransactionType;
use App\Enums\SportsCompetitionLevel;
use App\Enums\SportsEquipmentCondition;
use App\Enums\SportsGenderTarget;
use App\Enums\SportsLicenseType;
use App\Enums\SportsMatchResult;
use App\Enums\SportsMedicalAptitude;
use App\Enums\StudentAgeCategory;
use App\Models\AscsFinancialTransaction;
use App\Models\SportsCompetitionEvent;
use App\Models\SportsCulturalDiscipline;
use App\Models\SportsEquipmentInventory;
use App\Models\Student;
use App\Models\StudentSportsLicense;
use App\Models\User;
use App\Services\SchoolSportsService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolSportsHub extends Page
{
    protected static string $view = 'filament.pages.school-sports-hub';

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 7;

    public string $activeTab = 'licenses'; // licenses | disciplines | competitions | finances_equipment

    public string $academicYear = '2026/2027';

    public string $searchQuery = '';

    public string $disciplineFilter = 'all';

    public string $licenseTypeFilter = 'all';

    // ─── Modal State: Issue License ─────────────────────────────
    public bool $isLicenseModalOpen = false;

    public ?int $selectedStudentId = null;

    public ?int $selectedDisciplineId = null;

    public string $licenseType = 'competitive';

    public string $medicalAptitude = 'fit_apt';

    public bool $parentalConsent = true;

    public bool $insuranceCovered = true;

    public bool $feePaid = true;

    public string $licenseNotes = '';

    // ─── Modal State: Add Discipline ────────────────────────────
    public bool $isDisciplineModalOpen = false;

    public string $disciplineNameAr = '';

    public string $disciplineNameFr = '';

    public string $disciplineCategory = 'team_sport';

    public string $supervisingTeacher = '';

    public string $trainingSchedule = 'الثلاثاء مساء / الجمعة صباحاً';

    public string $targetGenders = 'mixed';

    public string $ageCategory = 'all';

    public int $maxParticipants = 30;

    // ─── Modal State: Record Match Event ────────────────────────
    public bool $isEventModalOpen = false;

    public ?int $eventDisciplineId = null;

    public string $eventName = '';

    public string $competitionLevel = 'daira_championship';

    public string $eventDate = '';

    public string $eventTime = '09:30';

    public string $locationVenue = 'القاعة متعددة الرياضات';

    public string $opponentTeam = '';

    public ?int $ourScore = 3;

    public ?int $opponentScore = 1;

    public string $matchResult = 'win';

    public string $rankingAchieved = '';

    public int $fairPlayScore = 10;

    public string $chaperonesList = '';

    public string $participationNotes = '';

    // ─── Modal State: Record ASCS Transaction ───────────────────
    public bool $isTransactionModalOpen = false;

    public string $transactionType = 'income_subscription';

    public float $transactionAmount = 5000.0;

    public string $transactionDate = '';

    public string $paymentMethod = 'Cash';

    public string $receiptRef = '';

    public string $transactionDescription = '';

    // ─── Modal State: Add Sports Equipment ──────────────────────
    public bool $isEquipmentModalOpen = false;

    public string $equipmentName = '';

    public ?int $equipmentDisciplineId = null;

    public int $quantityTotal = 10;

    public string $equipmentCondition = 'good';

    public string $storageLocation = 'مخزن التربية البدنية والرياضية';

    public string $acquisitionSource = 'PurchasedASCS';

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('الجمعية الرياضية والثقافية (ASCS)');
    }

    public function getTitle(): string|Htmlable
    {
        return __('الجمعية الثقافية والرياضية المدرسية (ASCS) والمنافسات');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSchoolSports();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->eventDate = $today;
        $this->transactionDate = $today;

        $user = Auth::user();
        $this->supervisingTeacher = $user?->name ?? 'أستاذ منسق مادة التربية البدنية والرياضية';
        $this->chaperonesList = $this->supervisingTeacher;

        // Auto initialize default ASCS disciplines and association
        $service = app(SchoolSportsService::class);
        $service->getOrCreateAssociation($this->academicYear);
        $service->bootstrapDefaultDisciplines();

        $firstDiscipline = SportsCulturalDiscipline::first();
        if ($firstDiscipline) {
            $this->selectedDisciplineId = $firstDiscipline->id;
            $this->eventDisciplineId = $firstDiscipline->id;
            $this->equipmentDisciplineId = $firstDiscipline->id;
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openLicenseModal(): void
    {
        $this->isLicenseModalOpen = true;
    }

    public function closeLicenseModal(): void
    {
        $this->isLicenseModalOpen = false;
    }

    public function openDisciplineModal(): void
    {
        $this->isDisciplineModalOpen = true;
    }

    public function closeDisciplineModal(): void
    {
        $this->isDisciplineModalOpen = false;
    }

    public function openEventModal(): void
    {
        $this->isEventModalOpen = true;
    }

    public function closeEventModal(): void
    {
        $this->isEventModalOpen = false;
    }

    public function openTransactionModal(): void
    {
        $this->isTransactionModalOpen = true;
    }

    public function closeTransactionModal(): void
    {
        $this->isTransactionModalOpen = false;
    }

    public function openEquipmentModal(): void
    {
        $this->isEquipmentModalOpen = true;
    }

    public function closeEquipmentModal(): void
    {
        $this->isEquipmentModalOpen = false;
    }

    public function saveLicense(): void
    {
        $this->validate([
            'selectedStudentId' => 'required|integer',
            'selectedDisciplineId' => 'required|integer',
        ]);

        $service = app(SchoolSportsService::class);
        $license = $service->issueStudentLicense([
            'academic_year' => $this->academicYear,
            'student_id' => $this->selectedStudentId,
            'discipline_id' => $this->selectedDisciplineId,
            'license_type' => SportsLicenseType::from($this->licenseType),
            'medical_aptitude_verdict' => SportsMedicalAptitude::from($this->medicalAptitude),
            'parental_consent_signed' => $this->parentalConsent,
            'insurance_covered' => $this->insuranceCovered,
            'fee_paid' => $this->feePaid,
            'notes' => $this->licenseNotes ?: null,
        ]);

        $this->closeLicenseModal();

        Notification::make()
            ->title(__('تم إصدار الإجازة الرياضية بنجاح'))
            ->body(__('رقم الإجازة: :num', ['num' => $license->license_number]))
            ->success()
            ->send();
    }

    public function saveDiscipline(): void
    {
        $this->validate([
            'disciplineNameAr' => 'required|string|max:255',
            'supervisingTeacher' => 'required|string|max:255',
        ]);

        $service = app(SchoolSportsService::class);
        $discipline = $service->createDiscipline([
            'name_ar' => $this->disciplineNameAr,
            'name_fr' => $this->disciplineNameFr ?: null,
            'category' => AscsDisciplineCategory::from($this->disciplineCategory),
            'supervising_teacher_name' => $this->supervisingTeacher,
            'training_schedule' => $this->trainingSchedule,
            'target_genders' => SportsGenderTarget::from($this->targetGenders),
            'age_category' => StudentAgeCategory::from($this->ageCategory),
            'max_participants' => $this->maxParticipants,
        ]);

        $this->closeDisciplineModal();

        Notification::make()
            ->title(__('تم اعتماد النشاط / الاختصاص الرياضي'))
            ->body(__('الرمز: :code — :name', ['code' => $discipline->discipline_code, 'name' => $discipline->name_ar]))
            ->success()
            ->send();
    }

    public function saveEvent(): void
    {
        $this->validate([
            'eventName' => 'required|string|max:255',
            'locationVenue' => 'required|string|max:255',
        ]);

        $service = app(SchoolSportsService::class);
        $event = $service->recordCompetitionEvent([
            'academic_year' => $this->academicYear,
            'discipline_id' => $this->eventDisciplineId,
            'event_name' => $this->eventName,
            'competition_level' => SportsCompetitionLevel::from($this->competitionLevel),
            'event_date' => $this->eventDate,
            'event_time' => $this->eventTime,
            'location_venue' => $this->locationVenue,
            'opponent_team_name' => $this->opponentTeam ?: null,
            'our_score' => $this->ourScore,
            'opponent_score' => $this->opponentScore,
            'match_result' => SportsMatchResult::from($this->matchResult),
            'ranking_achieved' => $this->rankingAchieved ?: null,
            'fair_play_score' => $this->fairPlayScore,
            'chaperones_list' => $this->chaperonesList,
            'participation_notes' => $this->participationNotes ?: null,
        ]);

        $this->closeEventModal();

        Notification::make()
            ->title(__('تم تسجيل المقابلة والمشاركة الرياضية'))
            ->body(__('الرمز: :code — :name', ['code' => $event->event_code, 'name' => $event->event_name]))
            ->success()
            ->send();
    }

    public function saveTransaction(): void
    {
        $this->validate([
            'transactionDescription' => 'required|string|max:255',
            'transactionAmount' => 'required|numeric|min:1',
        ]);

        $service = app(SchoolSportsService::class);
        $user = Auth::user();

        $transaction = $service->recordFinancialTransaction([
            'academic_year' => $this->academicYear,
            'transaction_type' => AscsTransactionType::from($this->transactionType),
            'amount_dzd' => $this->transactionAmount,
            'transaction_date' => $this->transactionDate,
            'payment_method' => $this->paymentMethod,
            'invoice_or_receipt_ref' => $this->receiptRef ?: null,
            'description' => $this->transactionDescription,
            'recorded_by_name' => $user?->name ?? 'أمين مال الجمعية',
        ]);

        $this->closeTransactionModal();

        Notification::make()
            ->title(__('تم قيد العملية المالية بالخزينة'))
            ->body(__('المرجع: :ref — :amount دج', ['ref' => $transaction->transaction_reference, 'amount' => number_format((float) $transaction->amount_dzd, 2)]))
            ->success()
            ->send();
    }

    public function saveEquipment(): void
    {
        $this->validate([
            'equipmentName' => 'required|string|max:255',
            'quantityTotal' => 'required|integer|min:1',
        ]);

        $service = app(SchoolSportsService::class);
        $equipment = $service->addOrUpdateSportsEquipment([
            'item_name' => $this->equipmentName,
            'discipline_id' => $this->equipmentDisciplineId,
            'quantity_total' => $this->quantityTotal,
            'quantity_operational' => $this->quantityTotal,
            'condition_rating' => SportsEquipmentCondition::from($this->equipmentCondition),
            'storage_location' => $this->storageLocation,
            'acquisition_source' => $this->acquisitionSource,
        ]);

        $this->closeEquipmentModal();

        Notification::make()
            ->title(__('تم تسجيل العتاد الرياضي بالمخزن'))
            ->body(__('الرمز: :code — :name', ['code' => $equipment->equipment_code, 'name' => $equipment->item_name]))
            ->success()
            ->send();
    }

    public function getTelemetryProperty(): array
    {
        return app(SchoolSportsService::class)->getSportsTelemetry($this->academicYear);
    }

    public function getLicensesProperty()
    {
        $query = StudentSportsLicense::with(['student.group.classroom', 'discipline'])
            ->where('academic_year', $this->academicYear);

        if ($this->disciplineFilter !== 'all') {
            $query->where('discipline_id', $this->disciplineFilter);
        }

        if ($this->licenseTypeFilter !== 'all') {
            $query->where('license_type', $this->licenseTypeFilter);
        }

        if (! empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('license_number', 'like', "%{$this->searchQuery}%")
                    ->orWhereHas('student', function ($sq) {
                        $sq->where('first_name', 'like', "%{$this->searchQuery}%")
                            ->orWhere('last_name', 'like', "%{$this->searchQuery}%");
                    });
            });
        }

        return $query->latest('issue_date')->get();
    }

    public function getDisciplinesProperty()
    {
        return SportsCulturalDiscipline::withCount(['licenses' => function ($q) {
            $q->where('academic_year', $this->academicYear);
        }])->get();
    }

    public function getEventsProperty()
    {
        return SportsCompetitionEvent::with('discipline')
            ->where('academic_year', $this->academicYear)
            ->latest('event_date')
            ->get();
    }

    public function getTransactionsProperty()
    {
        return AscsFinancialTransaction::where('academic_year', $this->academicYear)
            ->latest('transaction_date')
            ->get();
    }

    public function getEquipmentProperty()
    {
        return SportsEquipmentInventory::with('discipline')->latest()->get();
    }

    public function getStudentsProperty()
    {
        return Student::with('group.classroom')->orderBy('last_name')->limit(150)->get();
    }
}

