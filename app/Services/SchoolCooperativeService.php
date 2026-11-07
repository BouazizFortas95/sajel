<?php

namespace App\Services;

use App\Enums\CooperativeProjectStatus;
use App\Enums\CooperativeTransactionType;
use App\Enums\GardenIrrigationType;
use App\Enums\GardenPlotStatus;
use App\Models\BotanicalPlantsInventory;
use App\Models\CooperativeFinancialLedger;
use App\Models\CooperativeProjectsInitiative;
use App\Models\EcoGreenClubAction;
use App\Models\PedagogicalGardenPlot;
use App\Models\SchoolCooperative;
use App\Models\SchoolSetting;
use Carbon\Carbon;

class SchoolCooperativeService
{
    public function currentAcademicYear(): string
    {
        $now = Carbon::now();
        $year = $now->year;

        return $now->month >= 9 ? "{$year}/".($year + 1) : ($year - 1)."/{$year}";
    }

    public function generateProjectCode(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = CooperativeProjectsInitiative::where('academic_year', $year)->count() + 1;

        return sprintf('COOP-PRJ-%s-%03d', $cleanYear, $count);
    }

    public function generateTransactionReference(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = CooperativeFinancialLedger::where('academic_year', $year)->count() + 1;

        return sprintf('COOP-FIN-%s-%03d', $cleanYear, $count);
    }

    public function generateGardenPlotCode(): string
    {
        $count = PedagogicalGardenPlot::count() + 1;

        return sprintf('PLOT-%02d', $count);
    }

    public function generatePlantCode(): string
    {
        $count = BotanicalPlantsInventory::count() + 1;

        return sprintf('PLT-%03d', $count);
    }

    public function generateEcoActionCode(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = EcoGreenClubAction::where('academic_year', $year)->count() + 1;

        return sprintf('ECO-ACT-%s-%03d', $cleanYear, $count);
    }

    public function getOrCreateCooperative(?string $academicYear = null): SchoolCooperative
    {
        $year = $academicYear ?? $this->currentAcademicYear();

        return SchoolCooperative::firstOrCreate(
            ['academic_year' => $year],
            [
                'cooperative_name' => 'التعاونية المدرسية والنادي البيئي الأخضر',
                'approval_decree_number' => 'DEC-COOP-'.str_replace('/', '-', $year),
                'foundation_date' => Carbon::now()->subMonths(2),
                'motto_or_slogan' => 'بالتعاون والإبداع، نبني مدرسة خضراء متضامنة ومنتجة',
                'student_president_name' => 'التلميذ(ة) رئيس مكتب التعاونية',
                'student_vice_president_name' => 'التلميذ(ة) نائب الرئيس',
                'student_secretary_name' => 'التلميذ(ة) الكاتب العام',
                'student_treasurer_name' => 'التلميذ(ة) أمين المال',
                'total_members_count' => 45,
                'treasury_balance' => 0.00,
                'annual_action_plan' => 'خطة العمل السنوية: حملات التشجير، تسيير الحديقة البيداغوجية، معارض الرسم والمشغولات، وحملات التضامن المدرسي.',
                'is_active' => true,
            ]
        );
    }

    public function createProject(array $data): CooperativeProjectsInitiative
    {
        $year = $data['academic_year'] ?? $this->currentAcademicYear();
        $cooperative = $this->getOrCreateCooperative($year);

        if (empty($data['project_code'])) {
            $data['project_code'] = $this->generateProjectCode($year);
        }

        $data['academic_year'] = $year;
        $data['school_cooperative_id'] = $cooperative->id;

        return CooperativeProjectsInitiative::create($data);
    }

    public function updateProjectStatus(
        int $projectId,
        CooperativeProjectStatus|string $status,
        ?float $budgetSpent = null,
        ?string $achievementsNotes = null
    ): CooperativeProjectsInitiative {
        $project = CooperativeProjectsInitiative::findOrFail($projectId);

        $update = ['status' => $status];
        if ($budgetSpent !== null) {
            $update['budget_spent'] = $budgetSpent;
        }
        if ($achievementsNotes !== null) {
            $update['achievements_notes'] = $achievementsNotes;
        }

        $project->update($update);

        return $project->fresh();
    }

    public function recordFinancialTransaction(array $data): CooperativeFinancialLedger
    {
        $year = $data['academic_year'] ?? $this->currentAcademicYear();
        $cooperative = $this->getOrCreateCooperative($year);

        if (empty($data['transaction_reference'])) {
            $data['transaction_reference'] = $this->generateTransactionReference($year);
        }

        $data['academic_year'] = $year;
        $data['school_cooperative_id'] = $cooperative->id;

        $transaction = CooperativeFinancialLedger::create($data);

        // Update cooperative treasury balance
        $txType = $transaction->transaction_type instanceof CooperativeTransactionType
            ? $transaction->transaction_type
            : CooperativeTransactionType::tryFrom($transaction->transaction_type);

        $amount = (float) $transaction->amount_dzd;
        if ($txType && $txType->isIncome()) {
            $cooperative->increment('treasury_balance', $amount);
        } else {
            $cooperative->decrement('treasury_balance', $amount);
        }

        return $transaction;
    }

    public function createGardenPlot(array $data): PedagogicalGardenPlot
    {
        if (empty($data['plot_code'])) {
            $data['plot_code'] = $this->generateGardenPlotCode();
        }

        return PedagogicalGardenPlot::create($data);
    }

    public function bootstrapDefaultGardenPlots(): int
    {
        $plots = [
            [
                'plot_code' => 'PLOT-01',
                'plot_name' => 'حوض النباتات العطرية والطبية',
                'surface_area_sqm' => 45.00,
                'soil_type' => 'تربة خصبة مزيجة مع سماد عضوي',
                'irrigation_type' => GardenIrrigationType::DripIrrigation,
                'location_in_school' => 'الفناء الجنوبي بجانب الجناح العلمي',
                'status' => GardenPlotStatus::ActivePlanted,
                'notes' => 'مخصص لزراعة إكليل الجبل، النعناع، المريمية، والزعتر لأغراض التجارب المخبرية في العلوم الطبيعية.',
            ],
            [
                'plot_code' => 'PLOT-02',
                'plot_name' => 'مشتلة أشجار الزيتون والحمضيات',
                'surface_area_sqm' => 80.00,
                'soil_type' => 'تربة طينية رملية جيدة التصريف',
                'irrigation_type' => GardenIrrigationType::DripIrrigation,
                'location_in_school' => 'المحيط الشرقي للمؤسسة',
                'status' => GardenPlotStatus::ActivePlanted,
                'notes' => 'غرس شتلات الزيتون وأشجار الليمون والبرتقال ضمن مبادرة شجرة لكل فوج تربوي.',
            ],
            [
                'plot_code' => 'PLOT-03',
                'plot_name' => 'رقعة المحاصيل والخضروات البيداغوجية',
                'surface_area_sqm' => 60.00,
                'soil_type' => 'تربة طميية سوداء مخصبة',
                'irrigation_type' => GardenIrrigationType::Sprinkler,
                'location_in_school' => 'الجهة الخلفية للمطعم المدرسي',
                'status' => GardenPlotStatus::PreparationTillage,
                'notes' => 'تجارب زراعة الخس، الجزر، البازلاء، والطماطم لتعريف التلاميذ بدورة حياة النبات والزراعة البيولوجية.',
            ],
            [
                'plot_code' => 'PLOT-04',
                'plot_name' => 'حديقة الأزهار ونباتات التزيين',
                'surface_area_sqm' => 35.00,
                'soil_type' => 'تربة زراعية مصفاة مع بيتموس',
                'irrigation_type' => GardenIrrigationType::ManualWatering,
                'location_in_school' => 'المدخل الشرفي للإدارة والساحة المركزية',
                'status' => GardenPlotStatus::ActivePlanted,
                'notes' => 'نباتات الورد الجوري، القرنفل، واللبلاب الأخضر لتزيين مدخل المؤسسة وتنمية الحس الجمالي.',
            ],
        ];

        $created = 0;
        foreach ($plots as $p) {
            $exists = PedagogicalGardenPlot::where('plot_code', $p['plot_code'])->exists();
            if (! $exists) {
                PedagogicalGardenPlot::create($p);
                $created++;
            }
        }

        return $created;
    }

    public function addBotanicalPlant(array $data): BotanicalPlantsInventory
    {
        if (empty($data['plant_code'])) {
            $data['plant_code'] = $this->generatePlantCode();
        }

        return BotanicalPlantsInventory::create($data);
    }

    public function recordEcoAction(array $data): EcoGreenClubAction
    {
        $year = $data['academic_year'] ?? $this->currentAcademicYear();
        if (empty($data['action_code'])) {
            $data['action_code'] = $this->generateEcoActionCode($year);
        }

        $data['academic_year'] = $year;

        return EcoGreenClubAction::create($data);
    }

    public function getCooperativeTelemetry(?string $academicYear = null): array
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cooperative = $this->getOrCreateCooperative($year);

        $projects = CooperativeProjectsInitiative::where('academic_year', $year)->get();
        $activeProjects = $projects->where('status', CooperativeProjectStatus::Active)->count();
        $completedProjects = $projects->where('status', CooperativeProjectStatus::Completed)->count();
        $totalProjects = $projects->count();

        $transactions = CooperativeFinancialLedger::where('academic_year', $year)->get();
        $incomeTotal = 0.00;
        $expenseTotal = 0.00;

        foreach ($transactions as $t) {
            $type = $t->transaction_type instanceof CooperativeTransactionType
                ? $t->transaction_type
                : CooperativeTransactionType::tryFrom($t->transaction_type);

            $amount = (float) $t->amount_dzd;
            if ($type && $type->isIncome()) {
                $incomeTotal += $amount;
            } else {
                $expenseTotal += $amount;
            }
        }

        $gardenPlotsCount = PedagogicalGardenPlot::count();
        $totalAreaSqm = (float) PedagogicalGardenPlot::sum('surface_area_sqm');
        $totalPlantsCount = (int) BotanicalPlantsInventory::sum('quantity_planted');

        $ecoActions = EcoGreenClubAction::where('academic_year', $year)->get();
        $ecoActionsCount = $ecoActions->count();
        $treesPlanted = (int) $ecoActions->sum('trees_planted_count');
        $wasteRecycledKg = (float) $ecoActions->sum('waste_collected_kg');
        $ecoParticipants = (int) $ecoActions->sum('participants_count');

        return [
            'academicYear' => $year,
            'cooperativeName' => $cooperative->cooperative_name,
            'totalMembersCount' => $cooperative->total_members_count,
            'treasuryBalanceDzd' => (float) $cooperative->treasury_balance,
            'activeProjectsCount' => $activeProjects,
            'completedProjectsCount' => $completedProjects,
            'totalProjectsCount' => $totalProjects,
            'incomeTotalDzd' => $incomeTotal,
            'expenseTotalDzd' => $expenseTotal,
            'gardenPlotsCount' => $gardenPlotsCount,
            'totalGardenAreaSqm' => $totalAreaSqm,
            'totalPlantsCount' => $totalPlantsCount,
            'ecoActionsCount' => $ecoActionsCount,
            'treesPlanted' => $treesPlanted,
            'wasteRecycledKg' => $wasteRecycledKg,
            'ecoParticipants' => $ecoParticipants,
        ];
    }

    public function assembleCooperativeProjectFicheData(CooperativeProjectsInitiative $project): array
    {
        $cooperative = $project->cooperative ?? $this->getOrCreateCooperative($project->academic_year);
        $setting = SchoolSetting::first();

        return [
            'institution_name' => $setting?->school_name ?? config('app.name', 'مؤسسة سَجِّل التربوية'),
            'wilaya_name' => $setting?->wilaya ?? 'الجزائر',
            'daira_name' => $setting?->daira ?? 'الجزائر الوسطى',
            'academic_year' => $project->academic_year,
            'cooperative' => $cooperative,
            'project' => $project,
            'coordinator_name' => $project->coordinator?->name ?? 'الأستاذ المؤطر المنسق',
            'generated_at' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleBotanicalGardenFicheData(PedagogicalGardenPlot $plot): array
    {
        $setting = SchoolSetting::first();
        $plot->load(['plants', 'assignedClassroom', 'supervisingTeacher']);

        return [
            'institution_name' => $setting?->school_name ?? config('app.name', 'مؤسسة سَجِّل التربوية'),
            'wilaya_name' => $setting?->wilaya ?? 'الجزائر',
            'academic_year' => $this->currentAcademicYear(),
            'plot' => $plot,
            'plants' => $plot->plants,
            'supervising_teacher' => $plot->supervisingTeacher?->name ?? 'أستاذ مادة علوم الطبيعة والحياة',
            'assigned_classroom' => $plot->assignedClassroom?->name ?? 'فوج بيداغوجي مكلف',
            'generated_at' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleEcoActionReportData(EcoGreenClubAction $action): array
    {
        $setting = SchoolSetting::first();

        return [
            'institution_name' => $setting?->school_name ?? config('app.name', 'مؤسسة سَجِّل التربوية'),
            'wilaya_name' => $setting?->wilaya ?? 'الجزائر',
            'daira_name' => $setting?->daira ?? 'الجزائر الوسطى',
            'academic_year' => $action->academic_year,
            'action' => $action,
            'supervising_teacher' => $action->supervisingTeacher?->name ?? 'الأستاذ منسق النادي الأخضر',
            'generated_at' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleCooperativeAnnualBilanData(?string $academicYear = null): array
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cooperative = $this->getOrCreateCooperative($year);
        $setting = SchoolSetting::first();

        $projects = CooperativeProjectsInitiative::where('academic_year', $year)->get();
        $transactions = CooperativeFinancialLedger::where('academic_year', $year)->orderBy('transaction_date', 'asc')->get();
        $ecoActions = EcoGreenClubAction::where('academic_year', $year)->get();
        $plots = PedagogicalGardenPlot::with('plants')->get();

        $telemetry = $this->getCooperativeTelemetry($year);

        return [
            'institution_name' => $setting?->school_name ?? config('app.name', 'مؤسسة سَجِّل التربوية'),
            'wilaya_name' => $setting?->wilaya ?? 'الجزائر',
            'academic_year' => $year,
            'cooperative' => $cooperative,
            'projects' => $projects,
            'transactions' => $transactions,
            'eco_actions' => $ecoActions,
            'plots' => $plots,
            'telemetry' => $telemetry,
            'generated_at' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }
}
