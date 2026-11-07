<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\SocialEligibilityCategory;
use App\Enums\SolidarityAidType;
use App\Enums\SolidarityBeneficiaryStatus;
use App\Enums\SolidarityCampaignStatus;
use App\Enums\SolidarityCommitteeRole;
use App\Models\SchoolSetting;
use App\Models\SchoolSolidarityAidDistribution;
use App\Models\SchoolSolidarityCampaign;
use App\Models\SchoolSolidarityCommitteeMember;
use App\Models\Student;
use App\Models\StudentSolidarityBeneficiary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SchoolSolidarityService
{
    /**
     * Generate sequential file number for solidarity applicant (e.g. SOL/2026/001).
     */
    public function generateFileNumber(int $year): string
    {
        $prefix = "SOL/{$year}/";
        $lastRecord = StudentSolidarityBeneficiary::where('file_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('file_number');

        $sequence = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * Generate sequential voucher number for in-kind aid delivery (e.g. AID/2026/001).
     */
    public function generateAidVoucherNumber(int $year): string
    {
        $prefix = "AID/{$year}/";
        $lastRecord = SchoolSolidarityAidDistribution::where('voucher_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('voucher_number');

        $sequence = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * Create a new solidarity campaign and optionally bootstrap committee members.
     */
    public function createCampaign(array $data, int $userId): SchoolSolidarityCampaign
    {
        return DB::transaction(function () use ($data, $userId) {
            $campaignYear = (int) ($data['campaign_year'] ?? date('Y'));
            $academicYear = $data['academic_year'] ?? sprintf('%d/%d', $campaignYear, $campaignYear + 1);

            $campaign = SchoolSolidarityCampaign::create([
                'campaign_year' => $campaignYear,
                'academic_year' => $academicYear,
                'title' => $data['title'] ?? sprintf('حملة منحة التمدرس الخاصة 5000 دج ومجانية المستلزمات للسنة الدراسية %s', $academicYear),
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? now()->addMonths(2)->toDateString(),
                'grant_amount' => $data['grant_amount'] ?? 5000.00,
                'status' => $data['status'] ?? SolidarityCampaignStatus::Planning,
                'allocated_budget' => $data['allocated_budget'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $this->initializeDefaultCommittee($campaign);

            return $campaign;
        });
    }

    /**
     * Initialize statutory institutional committee members.
     */
    public function initializeDefaultCommittee(SchoolSolidarityCampaign $campaign): void
    {
        // 1. Principal (President)
        $principal = User::where('role', Role::Principal)->first();
        SchoolSolidarityCommitteeMember::firstOrCreate([
            'campaign_id' => $campaign->id,
            'committee_role' => SolidarityCommitteeRole::President,
        ], [
            'user_id' => $principal?->id,
            'member_name' => $principal?->name ?? 'مدير المؤسسة',
            'institution_role_label' => 'مدير المؤسسة ورئيس اللجنة',
            'attended_deliberation' => true,
        ]);

        // 2. Financial Officer / Bursar (Financial Reporter)
        $bursar = User::where('role', Role::FinanceOfficer)->first();
        SchoolSolidarityCommitteeMember::firstOrCreate([
            'campaign_id' => $campaign->id,
            'committee_role' => SolidarityCommitteeRole::FinancialReporter,
        ], [
            'user_id' => $bursar?->id,
            'member_name' => $bursar?->name ?? 'المسير المالي والمقتصد',
            'institution_role_label' => 'المقتصد / المسير المالي (المقرر)',
            'attended_deliberation' => true,
        ]);

        // 3. Censor / Director of Studies
        $censor = User::where('role', Role::Censor)->first();
        SchoolSolidarityCommitteeMember::firstOrCreate([
            'campaign_id' => $campaign->id,
            'committee_role' => SolidarityCommitteeRole::CensorMember,
        ], [
            'user_id' => $censor?->id,
            'member_name' => $censor?->name ?? 'ناظر الدراسات ومستشار التربية',
            'institution_role_label' => 'ناظر المؤسسة / مستشار التربية',
            'attended_deliberation' => true,
        ]);

        // 4. Counselor
        $counselor = User::where('role', Role::Counselor)->first();
        SchoolSolidarityCommitteeMember::firstOrCreate([
            'campaign_id' => $campaign->id,
            'committee_role' => SolidarityCommitteeRole::GuidanceMember,
        ], [
            'user_id' => $counselor?->id,
            'member_name' => $counselor?->name ?? 'مستشار التوجيه والإرشاد المدرسي',
            'institution_role_label' => 'مستشار التوجيه المدرسي والمهني',
            'attended_deliberation' => true,
        ]);

        // 5. Teachers Representative
        SchoolSolidarityCommitteeMember::firstOrCreate([
            'campaign_id' => $campaign->id,
            'committee_role' => SolidarityCommitteeRole::TeacherRepresentative,
        ], [
            'user_id' => null,
            'member_name' => 'ممثل هيئة التدريس المنتخب',
            'institution_role_label' => 'ممثل الأساتذة في اللجنة',
            'attended_deliberation' => true,
        ]);

        // 6. Parent Association Representative
        SchoolSolidarityCommitteeMember::firstOrCreate([
            'campaign_id' => $campaign->id,
            'committee_role' => SolidarityCommitteeRole::ParentAssociationRepresentative,
        ], [
            'user_id' => null,
            'member_name' => 'رئيس جمعية أولياء التلاميذ',
            'institution_role_label' => 'ممثل جمعية أولياء التلاميذ',
            'attended_deliberation' => true,
        ]);
    }

    /**
     * Register a student dossier for the 5,000 DZD special grant.
     */
    public function registerBeneficiary(array $data, ?int $verifiedById = null): StudentSolidarityBeneficiary
    {
        return DB::transaction(function () use ($data, $verifiedById) {
            $campaign = SchoolSolidarityCampaign::findOrFail($data['campaign_id']);
            $fileNumber = $data['file_number'] ?? $this->generateFileNumber($campaign->campaign_year);

            $beneficiary = StudentSolidarityBeneficiary::create([
                'campaign_id' => $campaign->id,
                'student_id' => $data['student_id'],
                'file_number' => $fileNumber,
                'eligibility_category' => $data['eligibility_category'],
                'guardian_type' => $data['guardian_type'] ?? 'father',
                'guardian_national_id' => $data['guardian_national_id'],
                'guardian_full_name' => $data['guardian_full_name'],
                'guardian_profession' => $data['guardian_profession'] ?? null,
                'monthly_income' => $data['monthly_income'] ?? 0.00,
                'ccp_account_number' => $data['ccp_account_number'] ?? null,
                'ccp_key' => $data['ccp_key'] ?? null,
                'rip_number' => $data['rip_number'] ?? null,
                'status' => $data['status'] ?? SolidarityBeneficiaryStatus::Submitted,
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'submitted_documents' => $data['submitted_documents'] ?? [],
                'notes' => $data['notes'] ?? null,
                'verified_by' => $verifiedById,
            ]);

            $this->syncCampaignCounts($campaign);

            return $beneficiary;
        });
    }

    /**
     * Update beneficiary status and synchronize campaign counts.
     */
    public function updateBeneficiaryStatus(StudentSolidarityBeneficiary $beneficiary, SolidarityBeneficiaryStatus $newStatus, ?string $rejectionReason = null): StudentSolidarityBeneficiary
    {
        return DB::transaction(function () use ($beneficiary, $newStatus, $rejectionReason) {
            $beneficiary->update([
                'status' => $newStatus,
                'rejection_reason' => $newStatus === SolidarityBeneficiaryStatus::Rejected ? $rejectionReason : null,
            ]);

            $this->syncCampaignCounts($beneficiary->campaign);

            return $beneficiary->fresh();
        });
    }

    /**
     * Mark beneficiary as paid and disburse 5,000 DZD.
     */
    public function markBeneficiaryPaid(StudentSolidarityBeneficiary $beneficiary, string $paymentReference, ?string $paymentDate = null): StudentSolidarityBeneficiary
    {
        return DB::transaction(function () use ($beneficiary, $paymentReference, $paymentDate) {
            $beneficiary->update([
                'status' => SolidarityBeneficiaryStatus::Paid,
                'payment_reference' => $paymentReference,
                'payment_date' => $paymentDate ?? now()->toDateString(),
            ]);

            $this->syncCampaignCounts($beneficiary->campaign);

            return $beneficiary->fresh();
        });
    }

    /**
     * Record in-kind aid distribution (Free textbooks, school bag, etc.).
     */
    public function recordAidDistribution(array $data, int $userId): SchoolSolidarityAidDistribution
    {
        return DB::transaction(function () use ($data, $userId) {
            $campaign = SchoolSolidarityCampaign::findOrFail($data['campaign_id']);
            $voucherNumber = $data['voucher_number'] ?? $this->generateAidVoucherNumber($campaign->campaign_year);

            return SchoolSolidarityAidDistribution::create([
                'campaign_id' => $campaign->id,
                'student_id' => $data['student_id'],
                'aid_type' => $data['aid_type'] ?? SolidarityAidType::FreeTextbooks,
                'voucher_number' => $voucherNumber,
                'distribution_date' => $data['distribution_date'] ?? now()->toDateString(),
                'items_description' => $data['items_description'],
                'estimated_value' => $data['estimated_value'] ?? 0.00,
                'donor_entity' => $data['donor_entity'] ?? 'وزارة التربية الوطنية',
                'received_by_guardian' => $data['received_by_guardian'] ?? true,
                'guardian_signature_confirmed' => $data['guardian_signature_confirmed'] ?? true,
                'issued_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Synchronize campaign totals.
     */
    public function syncCampaignCounts(SchoolSolidarityCampaign $campaign): void
    {
        $approvedCount = $campaign->beneficiaries()
            ->whereIn('status', [
                SolidarityBeneficiaryStatus::DeliberatedApproved,
                SolidarityBeneficiaryStatus::ApprovedByDaira,
                SolidarityBeneficiaryStatus::Paid,
            ])
            ->count();

        $paidCount = $campaign->beneficiaries()
            ->where('status', SolidarityBeneficiaryStatus::Paid)
            ->count();

        $campaign->update([
            'approved_beneficiaries_count' => $approvedCount,
            'total_disbursed_amount' => $paidCount * (float) $campaign->grant_amount,
        ]);
    }

    /**
     * Get real-time KPIs and telemetry.
     */
    public function getSolidarityKpis(?int $campaignId = null): array
    {
        $query = StudentSolidarityBeneficiary::query();
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $totalBeneficiaries = (clone $query)->count();
        $approvedCount = (clone $query)->whereIn('status', [
            SolidarityBeneficiaryStatus::DeliberatedApproved,
            SolidarityBeneficiaryStatus::ApprovedByDaira,
            SolidarityBeneficiaryStatus::Paid,
        ])->count();

        $paidCount = (clone $query)->where('status', SolidarityBeneficiaryStatus::Paid)->count();
        $rejectedCount = (clone $query)->where('status', SolidarityBeneficiaryStatus::Rejected)->count();
        $submittedCount = (clone $query)->where('status', SolidarityBeneficiaryStatus::Submitted)->count();

        $aidQuery = SchoolSolidarityAidDistribution::query();
        if ($campaignId) {
            $aidQuery->where('campaign_id', $campaignId);
        }
        $aidDistributionsCount = (clone $aidQuery)->count();
        $totalAidValue = (float) (clone $aidQuery)->sum('estimated_value');

        // Breakdown by eligibility category
        $orphansCount = (clone $query)->where('eligibility_category', SocialEligibilityCategory::Orphan)->count();
        $needyCount = (clone $query)->where('eligibility_category', SocialEligibilityCategory::NeedyNoIncome)->count();
        $lowIncomeCount = (clone $query)->where('eligibility_category', SocialEligibilityCategory::LowIncome)->count();
        $specialNeedsCount = (clone $query)->where('eligibility_category', SocialEligibilityCategory::SpecialNeeds)->count();
        $nationalTragedyCount = (clone $query)->where('eligibility_category', SocialEligibilityCategory::NationalTragedy)->count();

        $campaignCount = SchoolSolidarityCampaign::count();
        $activeCampaign = $campaignId ? SchoolSolidarityCampaign::find($campaignId) : SchoolSolidarityCampaign::latest('campaign_year')->first();
        $totalDisbursed = $activeCampaign ? (float) $activeCampaign->total_disbursed_amount : ($paidCount * 5000.00);

        return [
            'campaignCount' => $campaignCount,
            'totalBeneficiaries' => $totalBeneficiaries,
            'approvedCount' => $approvedCount,
            'paidCount' => $paidCount,
            'rejectedCount' => $rejectedCount,
            'submittedCount' => $submittedCount,
            'totalDisbursed' => $totalDisbursed,
            'aidDistributionsCount' => $aidDistributionsCount,
            'totalAidValue' => $totalAidValue,
            'orphansCount' => $orphansCount,
            'needyCount' => $needyCount,
            'lowIncomeCount' => $lowIncomeCount,
            'specialNeedsCount' => $specialNeedsCount,
            'nationalTragedyCount' => $nationalTragedyCount,
        ];
    }

    /**
     * Assemble Nominative List Data for Official Daira/Post CCP Transmittal.
     */
    public function assembleNominativeListData(SchoolSolidarityCampaign $campaign): array
    {
        $setting = SchoolSetting::first();
        $beneficiaries = $campaign->beneficiaries()
            ->with(['student.group.classroom'])
            ->whereIn('status', [
                SolidarityBeneficiaryStatus::DeliberatedApproved,
                SolidarityBeneficiaryStatus::ApprovedByDaira,
                SolidarityBeneficiaryStatus::Paid,
            ])
            ->get();

        $totalAmount = $beneficiaries->count() * (float) $campaign->grant_amount;

        return [
            'institution' => [
                'name' => $setting?->school_name ?? config('app.name', 'ثانوية سجّل الوطنية'),
                'wilaya' => $setting?->wilaya ?? 'الجزائر',
                'daira' => $setting?->daira ?? 'الجزائر الوسطى',
                'commune' => $setting?->commune ?? 'الجزائر الوسطى',
            ],
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'academic_year' => $campaign->academic_year,
                'campaign_year' => $campaign->campaign_year,
                'grant_amount' => (float) $campaign->grant_amount,
                'status' => $campaign->status->label(),
            ],
            'beneficiaries' => $beneficiaries->map(function ($item, $index) use ($campaign) {
                return [
                    'index' => $index + 1,
                    'file_number' => $item->file_number,
                    'student_name' => $item->student?->full_name ?? '—',
                    'matricule' => $item->student?->matricule_number ?? $item->student?->registration_number ?? '—',
                    'date_of_birth' => $item->student?->date_of_birth?->format('Y-m-d') ?? '—',
                    'classroom' => $item->student?->group?->classroom?->name ?? '—',
                    'eligibility_category' => $item->eligibility_category->label(),
                    'guardian_name' => $item->guardian_full_name,
                    'guardian_id' => $item->guardian_national_id,
                    'ccp_account' => $item->ccp_account_number ? sprintf('%s / %s', $item->ccp_account_number, $item->ccp_key ?? '00') : '—',
                    'rip' => $item->rip_number ?? '—',
                    'status' => $item->status->label(),
                    'grant_amount' => (float) $campaign->grant_amount,
                ];
            }),
            'total_count' => $beneficiaries->count(),
            'total_amount' => $totalAmount,
            'generated_at' => now()->format('Y-m-d H:i'),
        ];
    }

    /**
     * Assemble Institutional Committee Deliberation Minutes (PV).
     */
    public function assembleCommitteePvData(SchoolSolidarityCampaign $campaign): array
    {
        $setting = SchoolSetting::first();
        $members = $campaign->committeeMembers()->with('user')->get();
        $beneficiaries = $campaign->beneficiaries()->get();

        $totalApplicants = $beneficiaries->count();
        $approvedCount = $beneficiaries->whereIn('status', [
            SolidarityBeneficiaryStatus::DeliberatedApproved,
            SolidarityBeneficiaryStatus::ApprovedByDaira,
            SolidarityBeneficiaryStatus::Paid,
        ])->count();
        $rejectedCount = $beneficiaries->where('status', SolidarityBeneficiaryStatus::Rejected)->count();
        $pendingCount = $totalApplicants - $approvedCount - $rejectedCount;

        $categories = [
            'orphans' => $beneficiaries->where('eligibility_category', SocialEligibilityCategory::Orphan)->where('status', '!=', SolidarityBeneficiaryStatus::Rejected)->count(),
            'needy' => $beneficiaries->where('eligibility_category', SocialEligibilityCategory::NeedyNoIncome)->where('status', '!=', SolidarityBeneficiaryStatus::Rejected)->count(),
            'low_income' => $beneficiaries->where('eligibility_category', SocialEligibilityCategory::LowIncome)->where('status', '!=', SolidarityBeneficiaryStatus::Rejected)->count(),
            'special_needs' => $beneficiaries->where('eligibility_category', SocialEligibilityCategory::SpecialNeeds)->where('status', '!=', SolidarityBeneficiaryStatus::Rejected)->count(),
            'national_tragedy' => $beneficiaries->where('eligibility_category', SocialEligibilityCategory::NationalTragedy)->where('status', '!=', SolidarityBeneficiaryStatus::Rejected)->count(),
        ];

        return [
            'institution' => [
                'name' => $setting?->school_name ?? config('app.name', 'ثانوية سجّل الوطنية'),
                'wilaya' => $setting?->wilaya ?? 'الجزائر',
                'daira' => $setting?->daira ?? 'الجزائر الوسطى',
            ],
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'academic_year' => $campaign->academic_year,
                'grant_amount' => (float) $campaign->grant_amount,
            ],
            'statistics' => [
                'total_applicants' => $totalApplicants,
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'pending_count' => $pendingCount,
                'total_financial_volume' => $approvedCount * (float) $campaign->grant_amount,
                'categories' => $categories,
            ],
            'members' => $members->map(function ($m) {
                return [
                    'name' => $m->member_name,
                    'role_label' => $m->institution_role_label,
                    'attended' => $m->attended_deliberation,
                    'notes' => $m->signature_notes,
                ];
            }),
            'deliberation_date' => now()->translatedFormat('d F Y'),
        ];
    }

    /**
     * Assemble In-Kind Aid Voucher Data (Free Textbooks / School Bag).
     */
    public function assembleAidVoucherData(SchoolSolidarityAidDistribution $distribution): array
    {
        $setting = SchoolSetting::first();
        $student = $distribution->student;

        return [
            'institution' => [
                'name' => $setting?->school_name ?? config('app.name', 'ثانوية سجّل الوطنية'),
                'wilaya' => $setting?->wilaya ?? 'الجزائر',
                'daira' => $setting?->daira ?? 'الجزائر الوسطى',
            ],
            'distribution' => [
                'id' => $distribution->id,
                'voucher_number' => $distribution->voucher_number,
                'distribution_date' => $distribution->distribution_date->format('Y-m-d'),
                'aid_type' => $distribution->aid_type->label(),
                'items_description' => $distribution->items_description,
                'estimated_value' => (float) $distribution->estimated_value,
                'donor_entity' => $distribution->donor_entity,
                'notes' => $distribution->notes,
            ],
            'student' => [
                'full_name' => $student?->full_name ?? '—',
                'matricule' => $student?->matricule_number ?? $student?->registration_number ?? '—',
                'classroom' => $student?->group?->classroom?->name ?? '—',
                'guardian_name' => $student?->guardian_name ?? 'الولي الشرعي',
                'guardian_phone' => $student?->guardian_phone ?? '—',
            ],
            'campaign' => [
                'academic_year' => $distribution->campaign?->academic_year ?? '2026/2027',
                'title' => $distribution->campaign?->title,
            ],
            'issuer' => [
                'name' => $distribution->issuedBy?->name ?? 'المسير المالي',
                'role' => $distribution->issuedBy?->role?->label() ?? 'مسؤول المصلحة',
            ],
        ];
    }
}
