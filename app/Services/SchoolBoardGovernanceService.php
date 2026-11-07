<?php

namespace App\Services;

use App\Enums\SchoolBoardMemberCategory;
use App\Enums\SchoolBoardSessionStatus;
use App\Enums\SchoolBoardType;
use App\Enums\SchoolProjectStatus;
use App\Models\ParentsAssociation;
use App\Models\ParentsAssociationMeeting;
use App\Models\SchoolBoardMember;
use App\Models\SchoolBoardSession;
use App\Models\SchoolInternalRegulation;
use App\Models\SchoolProject;
use App\Models\SchoolSetting;
use Carbon\Carbon;

class SchoolBoardGovernanceService
{
    /**
     * Generate sequential Board session reference (e.g. CEG-2026-01 or COG-2026-01).
     */
    public function generateSessionCode(int $year, SchoolBoardType $type = SchoolBoardType::Ceg): string
    {
        $prefix = strtoupper($type->value)."-{$year}-";
        $count = SchoolBoardSession::where('session_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Generate sequential school project code (e.g. PRJ-2026-01).
     */
    public function generateProjectCode(int $year): string
    {
        $prefix = "PRJ-{$year}-";
        $count = SchoolProject::where('project_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Generate sequential internal regulation code (e.g. REG-2026-01).
     */
    public function generateRegulationCode(int $year): string
    {
        $prefix = "REG-{$year}-";
        $count = SchoolInternalRegulation::where('regulation_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Generate sequential APE meeting code (e.g. APE-2026-01).
     */
    public function generateParentsMeetingCode(int $year): string
    {
        $prefix = "APE-{$year}-";
        $count = ParentsAssociationMeeting::where('meeting_code', 'like', "{$prefix}%")->count();

        return sprintf('%s%02d', $prefix, $count + 1);
    }

    /**
     * Record a formal Board session (CEG / COG).
     */
    public function recordBoardSession(array $data): SchoolBoardSession
    {
        $academicYear = $data['academic_year'] ?? '2026/2027';
        $year = (int) substr($academicYear, 0, 4);
        $type = isset($data['board_type']) && is_string($data['board_type'])
            ? SchoolBoardType::from($data['board_type'])
            : ($data['board_type'] ?? SchoolBoardType::Ceg);

        if (empty($data['session_code'])) {
            $data['session_code'] = $this->generateSessionCode($year, $type);
        }

        if (! isset($data['is_quorum_reached'])) {
            $present = $data['members_present_count'] ?? 0;
            $required = $data['quorum_required'] ?? 8;
            $data['is_quorum_reached'] = $present >= $required;
        }

        return SchoolBoardSession::create($data);
    }

    /**
     * Initialize statutory and elected Board members per Executive Decree 90-12 / 90-13.
     */
    public function autoBootstrapBoardMembers(string $academicYear, SchoolBoardType $type = SchoolBoardType::Ceg): void
    {
        $existing = SchoolBoardMember::where('academic_year', $academicYear)->count();
        if ($existing > 0) {
            return;
        }

        $mandateStart = Carbon::parse(substr($academicYear, 0, 4).'-09-01');
        $mandateEnd = Carbon::parse(substr($academicYear, 0, 4).'-09-01')->addYears(3);

        $defaultMembers = [
            [
                'member_category' => SchoolBoardMemberCategory::Statutory->value,
                'institution_role' => 'مدير المؤسسة (رئيس المجلس)',
                'member_name' => 'مدير المؤسسة التعليمية',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::Statutory->value,
                'institution_role' => $type === SchoolBoardType::Cog ? 'ناظر الثانوية (المقرر)' : 'مستشار رئيسي للتربية (المقرر)',
                'member_name' => 'مقرر المجلس',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::Statutory->value,
                'institution_role' => 'المقتصد / المسير المالي',
                'member_name' => 'المسير المالي للمؤسسة',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::Statutory->value,
                'institution_role' => 'مستشار التوجيه والإرشاد المدرسي والمهني',
                'member_name' => 'مستشار التوجيه',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::Statutory->value,
                'institution_role' => 'طبيب / ممرض وحدة الكشف والمتابعة (UDS)',
                'member_name' => 'طبيب الصحة المدرسية',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::ElectedTeacher->value,
                'institution_role' => 'ممثل منتخب عن هيئة التدريس (المواد العلمية)',
                'member_name' => 'الأستاذ المنسق العلمي',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::ElectedTeacher->value,
                'institution_role' => 'ممثل منتخب عن هيئة التدريس (المواد الأدبية واللغات)',
                'member_name' => 'الأستاذ المنسق الأدبي',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::ElectedStaff->value,
                'institution_role' => 'ممثل منتخب عن الموظفين الإداريين وأعوان الخدمة',
                'member_name' => 'مندوب الموظفين والعمال',
            ],
            [
                'member_category' => SchoolBoardMemberCategory::ElectedParent->value,
                'institution_role' => 'رئيس جمعية أولياء التلاميذ (APE)',
                'member_name' => 'رئيس مكتب جمعية الأولياء',
            ],
        ];

        foreach ($defaultMembers as $member) {
            SchoolBoardMember::create(array_merge($member, [
                'academic_year' => $academicYear,
                'mandate_start_date' => $mandateStart,
                'mandate_end_date' => $mandateEnd,
                'is_active' => true,
            ]));
        }
    }

    /**
     * Add an individual Board member.
     */
    public function addBoardMember(array $data): SchoolBoardMember
    {
        if (empty($data['mandate_start_date'])) {
            $data['mandate_start_date'] = Carbon::today();
        }
        if (empty($data['mandate_end_date'])) {
            $data['mandate_end_date'] = Carbon::parse($data['mandate_start_date'])->addYears(3);
        }

        return SchoolBoardMember::create($data);
    }

    /**
     * Create or update the Multi-Year School Strategic Project (Projet d'Établissement).
     */
    public function createOrUpdateSchoolProject(array $data): SchoolProject
    {
        $startYear = $data['start_year'] ?? '2026';
        $year = (int) $startYear;

        if (empty($data['project_code'])) {
            $data['project_code'] = $this->generateProjectCode($year);
        }

        if (empty($data['strategic_axes'])) {
            $data['strategic_axes'] = [
                'المحور 1: الرفع من مستوى التحصيل الدراسي والنجاح في الامتحانات الرسمية',
                'المحور 2: تحسين مناخ المؤسسة والانضباط ومكافحة الغيابات والتسرب المدرسي',
                'المحور 3: تطوير الحياة المدرسية، النوادي العلمية، والأنشطة الثقافية والرياضية',
                'المحور 4: ترقية الحوكمة التشاركية والتواصل المستمر مع أولياء التلاميذ',
            ];
        }

        if (empty($data['quantitative_targets'])) {
            $data['quantitative_targets'] = [
                'نسبة النجاح المستهدفة في شهادة التعليم المتوسط (BEM)' => '85.0%',
                'تقليص نسبة الإعادة والتكرار في الطور المتوسط' => '< 6.0%',
                'نسبة التغطية بالفحوصات الطبية المدرسية UDS' => '100%',
                'نسبة انخراط التلاميذ في النوادي المدرسية اللاصفية' => '> 50%',
            ];
        }

        return SchoolProject::updateOrCreate(
            ['project_code' => $data['project_code']],
            $data
        );
    }

    /**
     * Adopt and publish School Internal Regulations.
     */
    public function adoptInternalRegulations(array $data): SchoolInternalRegulation
    {
        $academicYear = $data['academic_year'] ?? '2026/2027';
        $year = (int) substr($academicYear, 0, 4);

        if (empty($data['regulation_code'])) {
            $data['regulation_code'] = $this->generateRegulationCode($year);
        }

        if (empty($data['adoption_date'])) {
            $data['adoption_date'] = Carbon::today();
        }

        // Deactivate previous active regulations
        SchoolInternalRegulation::where('academic_year', $academicYear)
            ->where('is_current_active', true)
            ->update(['is_current_active' => false]);

        return SchoolInternalRegulation::create(array_merge($data, ['is_current_active' => true]));
    }

    /**
     * Register or update Parents-Teachers Association (APE).
     */
    public function registerParentsAssociation(array $data): ParentsAssociation
    {
        $academicYear = $data['academic_year'] ?? '2026/2027';

        return ParentsAssociation::updateOrCreate(
            ['academic_year' => $academicYear],
            $data
        );
    }

    /**
     * Record a Parents Association General Assembly or joint school meeting.
     */
    public function recordParentsMeeting(ParentsAssociation $association, array $data): ParentsAssociationMeeting
    {
        $academicYear = $data['academic_year'] ?? $association->academic_year;
        $year = (int) substr($academicYear, 0, 4);

        if (empty($data['meeting_code'])) {
            $data['meeting_code'] = $this->generateParentsMeetingCode($year);
        }

        return $association->meetings()->create(array_merge($data, [
            'academic_year' => $academicYear,
        ]));
    }

    /**
     * Compute institutional telemetry for the governance cockpit.
     */
    public function getGovernanceTelemetry(?string $academicYear = null): array
    {
        $academicYear = $academicYear ?? '2026/2027';

        $sessionsCount = SchoolBoardSession::where('academic_year', $academicYear)->count();
        $deliberatedSessions = SchoolBoardSession::where('academic_year', $academicYear)
            ->whereIn('status', [SchoolBoardSessionStatus::Deliberated->value, SchoolBoardSessionStatus::Approved->value])
            ->count();
        $membersCount = SchoolBoardMember::where('academic_year', $academicYear)
            ->where('is_active', true)
            ->count();

        $activeProject = SchoolProject::where('status', '!=', SchoolProjectStatus::Archived->value)
            ->latest('id')
            ->first();

        $currentRegulations = SchoolInternalRegulation::where('academic_year', $academicYear)
            ->where('is_current_active', true)
            ->first();

        $ape = ParentsAssociation::where('academic_year', $academicYear)->first();
        $apeMeetingsCount = $ape ? $ape->meetings()->count() : 0;

        return [
            'sessions_count' => $sessionsCount,
            'deliberated_sessions' => $deliberatedSessions,
            'members_count' => $membersCount,
            'has_active_project' => $activeProject !== null,
            'project_completion_rate' => $activeProject ? (float) $activeProject->completion_rate_percentage : 0.0,
            'project_title' => $activeProject?->title ?? __('لا يوجد مشروع مفعل حالياً'),
            'has_active_regulations' => $currentRegulations !== null,
            'signed_undertakings_count' => $currentRegulations?->signed_undertakings_count ?? 0,
            'has_active_ape' => $ape !== null && $ape->status === 'active',
            'ape_registered_parents' => $ape?->registered_parents_count ?? 0,
            'ape_meetings_count' => $apeMeetingsCount,
        ];
    }

    /**
     * Assemble printable data for Board Session Official Deliberation PV (A4 Portrait).
     */
    public function assembleBoardSessionPvData(SchoolBoardSession $session): array
    {
        $school = SchoolSetting::first();
        $members = SchoolBoardMember::where('academic_year', $session->academic_year)
            ->where('is_active', true)
            ->get();

        return [
            'session' => $session,
            'school' => $school,
            'members' => $members,
            'institution_name' => $school?->name ?? 'متوسطة العربي التبسي',
            'wilaya' => $school?->wilaya ?? 'الجزائر',
            'daira' => $school?->daira ?? 'الجزائر الوسطى',
            'academic_year' => $session->academic_year,
            'board_label' => $session->board_type?->label() ?? 'مجلس التربية والتسيير',
            'session_term_label' => $session->session_term?->label() ?? 'الدورة العادية الأولى',
            'formatted_session_date' => $session->session_date?->format('Y-m-d') ?? '',
        ];
    }

    /**
     * Assemble printable data for School Strategic Project Summary (A4 Landscape).
     */
    public function assembleSchoolProjectSummaryData(SchoolProject $project): array
    {
        $school = SchoolSetting::first();

        return [
            'project' => $project,
            'school' => $school,
            'institution_name' => $school?->name ?? 'متوسطة العربي التبسي',
            'wilaya' => $school?->wilaya ?? 'الجزائر',
            'daira' => $school?->daira ?? 'الجزائر الوسطى',
            'strategic_axes' => $project->strategic_axes ?? [],
            'quantitative_targets' => $project->quantitative_targets ?? [],
            'status_label' => $project->status?->label() ?? 'قيد الإنجاز',
            'completion_rate' => $project->completion_rate_percentage ?? 0.0,
        ];
    }

    /**
     * Assemble printable data for Parents Association General Assembly PV (A4 Portrait).
     */
    public function assembleParentsGeneralAssemblyPvData(ParentsAssociationMeeting $meeting): array
    {
        $school = SchoolSetting::first();
        $association = $meeting->association;

        return [
            'meeting' => $meeting,
            'association' => $association,
            'school' => $school,
            'institution_name' => $school?->name ?? 'متوسطة العربي التبسي',
            'wilaya' => $school?->wilaya ?? 'الجزائر',
            'daira' => $school?->daira ?? 'الجزائر الوسطى',
            'academic_year' => $meeting->academic_year,
            'meeting_type_label' => $meeting->meeting_type?->label() ?? 'جمعية عامة عادية',
            'formatted_meeting_date' => $meeting->meeting_date?->format('Y-m-d') ?? '',
        ];
    }
}
