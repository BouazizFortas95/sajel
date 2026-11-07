<?php

namespace App\Services;

use App\Enums\BudgetType;
use App\Enums\CommitmentStatus;
use App\Models\BudgetCommitment;
use App\Models\PaymentMandate;
use App\Models\SchoolBudgetLine;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;

class SchoolBudgetService
{
    /**
     * Seed standard Algerian Middle/Secondary school budget chapters and articles (المرسوم 91-311).
     */
    public function seedStandardBudgetGrid(int $fiscalYear, ?string $academicYear = null): void
    {
        $year = $academicYear ?? '2026/2027';

        $standardGrid = [
            // النفقات (Dépenses)
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 1: أعباء ومرتبات المستخدمين',
                'article' => 'المادة 1: رواتب وأجور الموظفين والأساتذة',
                'allocated_amount' => 5000000.00,
            ],
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 2: نفقات التسيير والوسائل البيداغوجية',
                'article' => 'المادة 1: مستلزمات التدريس وأدوات المخابر العلمية',
                'allocated_amount' => 850000.00,
            ],
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 2: نفقات التسيير والوسائل البيداغوجية',
                'article' => 'المادة 2: القرطاسية ومطبوعات الإدارة والامتحانات',
                'allocated_amount' => 600000.00,
            ],
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 2: نفقات التسيير والوسائل البيداغوجية',
                'article' => 'المادة 3: التوثيق والكتب المدرسية والمكتبة',
                'allocated_amount' => 300000.00,
            ],
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 3: نفقات التغذية والأنشطة المدرسية',
                'article' => 'المادة 1: تموين المطعم المدرسي بالمواد الغذائية',
                'allocated_amount' => 1200000.00,
            ],
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 3: نفقات التغذية والأنشطة المدرسية',
                'article' => 'المادة 2: النشاط الثقافي والرياضي المدرسي',
                'allocated_amount' => 200000.00,
            ],
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 4: الصيانة العامة والأعباء المشتركة',
                'article' => 'المادة 1: استهلاك الكهرباء والغاز والماء والوقود',
                'allocated_amount' => 750000.00,
            ],
            [
                'budget_type' => BudgetType::Expenditure->value,
                'chapter' => 'الباب 4: الصيانة العامة والأعباء المشتركة',
                'article' => 'المادة 2: صيانة المباني والشبكات والأجهزة',
                'allocated_amount' => 450000.00,
            ],

            // الإيرادات (Recettes)
            [
                'budget_type' => BudgetType::Revenue->value,
                'chapter' => 'الباب 1: إعانات الدولة والجماعات المحلية',
                'article' => 'المادة 1: إعانة التسيير السنوية لوزارة التربية',
                'allocated_amount' => 8500000.00,
            ],
            [
                'budget_type' => BudgetType::Revenue->value,
                'chapter' => 'الباب 2: المداخيل الذاتية والخدمات',
                'article' => 'المادة 1: حقوق التسجيل والمساهمات المدرسية',
                'allocated_amount' => 500000.00,
            ],
            [
                'budget_type' => BudgetType::Revenue->value,
                'chapter' => 'الباب 2: المداخيل الذاتية والخدمات',
                'article' => 'المادة 2: مداخيل الأنشطة وبيع المطبوعات والتأمين',
                'allocated_amount' => 350000.00,
            ],
        ];

        foreach ($standardGrid as $row) {
            SchoolBudgetLine::firstOrCreate(
                [
                    'fiscal_year' => $fiscalYear,
                    'budget_type' => $row['budget_type'],
                    'chapter' => $row['chapter'],
                    'article' => $row['article'],
                ],
                [
                    'academic_year' => $year,
                    'allocated_amount' => $row['allocated_amount'],
                    'committed_amount' => 0.00,
                    'liquidated_amount' => 0.00,
                ]
            );
        }
    }

    /**
     * Generate sequential purchase order reference.
     */
    public function generateOrderReference(?int $year = null): string
    {
        $year = $year ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $count = BudgetCommitment::whereYear('order_date', $year)->count() + 1;
        $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "BC/{$year}/{$seq}";
    }

    /**
     * Generate sequential commitment number.
     */
    public function generateCommitmentNumber(?int $year = null): string
    {
        $year = $year ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $count = BudgetCommitment::whereYear('order_date', $year)->count() + 1;
        $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "ENG/{$year}/{$seq}";
    }

    /**
     * Record a new budget commitment & purchase order.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordCommitment(array $data): BudgetCommitment
    {
        $year = ! empty($data['order_date']) ? (int) date('Y', strtotime($data['order_date'])) : (int) now()->year;

        if (empty($data['commitment_number'])) {
            $data['commitment_number'] = $this->generateCommitmentNumber($year);
        }

        if (empty($data['order_reference'])) {
            $data['order_reference'] = $this->generateOrderReference($year);
        }

        return DB::transaction(function () use ($data) {
            $commitment = BudgetCommitment::create($data);

            $budgetLine = $commitment->budgetLine;
            $budgetLine->increment('committed_amount', $commitment->estimated_amount);

            return $commitment;
        });
    }

    /**
     * Generate sequential payment mandate number.
     */
    public function generateMandateNumber(?int $year = null): string
    {
        $year = $year ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $count = PaymentMandate::whereYear('mandate_date', $year)->count() + 1;
        $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "MAND/{$year}/{$seq}";
    }

    /**
     * Record a payment mandate and liquidate the expenditure.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordPaymentMandate(array $data): PaymentMandate
    {
        $year = ! empty($data['mandate_date']) ? (int) date('Y', strtotime($data['mandate_date'])) : (int) now()->year;

        if (empty($data['mandate_number'])) {
            $data['mandate_number'] = $this->generateMandateNumber($year);
        }

        return DB::transaction(function () use ($data) {
            $mandate = PaymentMandate::create($data);

            $commitment = $mandate->commitment;
            $commitment->update([
                'status' => CommitmentStatus::Liquidated,
            ]);

            $budgetLine = $commitment->budgetLine;
            $budgetLine->increment('liquidated_amount', $mandate->liquidated_amount);

            return $mandate;
        });
    }

    /**
     * Assemble printable data for Budget Execution Ledger (A4 Landscape).
     *
     * @return array<string, mixed>
     */
    public function assembleBudgetLedgerData(int $fiscalYear, ?string $budgetType = null): array
    {
        $query = SchoolBudgetLine::where('fiscal_year', $fiscalYear)
            ->with('commitments');

        if ($budgetType && $budgetType !== 'all') {
            $query->where('budget_type', $budgetType);
        }

        $lines = $query->orderBy('budget_type')->orderBy('chapter')->get();

        $totalAllocated = $lines->sum('allocated_amount');
        $totalCommitted = $lines->sum('committed_amount');
        $totalLiquidated = $lines->sum('liquidated_amount');
        $availableBalance = max(0.0, $totalAllocated - $totalCommitted);

        return [
            'fiscalYear' => $fiscalYear,
            'budgetType' => $budgetType ? BudgetType::tryFrom($budgetType) : null,
            'lines' => $lines,
            'totalAllocated' => $totalAllocated,
            'totalCommitted' => $totalCommitted,
            'totalLiquidated' => $totalLiquidated,
            'availableBalance' => $availableBalance,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Purchase Order (Bon de Commande - A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assemblePurchaseOrderData(BudgetCommitment $commitment): array
    {
        $commitment->loadMissing(['budgetLine', 'createdBy']);

        return [
            'commitment' => $commitment,
            'budgetLine' => $commitment->budgetLine,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Payment Mandate (Mandat de Paiement - A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assemblePaymentMandateData(PaymentMandate $mandate): array
    {
        $mandate->loadMissing(['commitment.budgetLine', 'authorizedBy', 'paidBy']);

        return [
            'mandate' => $mandate,
            'commitment' => $mandate->commitment,
            'budgetLine' => $mandate->commitment?->budgetLine,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * KPI telemetry for the budget cockpit.
     *
     * @return array<string, mixed>
     */
    public function getBudgetKpis(int $fiscalYear): array
    {
        $expenditureLines = SchoolBudgetLine::where('fiscal_year', $fiscalYear)
            ->where('budget_type', BudgetType::Expenditure->value)
            ->get();

        $totalAllocated = $expenditureLines->sum('allocated_amount');
        $totalCommitted = $expenditureLines->sum('committed_amount');
        $totalLiquidated = $expenditureLines->sum('liquidated_amount');
        $availableBalance = max(0.0, $totalAllocated - $totalCommitted);

        $utilizationRate = $totalAllocated > 0
            ? min(100.0, round(($totalCommitted / $totalAllocated) * 100, 1))
            : 0.0;

        $commitmentsCount = BudgetCommitment::whereYear('order_date', $fiscalYear)->count();
        $mandatesCount = PaymentMandate::whereYear('mandate_date', $fiscalYear)->count();

        return [
            'totalAllocated' => $totalAllocated,
            'totalCommitted' => $totalCommitted,
            'totalLiquidated' => $totalLiquidated,
            'availableBalance' => $availableBalance,
            'utilizationRate' => $utilizationRate,
            'commitmentsCount' => $commitmentsCount,
            'mandatesCount' => $mandatesCount,
        ];
    }
}
