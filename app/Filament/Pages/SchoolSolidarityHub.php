<?php

namespace App\Filament\Pages;

use App\Enums\SocialEligibilityCategory;
use App\Enums\SolidarityAidType;
use App\Enums\SolidarityBeneficiaryStatus;
use App\Enums\SolidarityCommitteeRole;
use App\Models\SchoolSolidarityAidDistribution;
use App\Models\SchoolSolidarityCampaign;
use App\Models\SchoolSolidarityCommitteeMember;
use App\Models\Student;
use App\Models\StudentSolidarityBeneficiary;
use App\Models\User;
use App\Services\SchoolSolidarityService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolSolidarityHub extends Page
{
    protected static string $view = 'filament.pages.school-solidarity-hub';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 5;

    public string $activeTab = 'campaigns'; // campaigns | beneficiaries | committee | aid

    public ?int $selectedCampaignId = null;

    public string $searchQuery = '';

    public string $categoryFilter = 'all';

    public string $statusFilter = 'all';

    // ─── Modal State: Add Campaign ───────────────────────────────
    public bool $isCampaignModalOpen = false;

    public int $newCampaignYear = 2026;

    public string $newAcademicYear = '2026/2027';

    public string $newCampaignTitle = '';

    public string $newStartDate = '';

    public string $newEndDate = '';

    public float $newGrantAmount = 5000.00;

    public ?float $newAllocatedBudget = null;

    public string $newCampaignNotes = '';

    // ─── Modal State: Add Beneficiary Dossier ───────────────────
    public bool $isBeneficiaryModalOpen = false;

    public ?int $newStudentId = null;

    public string $newEligibilityCategory = 'orphan';

    public string $newGuardianType = 'father';

    public string $newGuardianNationalId = '';

    public string $newGuardianFullName = '';

    public string $newGuardianProfession = '';

    public float $newMonthlyIncome = 0.00;

    public string $newCcpAccountNumber = '';

    public string $newCcpKey = '';

    public string $newRipNumber = '';

    public array $newSubmittedDocuments = [];

    public string $newBeneficiaryNotes = '';

    // ─── Modal State: Mark Beneficiary Paid ──────────────────────
    public bool $isPaymentModalOpen = false;

    public ?int $paymentBeneficiaryId = null;

    public string $paymentReference = '';

    public string $paymentDate = '';

    // ─── Modal State: Record In-Kind Aid Distribution ───────────
    public bool $isAidModalOpen = false;

    public ?int $aidStudentId = null;

    public string $aidType = 'free_textbooks';

    public string $aidItemsDescription = '';

    public float $aidEstimatedValue = 0.00;

    public string $aidDonorEntity = 'وزارة التربية الوطنية';

    public string $aidDistributionDate = '';

    public string $aidNotes = '';

    // ─── Modal State: Add Committee Member ──────────────────────
    public bool $isCommitteeModalOpen = false;

    public ?int $committeeUserId = null;

    public string $committeeMemberName = '';

    public string $committeeRole = 'teacher_representative';

    public string $committeeRoleLabel = '';

    public bool $committeeAttended = true;

    public function mount(): void
    {
        $today = now(config('app.timezone', 'Africa/Algiers'));
        $this->newCampaignYear = (int) $today->year;
        $this->newAcademicYear = sprintf('%d/%d', $this->newCampaignYear, $this->newCampaignYear + 1);
        $this->newStartDate = $today->format('Y-m-d');
        $this->newEndDate = $today->copy()->addMonths(2)->format('Y-m-d');
        $this->paymentDate = $today->format('Y-m-d');
        $this->aidDistributionDate = $today->format('Y-m-d');

        $activeCampaign = SchoolSolidarityCampaign::latest('campaign_year')->first();
        if ($activeCampaign) {
            $this->selectedCampaignId = $activeCampaign->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSolidarity();
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
        return __('التضامن ومنحة 5000 دج');
    }

    public function getTitle(): string|Htmlable
    {
        return __('منظومة التضامن المدرسي، منحة 5000 دج ومجانية المستلزمات');
    }

    // ─── Campaign Actions ────────────────────────────────────────

    public function openCampaignModal(): void
    {
        $this->newCampaignTitle = sprintf('حملة منحة التمدرس الخاصة 5000 دج ومجانية المستلزمات للسنة الدراسية %s', $this->newAcademicYear);
        $this->isCampaignModalOpen = true;
    }

    public function closeCampaignModal(): void
    {
        $this->isCampaignModalOpen = false;
    }

    public function createCampaign(SchoolSolidarityService $service): void
    {
        $campaign = $service->createCampaign([
            'campaign_year' => $this->newCampaignYear,
            'academic_year' => $this->newAcademicYear,
            'title' => $this->newCampaignTitle ?: sprintf('حملة منحة التمدرس الخاصة للسنة الدراسية %s', $this->newAcademicYear),
            'start_date' => $this->newStartDate,
            'end_date' => $this->newEndDate,
            'grant_amount' => $this->newGrantAmount,
            'allocated_budget' => $this->newAllocatedBudget,
            'notes' => $this->newCampaignNotes,
        ], Auth::id());

        $this->selectedCampaignId = $campaign->id;
        $this->isCampaignModalOpen = false;

        Notification::make()
            ->title(__('تم إطلاق حملة التضامن المدرسي وتشكيل اللجنة النموذجية بنجاح'))
            ->success()
            ->send();
    }

    // ─── Beneficiary Actions ─────────────────────────────────────

    public function openBeneficiaryModal(): void
    {
        $this->newStudentId = null;
        $this->newGuardianNationalId = '';
        $this->newGuardianFullName = '';
        $this->newGuardianProfession = '';
        $this->newMonthlyIncome = 0.00;
        $this->newCcpAccountNumber = '';
        $this->newCcpKey = '';
        $this->newRipNumber = '';
        $this->newSubmittedDocuments = [];
        $this->newBeneficiaryNotes = '';
        $this->isBeneficiaryModalOpen = true;
    }

    public function closeBeneficiaryModal(): void
    {
        $this->isBeneficiaryModalOpen = false;
    }

    public function onStudentSelected(): void
    {
        if ($this->newStudentId) {
            $student = Student::find($this->newStudentId);
            if ($student) {
                $this->newGuardianFullName = $student->guardian_name ?? '';
            }
        }
    }

    public function registerBeneficiary(SchoolSolidarityService $service): void
    {
        if (! $this->selectedCampaignId || ! $this->newStudentId) {
            Notification::make()->title(__('يرجى اختيار الحملة والتلميذ أولاً'))->danger()->send();

            return;
        }

        // Prevent duplicate registration in the same campaign
        $exists = StudentSolidarityBeneficiary::where('campaign_id', $this->selectedCampaignId)
            ->where('student_id', $this->newStudentId)
            ->exists();

        if ($exists) {
            Notification::make()->title(__('التلميذ مسجل بالفعل في هذه الحملة'))->warning()->send();

            return;
        }

        $service->registerBeneficiary([
            'campaign_id' => $this->selectedCampaignId,
            'student_id' => $this->newStudentId,
            'eligibility_category' => SocialEligibilityCategory::from($this->newEligibilityCategory),
            'guardian_type' => $this->newGuardianType,
            'guardian_national_id' => $this->newGuardianNationalId ?: '000000000',
            'guardian_full_name' => $this->newGuardianFullName ?: 'الولي الشرعي',
            'guardian_profession' => $this->newGuardianProfession,
            'monthly_income' => $this->newMonthlyIncome,
            'ccp_account_number' => $this->newCcpAccountNumber,
            'ccp_key' => $this->newCcpKey,
            'rip_number' => $this->newRipNumber,
            'submitted_documents' => $this->newSubmittedDocuments,
            'notes' => $this->newBeneficiaryNotes,
        ], Auth::id());

        $this->isBeneficiaryModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل ملف التلميذ للاستفادة من المنحة بنجاح'))
            ->success()
            ->send();
    }

    public function approveBeneficiary(int $beneficiaryId, SchoolSolidarityService $service): void
    {
        $beneficiary = StudentSolidarityBeneficiary::findOrFail($beneficiaryId);
        $service->updateBeneficiaryStatus($beneficiary, SolidarityBeneficiaryStatus::DeliberatedApproved);

        Notification::make()->title(__('تم قبول الملف ومصادقة اللجنة عليه'))->success()->send();
    }

    public function rejectBeneficiary(int $beneficiaryId, SchoolSolidarityService $service): void
    {
        $beneficiary = StudentSolidarityBeneficiary::findOrFail($beneficiaryId);
        $service->updateBeneficiaryStatus($beneficiary, SolidarityBeneficiaryStatus::Rejected, 'عدم استيفاء الشروط الاجتماعية أو تجاوز الدخل للحد القانوني');

        Notification::make()->title(__('تم رفض الملف لعدم استيفاء الشروط'))->warning()->send();
    }

    public function openPaymentModal(int $beneficiaryId): void
    {
        $this->paymentBeneficiaryId = $beneficiaryId;
        $this->paymentReference = 'VIR-CCP-'.now()->format('YmdHi');
        $this->isPaymentModalOpen = true;
    }

    public function closePaymentModal(): void
    {
        $this->isPaymentModalOpen = false;
    }

    public function confirmPayment(SchoolSolidarityService $service): void
    {
        if (! $this->paymentBeneficiaryId) {
            return;
        }

        $beneficiary = StudentSolidarityBeneficiary::findOrFail($this->paymentBeneficiaryId);
        $service->markBeneficiaryPaid($beneficiary, $this->paymentReference, $this->paymentDate);

        $this->isPaymentModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل صرف المنحة وتحويل المبلغ عبر البريد بنجاح'))
            ->success()
            ->send();
    }

    // ─── In-Kind Aid Distribution Actions ────────────────────────

    public function openAidModal(): void
    {
        $this->aidStudentId = null;
        $this->aidItemsDescription = '';
        $this->aidEstimatedValue = 0.00;
        $this->aidNotes = '';
        $this->isAidModalOpen = true;
    }

    public function closeAidModal(): void
    {
        $this->isAidModalOpen = false;
    }

    public function recordAidDistribution(SchoolSolidarityService $service): void
    {
        if (! $this->selectedCampaignId || ! $this->aidStudentId) {
            Notification::make()->title(__('يرجى اختيار الحملة والتلميذ المستفيد'))->danger()->send();

            return;
        }

        $service->recordAidDistribution([
            'campaign_id' => $this->selectedCampaignId,
            'student_id' => $this->aidStudentId,
            'aid_type' => SolidarityAidType::from($this->aidType),
            'distribution_date' => $this->aidDistributionDate,
            'items_description' => $this->aidItemsDescription ?: 'طقم الكتب المدرسية المجانية + مستلزمات دراسية',
            'estimated_value' => $this->aidEstimatedValue,
            'donor_entity' => $this->aidDonorEntity,
            'notes' => $this->aidNotes,
        ], Auth::id());

        $this->isAidModalOpen = false;

        Notification::make()
            ->title(__('تم تسجيل تسليم الإعانة العينية واستخراج سند الاستلام بنجاح'))
            ->success()
            ->send();
    }

    // ─── Committee Actions ───────────────────────────────────────

    public function openCommitteeModal(): void
    {
        $this->committeeUserId = null;
        $this->committeeMemberName = '';
        $this->committeeRole = 'teacher_representative';
        $this->committeeRoleLabel = 'ممثل هيئة التدريس';
        $this->committeeAttended = true;
        $this->isCommitteeModalOpen = true;
    }

    public function closeCommitteeModal(): void
    {
        $this->isCommitteeModalOpen = false;
    }

    public function addCommitteeMember(): void
    {
        if (! $this->selectedCampaignId || ! $this->committeeMemberName) {
            Notification::make()->title(__('يرجى ملء اسم العضو'))->danger()->send();

            return;
        }

        SchoolSolidarityCommitteeMember::create([
            'campaign_id' => $this->selectedCampaignId,
            'user_id' => $this->committeeUserId,
            'member_name' => $this->committeeMemberName,
            'committee_role' => SolidarityCommitteeRole::from($this->committeeRole),
            'institution_role_label' => $this->committeeRoleLabel ?: 'عضو لجنة التضامن المدرسي',
            'attended_deliberation' => $this->committeeAttended,
        ]);

        $this->isCommitteeModalOpen = false;

        Notification::make()
            ->title(__('تمت إضافة عضو اللجنة بنجاح'))
            ->success()
            ->send();
    }

    // ─── View Properties ─────────────────────────────────────────

    public function getSelectedCampaignProperty(): ?SchoolSolidarityCampaign
    {
        return $this->selectedCampaignId
            ? SchoolSolidarityCampaign::with(['committeeMembers', 'beneficiaries'])->find($this->selectedCampaignId)
            : null;
    }

    public function getCampaignsProperty()
    {
        return SchoolSolidarityCampaign::orderByDesc('campaign_year')->get();
    }

    public function getBeneficiariesProperty()
    {
        if (! $this->selectedCampaignId) {
            return collect();
        }

        $query = StudentSolidarityBeneficiary::with(['student.group.classroom'])
            ->where('campaign_id', $this->selectedCampaignId);

        if ($this->searchQuery) {
            $q = '%'.$this->searchQuery.'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('file_number', 'like', $q)
                    ->orWhere('guardian_full_name', 'like', $q)
                    ->orWhere('guardian_national_id', 'like', $q)
                    ->orWhereHas('student', function ($sq) use ($q) {
                        $sq->where('first_name', 'like', $q)
                            ->orWhere('last_name', 'like', $q)
                            ->orWhere('matricule_number', 'like', $q)
                            ->orWhere('registration_number', 'like', $q);
                    });
            });
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('eligibility_category', $this->categoryFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->latest('id')->paginate(15);
    }

    public function getAidDistributionsProperty()
    {
        if (! $this->selectedCampaignId) {
            return collect();
        }

        return SchoolSolidarityAidDistribution::with(['student.group.classroom', 'issuedBy'])
            ->where('campaign_id', $this->selectedCampaignId)
            ->latest('id')
            ->paginate(15);
    }

    public function getCommitteeMembersProperty()
    {
        if (! $this->selectedCampaignId) {
            return collect();
        }

        return SchoolSolidarityCommitteeMember::where('campaign_id', $this->selectedCampaignId)
            ->orderBy('id')
            ->get();
    }

    public function getKpisProperty(SchoolSolidarityService $service): array
    {
        return $service->getSolidarityKpis($this->selectedCampaignId);
    }

    public function getStudentsListProperty()
    {
        return Student::with('group.classroom')
            ->active()
            ->orderBy('last_name')
            ->limit(100)
            ->get();
    }
}
