<?php

namespace App\Filament\Pages;

use App\Enums\BotanicalPlantCategory;
use App\Enums\CooperativeProjectStatus;
use App\Enums\CooperativeProjectType;
use App\Enums\CooperativeTransactionType;
use App\Enums\EcoActionCategory;
use App\Enums\GardenIrrigationType;
use App\Enums\GardenPlotStatus;
use App\Enums\PlantHealthStatus;
use App\Models\Classroom;
use App\Models\CooperativeFinancialLedger;
use App\Models\CooperativeProjectsInitiative;
use App\Models\EcoGreenClubAction;
use App\Models\PedagogicalGardenPlot;
use App\Models\SchoolCooperative;
use App\Models\User;
use App\Services\SchoolCooperativeService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolCooperativeHub extends Page
{
    protected static string $view = 'filament.pages.school-cooperative-hub';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 8;

    public string $activeTab = 'projects'; // projects | garden | eco_club | treasury

    public string $academicYear = '2026/2027';

    public string $searchQuery = '';

    public string $projectTypeFilter = 'all';

    public string $projectStatusFilter = 'all';

    // ─── Modal State: Add Cooperative Project ───────────────────
    public bool $isProjectModalOpen = false;

    public string $projectTitle = '';

    public string $projectType = 'solidarity_charity';

    public string $targetAudience = 'تلاميذ المؤسسة والمعوزون';

    public string $startDate = '';

    public string $endDate = '';

    public float $budgetAllocated = 15000.00;

    public string $studentLeadsSummary = 'لجنة تلاميذية برئاسة مكتب التعاونية المدرسية وتنسيق الأستاذ المشرف.';

    // ─── Modal State: Add Garden Plot ───────────────────────────
    public bool $isPlotModalOpen = false;

    public string $plotName = '';

    public float $surfaceArea = 40.00;

    public string $soilType = 'تربة خصبة زراعية مخصبة بالسماد العضوي';

    public string $irrigationType = 'drip_irrigation';

    public string $locationInSchool = 'الفناء الخلفي بجوار الجناح العلمي';

    public ?int $assignedClassroomId = null;

    public string $plotNotes = '';

    // ─── Modal State: Add Botanical Plant ───────────────────────
    public bool $isPlantModalOpen = false;

    public ?int $selectedPlotId = null;

    public string $commonNameAr = '';

    public string $commonNameFr = '';

    public string $botanicalName = '';

    public string $plantCategory = 'aromatic_medicinal';

    public int $quantityPlanted = 10;

    public string $plantingDate = '';

    public string $healthStatus = 'thriving_good';

    public string $plantNotes = '';

    // ─── Modal State: Record Eco Action ─────────────────────────
    public bool $isEcoActionModalOpen = false;

    public string $ecoTitle = '';

    public string $ecoCategory = 'tree_planting';

    public string $executionDate = '';

    public int $participantsCount = 35;

    public int $treesPlantedCount = 15;

    public float $wasteCollectedKg = 0.00;

    public string $locationDetails = 'ساحات وأفنية المؤسسة والمحيط الخارجي';

    public string $partnerOrganization = 'محافظة الغابات والبلدية APC';

    public string $impactSummary = '';

    // ─── Modal State: Record Financial Entry ────────────────────
    public bool $isTransactionModalOpen = false;

    public string $transactionDate = '';

    public string $transactionType = 'income_membership_dues';

    public float $transactionAmount = 5000.00;

    public string $beneficiaryOrSource = 'اشتراكات سنوية للتلاميذ الأعضاء';

    public string $paymentMethod = 'Cash';

    public string $receiptNumber = '';

    public string $transactionDescription = '';

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('التعاونية المدرسية والبيئة');
    }

    public function getTitle(): string|Htmlable
    {
        return __('منظومة التعاونية المدرسية، الحديقة البيداغوجية والنوادي الخضراء');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSchoolCooperative();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->startDate = $today;
        $this->plantingDate = $today;
        $this->executionDate = $today;
        $this->transactionDate = $today;

        $service = app(SchoolCooperativeService::class);
        $service->getOrCreateCooperative($this->academicYear);
        $service->bootstrapDefaultGardenPlots();

        $firstPlot = PedagogicalGardenPlot::first();
        if ($firstPlot) {
            $this->selectedPlotId = $firstPlot->id;
        }

        $firstClassroom = Classroom::first();
        if ($firstClassroom) {
            $this->assignedClassroomId = $firstClassroom->id;
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openProjectModal(): void
    {
        $this->isProjectModalOpen = true;
    }

    public function closeProjectModal(): void
    {
        $this->isProjectModalOpen = false;
    }

    public function openPlotModal(): void
    {
        $this->isPlotModalOpen = true;
    }

    public function closePlotModal(): void
    {
        $this->isPlotModalOpen = false;
    }

    public function openPlantModal(?int $plotId = null): void
    {
        if ($plotId) {
            $this->selectedPlotId = $plotId;
        }
        $this->isPlantModalOpen = true;
    }

    public function closePlantModal(): void
    {
        $this->isPlantModalOpen = false;
    }

    public function openEcoActionModal(): void
    {
        $this->isEcoActionModalOpen = true;
    }

    public function closeEcoActionModal(): void
    {
        $this->isEcoActionModalOpen = false;
    }

    public function openTransactionModal(): void
    {
        $this->isTransactionModalOpen = true;
    }

    public function closeTransactionModal(): void
    {
        $this->isTransactionModalOpen = false;
    }

    public function saveProject(): void
    {
        $this->validate([
            'projectTitle' => 'required|string|min:3|max:200',
            'projectType' => 'required|string',
            'startDate' => 'required|date',
            'budgetAllocated' => 'required|numeric|min:0',
        ]);

        $service = app(SchoolCooperativeService::class);
        $project = $service->createProject([
            'academic_year' => $this->academicYear,
            'title' => $this->projectTitle,
            'project_type' => CooperativeProjectType::from($this->projectType),
            'target_audience' => $this->targetAudience,
            'start_date' => $this->startDate,
            'end_date' => ! empty($this->endDate) ? $this->endDate : null,
            'budget_allocated' => $this->budgetAllocated,
            'budget_spent' => 0.00,
            'status' => CooperativeProjectStatus::Active,
            'coordinator_user_id' => Auth::id(),
            'student_leads_summary' => $this->studentLeadsSummary,
        ]);

        $this->closeProjectModal();
        $this->projectTitle = '';

        Notification::make()
            ->title(__('تم اعتماد مشروع ومبادرة التعاونية المدرسية'))
            ->body(__('الرمز: :code — :name', ['code' => $project->project_code, 'name' => $project->title]))
            ->success()
            ->send();
    }

    public function savePlot(): void
    {
        $this->validate([
            'plotName' => 'required|string|min:3|max:150',
            'surfaceArea' => 'required|numeric|min:1',
            'irrigationType' => 'required|string',
        ]);

        $service = app(SchoolCooperativeService::class);
        $plot = $service->createGardenPlot([
            'plot_name' => $this->plotName,
            'surface_area_sqm' => $this->surfaceArea,
            'soil_type' => $this->soilType,
            'irrigation_type' => GardenIrrigationType::from($this->irrigationType),
            'location_in_school' => $this->locationInSchool,
            'assigned_classroom_id' => $this->assignedClassroomId,
            'supervising_teacher_id' => Auth::id(),
            'status' => GardenPlotStatus::ActivePlanted,
            'notes' => $this->plotNotes,
        ]);

        $this->closePlotModal();
        $this->plotName = '';

        Notification::make()
            ->title(__('تم تهيئة وتسجيل الحوض البيداغوجي بالحديقة'))
            ->body(__('الرمز: :code — :name', ['code' => $plot->plot_code, 'name' => $plot->plot_name]))
            ->success()
            ->send();
    }

    public function savePlant(): void
    {
        $this->validate([
            'selectedPlotId' => 'required|exists:pedagogical_garden_plots,id',
            'commonNameAr' => 'required|string|min:2|max:100',
            'quantityPlanted' => 'required|integer|min:1',
            'plantCategory' => 'required|string',
        ]);

        $service = app(SchoolCooperativeService::class);
        $plant = $service->addBotanicalPlant([
            'pedagogical_garden_plot_id' => $this->selectedPlotId,
            'common_name_ar' => $this->commonNameAr,
            'common_name_fr' => $this->commonNameFr,
            'botanical_name' => $this->botanicalName,
            'plant_category' => BotanicalPlantCategory::from($this->plantCategory),
            'quantity_planted' => $this->quantityPlanted,
            'planting_date' => $this->plantingDate,
            'health_status' => PlantHealthStatus::from($this->healthStatus),
            'notes' => $this->plantNotes,
        ]);

        $this->closePlantModal();
        $this->commonNameAr = '';
        $this->botanicalName = '';

        Notification::make()
            ->title(__('تم تسجيل الصنف النباتي بجدول الحوض'))
            ->body(__('الرمز: :code — :name', ['code' => $plant->plant_code, 'name' => $plant->common_name_ar]))
            ->success()
            ->send();
    }

    public function saveEcoAction(): void
    {
        $this->validate([
            'ecoTitle' => 'required|string|min:3|max:200',
            'ecoCategory' => 'required|string',
            'executionDate' => 'required|date',
            'participantsCount' => 'required|integer|min:1',
        ]);

        $service = app(SchoolCooperativeService::class);
        $action = $service->recordEcoAction([
            'academic_year' => $this->academicYear,
            'action_title' => $this->ecoTitle,
            'action_category' => EcoActionCategory::from($this->ecoCategory),
            'execution_date' => $this->executionDate,
            'participants_count' => $this->participantsCount,
            'trees_planted_count' => $this->treesPlantedCount,
            'waste_collected_kg' => $this->wasteCollectedKg,
            'supervising_teacher_id' => Auth::id(),
            'location_details' => $this->locationDetails,
            'partner_organization' => $this->partnerOrganization,
            'impact_summary' => $this->impactSummary,
        ]);

        $this->closeEcoActionModal();
        $this->ecoTitle = '';

        Notification::make()
            ->title(__('تم تسجيل النشاط البيئي للنادي الأخضر'))
            ->body(__('الرمز: :code — :name', ['code' => $action->action_code, 'name' => $action->action_title]))
            ->success()
            ->send();
    }

    public function saveTransaction(): void
    {
        $this->validate([
            'transactionDate' => 'required|date',
            'transactionType' => 'required|string',
            'transactionAmount' => 'required|numeric|min:1',
            'beneficiaryOrSource' => 'required|string|min:2',
        ]);

        $service = app(SchoolCooperativeService::class);
        $tx = $service->recordFinancialTransaction([
            'academic_year' => $this->academicYear,
            'transaction_date' => $this->transactionDate,
            'transaction_type' => CooperativeTransactionType::from($this->transactionType),
            'amount_dzd' => $this->transactionAmount,
            'beneficiary_or_source' => $this->beneficiaryOrSource,
            'payment_method' => $this->paymentMethod,
            'receipt_voucher_number' => $this->receiptNumber,
            'recorded_by_user_id' => Auth::id(),
            'description' => $this->transactionDescription,
        ]);

        $this->closeTransactionModal();
        $this->beneficiaryOrSource = '';
        $this->transactionDescription = '';

        Notification::make()
            ->title(__('تم قيد العملية المالية بصندوق التعاونية'))
            ->body(__('المرجع: :ref — :amount دج', ['ref' => $tx->transaction_reference, 'amount' => number_format($tx->amount_dzd, 2)]))
            ->success()
            ->send();
    }

    public function getTelemetryProperty(): array
    {
        return app(SchoolCooperativeService::class)->getCooperativeTelemetry($this->academicYear);
    }

    public function getProjectsProperty()
    {
        $query = CooperativeProjectsInitiative::with(['coordinator', 'cooperative'])
            ->where('academic_year', $this->academicYear);

        if ($this->projectTypeFilter !== 'all') {
            $query->where('project_type', $this->projectTypeFilter);
        }

        if ($this->projectStatusFilter !== 'all') {
            $query->where('status', $this->projectStatusFilter);
        }

        if (! empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->searchQuery}%")
                    ->orWhere('project_code', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->latest('start_date')->get();
    }

    public function getGardenPlotsProperty()
    {
        return PedagogicalGardenPlot::with(['plants', 'assignedClassroom', 'supervisingTeacher'])
            ->orderBy('plot_code')
            ->get();
    }

    public function getEcoActionsProperty()
    {
        return EcoGreenClubAction::with('supervisingTeacher')
            ->where('academic_year', $this->academicYear)
            ->latest('execution_date')
            ->get();
    }

    public function getTransactionsProperty()
    {
        return CooperativeFinancialLedger::with('recordedBy')
            ->where('academic_year', $this->academicYear)
            ->latest('transaction_date')
            ->get();
    }

    public function getClassroomsProperty()
    {
        return Classroom::orderBy('name')->get();
    }

    public function getCooperativeProperty(): ?SchoolCooperative
    {
        return SchoolCooperative::where('academic_year', $this->academicYear)->first();
    }
}
