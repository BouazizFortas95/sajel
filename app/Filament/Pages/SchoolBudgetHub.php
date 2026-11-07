<?php

namespace App\Filament\Pages;

use App\Enums\BudgetType;
use App\Enums\CommitmentStatus;
use App\Enums\MandateStatus;
use App\Models\BudgetCommitment;
use App\Models\PaymentMandate;
use App\Models\SchoolBudgetLine;
use App\Models\User;
use App\Services\SchoolBudgetService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolBudgetHub extends Page
{
    protected static string $view = 'filament.pages.school-budget-hub';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    public string $activeTab = 'lines'; // lines | commitments | mandates

    public int $fiscalYear = 2026;

    public string $budgetTypeFilter = 'all';

    // Modal state: Add Budget Line
    public bool $isLineModalOpen = false;

    public string $lineBudgetType = 'expenditure';

    public string $lineChapter = '';

    public string $lineArticle = '';

    public ?float $lineAllocatedAmount = null;

    public string $lineNotes = '';

    // Modal state: Add Purchase Order / Commitment
    public bool $isCommitmentModalOpen = false;

    public ?int $selectedBudgetLineId = null;

    public string $supplierName = '';

    public string $supplierTaxNumber = '';

    public string $orderDate = '';

    public string $commitmentSubject = '';

    public ?float $commitmentEstimatedAmount = null;

    public string $commitmentNotes = '';

    // Modal state: Add Payment Mandate
    public bool $isMandateModalOpen = false;

    public ?int $selectedCommitmentId = null;

    public string $mandateDate = '';

    public string $invoiceNumber = '';

    public string $invoiceDate = '';

    public string $receptionSlipNumber = '';

    public ?float $mandateLiquidatedAmount = null;

    public string $paymentMethod = 'treasury_transfer';

    public string $paymentReference = '';

    public string $mandateNotes = '';

    public function mount(): void
    {
        $today = now(config('app.timezone', 'Africa/Algiers'));
        $this->fiscalYear = (int) $today->year;
        $this->orderDate = $today->format('Y-m-d');
        $this->mandateDate = $today->format('Y-m-d');
        $this->invoiceDate = $today->format('Y-m-d');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSchoolBudget();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('المصالح المالية والكتاب المدرسي');
    }

    public static function getNavigationLabel(): string
    {
        return __('الميزانية والمحاسبة المدرسية');
    }

    public function getTitle(): string|Htmlable
    {
        return __('تسيير الميزانية والمحاسبة المدرسية وسجل الالتزامات');
    }

    // ─── Budget Grid Actions ────────────────────────────────────

    public function seedStandardGrid(SchoolBudgetService $service): void
    {
        $service->seedStandardBudgetGrid($this->fiscalYear);

        Notification::make()
            ->title(__('تم توليد الأبواب والمواد النموذجية للميزانية بنجاح'))
            ->success()
            ->send();
    }

    public function openLineModal(): void
    {
        $this->isLineModalOpen = true;
    }

    public function closeLineModal(): void
    {
        $this->isLineModalOpen = false;
    }

    public function saveLine(): void
    {
        $this->validate([
            'lineBudgetType' => 'required|string',
            'lineChapter' => 'required|string|max:255',
            'lineArticle' => 'required|string|max:255',
            'lineAllocatedAmount' => 'required|numeric|min:0',
        ]);

        SchoolBudgetLine::updateOrCreate(
            [
                'fiscal_year' => $this->fiscalYear,
                'budget_type' => $this->lineBudgetType,
                'chapter' => $this->lineChapter,
                'article' => $this->lineArticle,
            ],
            [
                'academic_year' => '2026/2027',
                'allocated_amount' => (float) $this->lineAllocatedAmount,
                'notes' => $this->lineNotes ?: null,
            ]
        );

        $this->isLineModalOpen = false;
        $this->reset(['lineChapter', 'lineArticle', 'lineAllocatedAmount', 'lineNotes']);

        Notification::make()
            ->title(__('تم حفظ بند الميزانية بنجاح'))
            ->success()
            ->send();
    }

    // ─── Commitment Actions ─────────────────────────────────────

    public function openCommitmentModal(): void
    {
        $this->isCommitmentModalOpen = true;
    }

    public function closeCommitmentModal(): void
    {
        $this->isCommitmentModalOpen = false;
    }

    public function saveCommitment(SchoolBudgetService $service): void
    {
        $this->validate([
            'selectedBudgetLineId' => 'required|exists:school_budget_lines,id',
            'supplierName' => 'required|string|max:255',
            'orderDate' => 'required|date',
            'commitmentSubject' => 'required|string|max:255',
            'commitmentEstimatedAmount' => 'required|numeric|min:0.01',
        ]);

        $service->recordCommitment([
            'budget_line_id' => $this->selectedBudgetLineId,
            'supplier_name' => $this->supplierName,
            'supplier_tax_number' => $this->supplierTaxNumber ?: null,
            'order_date' => $this->orderDate,
            'subject' => $this->commitmentSubject,
            'estimated_amount' => (float) $this->commitmentEstimatedAmount,
            'notes' => $this->commitmentNotes ?: null,
            'created_by' => Auth::id(),
        ]);

        $this->isCommitmentModalOpen = false;
        $this->reset([
            'selectedBudgetLineId',
            'supplierName',
            'supplierTaxNumber',
            'commitmentSubject',
            'commitmentEstimatedAmount',
            'commitmentNotes',
        ]);

        Notification::make()
            ->title(__('تم تسجيل سند الطلب والالتزام المالي بنجاح'))
            ->success()
            ->send();
    }

    // ─── Mandate Actions ────────────────────────────────────────

    public function openMandateModal(): void
    {
        $this->isMandateModalOpen = true;
    }

    public function closeMandateModal(): void
    {
        $this->isMandateModalOpen = false;
    }

    public function saveMandate(SchoolBudgetService $service): void
    {
        $this->validate([
            'selectedCommitmentId' => 'required|exists:budget_commitments,id',
            'mandateDate' => 'required|date',
            'invoiceNumber' => 'required|string|max:255',
            'invoiceDate' => 'required|date',
            'mandateLiquidatedAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|string',
        ]);

        $service->recordPaymentMandate([
            'commitment_id' => $this->selectedCommitmentId,
            'mandate_date' => $this->mandateDate,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'reception_slip_number' => $this->receptionSlipNumber ?: null,
            'liquidated_amount' => (float) $this->mandateLiquidatedAmount,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference ?: null,
            'status' => MandateStatus::Approved->value,
            'authorized_by' => Auth::id(),
            'notes' => $this->mandateNotes ?: null,
        ]);

        $this->isMandateModalOpen = false;
        $this->reset([
            'selectedCommitmentId',
            'invoiceNumber',
            'receptionSlipNumber',
            'mandateLiquidatedAmount',
            'paymentReference',
            'mandateNotes',
        ]);

        Notification::make()
            ->title(__('تم تسجيل حوالة الدفع وتصفية النفقة بنجاح'))
            ->success()
            ->send();
    }

    // ─── Data View Accessors ────────────────────────────────────

    public function getViewData(): array
    {
        /** @var SchoolBudgetService $service */
        $service = app(SchoolBudgetService::class);
        $kpis = $service->getBudgetKpis($this->fiscalYear);

        // Budget lines query
        $linesQuery = SchoolBudgetLine::where('fiscal_year', $this->fiscalYear);
        if ($this->budgetTypeFilter !== 'all') {
            $linesQuery->where('budget_type', $this->budgetTypeFilter);
        }
        $lines = $linesQuery->orderBy('budget_type')->orderBy('chapter')->get();

        // Commitments query
        $commitments = BudgetCommitment::whereYear('order_date', $this->fiscalYear)
            ->with(['budgetLine', 'createdBy'])
            ->latest('order_date')
            ->get();

        // Mandates query
        $mandates = PaymentMandate::whereYear('mandate_date', $this->fiscalYear)
            ->with(['commitment.budgetLine', 'authorizedBy'])
            ->latest('mandate_date')
            ->get();

        // Dropdown available expenditure lines
        $expenditureLines = SchoolBudgetLine::where('fiscal_year', $this->fiscalYear)
            ->where('budget_type', BudgetType::Expenditure->value)
            ->orderBy('chapter')
            ->get();

        // Available commitments for mandating
        $eligibleCommitments = BudgetCommitment::whereYear('order_date', $this->fiscalYear)
            ->where('status', CommitmentStatus::Committed->value)
            ->get();

        return [
            'kpis' => $kpis,
            'lines' => $lines,
            'commitments' => $commitments,
            'mandates' => $mandates,
            'expenditureLines' => $expenditureLines,
            'eligibleCommitments' => $eligibleCommitments,
        ];
    }
}
