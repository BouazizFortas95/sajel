<?php

namespace App\Filament\Pages;

use App\Enums\SchoolBoardSessionStatus;
use App\Enums\SchoolBoardType;
use App\Enums\SchoolProjectStatus;
use App\Models\ParentsAssociation;
use App\Models\SchoolBoardMember;
use App\Models\SchoolBoardSession;
use App\Models\SchoolInternalRegulation;
use App\Models\SchoolProject;
use App\Models\User;
use App\Services\SchoolBoardGovernanceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolBoardGovernanceHub extends Page
{
    protected static string $view = 'filament.pages.school-board-governance-hub';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 6;

    public string $activeTab = 'board_sessions'; // board_sessions | members | school_project | parents_and_regulations

    public string $academicYear = '2026/2027';

    public string $searchQuery = '';

    public string $boardTypeFilter = 'all';

    public string $sessionTermFilter = 'all';

    // ─── Modal State: Record Board Session ──────────────────────
    public bool $isSessionModalOpen = false;

    public string $newBoardType = 'ceg';

    public string $newSessionTerm = 'term_1_opening';

    public string $newSessionDate = '';

    public string $newSessionTime = '14:30';

    public string $newMeetingPlace = 'قاعة الاجتماعات الرسمية';

    public ?int $presidentUserId = null;

    public string $presidentName = '';

    public ?int $rapporteurUserId = null;

    public string $rapporteurName = '';

    public int $quorumRequired = 8;

    public int $membersPresentCount = 9;

    public string $agendaItems = '';

    public string $deliberationDetails = '';

    public string $decisionsSummary = '';

    // ─── Modal State: Add Board Member ──────────────────────────
    public bool $isMemberModalOpen = false;

    public string $memberCategory = 'statutory';

    public string $institutionRole = '';

    public ?int $memberUserId = null;

    public string $memberName = '';

    public string $mandateStartDate = '';

    public string $mandateEndDate = '';

    public string $memberPhone = '';

    // ─── Modal State: School Project ────────────────────────────
    public bool $isProjectModalOpen = false;

    public string $projectTitle = '';

    public string $projectStartYear = '2026';

    public string $projectEndYear = '2029';

    public string $projectActionPlan = '';

    public float $projectCompletionRate = 0.0;

    public string $projectEvaluationNotes = '';

    // ─── Modal State: Record Parents Meeting ────────────────────
    public bool $isParentsMeetingModalOpen = false;

    public string $parentsMeetingType = 'ordinary_general_assembly';

    public string $parentsMeetingDate = '';

    public int $parentsAttendeesCount = 45;

    public string $parentsAgendaTopics = '';

    public string $parentsDeliberationNarrative = '';

    public string $parentsRecommendations = '';

    // ─── Modal State: Update APE Profile ────────────────────────
    public bool $isApeModalOpen = false;

    public string $apeName = 'جمعية أولياء تلاميذ متوسطة العربي التبسي';

    public string $apeRegNumber = 'وصـل إيداع رقم 14/2026';

    public string $apeAccreditationDate = '';

    public string $apePresidentName = '';

    public string $apePresidentPhone = '';

    public string $apePresidentEmail = '';

    public int $apeRegisteredParents = 120;

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('مجلس التربية ومشروع المؤسسة (CEG)');
    }

    public function getTitle(): string|Htmlable
    {
        return __('مجلس التربية والتسيير ومشروع المؤسسة (CEG / COG)');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSchoolGovernance();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->newSessionDate = Carbon::today()->format('Y-m-d');
        $this->mandateStartDate = Carbon::parse(substr($this->academicYear, 0, 4).'-09-01')->format('Y-m-d');
        $this->mandateEndDate = Carbon::parse(substr($this->academicYear, 0, 4).'-09-01')->addYears(3)->format('Y-m-d');
        $this->parentsMeetingDate = Carbon::today()->format('Y-m-d');
        $this->apeAccreditationDate = Carbon::parse(substr($this->academicYear, 0, 4).'-10-15')->format('Y-m-d');

        $user = Auth::user();
        $this->presidentName = $user?->name ?? 'مدير المؤسسة التعليمية';
        $this->presidentUserId = $user?->id;
        $this->rapporteurName = 'مستشار رئيسي للتربية / الناظر';

        // Ensure default board members are bootstrapped
        app(SchoolBoardGovernanceService::class)->autoBootstrapBoardMembers($this->academicYear, SchoolBoardType::Ceg);

        // Ensure default school project exists
        app(SchoolBoardGovernanceService::class)->createOrUpdateSchoolProject([
            'project_code' => 'PRJ-2026-01',
            'title' => 'مشروع المؤسسة 2026-2029: تحسين نواتج التعلم وتطوير الحياة المدرسية',
            'start_year' => '2026',
            'end_year' => '2029',
            'status' => SchoolProjectStatus::InProgress->value,
            'completion_rate_percentage' => 35.0,
        ]);

        // Ensure default internal regulations exist
        app(SchoolBoardGovernanceService::class)->adoptInternalRegulations([
            'academic_year' => $this->academicYear,
            'title' => 'النظام الداخلي النموذجي للمؤسسة '.$this->academicYear,
            'version' => '1.0',
            'key_rules_summary' => 'أوقات الدخول والخروج، الهندام المدرسي اللائق، منع الهواتف الذكية، الانضباط والمحافظة على المنشآت والتجهيزات وفق القرار الوزاري 65/771.',
            'signed_undertakings_count' => 380,
        ]);

        // Ensure default APE exists
        app(SchoolBoardGovernanceService::class)->registerParentsAssociation([
            'academic_year' => $this->academicYear,
            'association_name' => $this->apeName,
            'registration_number' => $this->apeRegNumber,
            'accreditation_date' => Carbon::parse($this->apeAccreditationDate),
            'president_name' => 'الأستاذ عبد القادر بن عيسى',
            'president_phone' => '0661234567',
            'registered_parents_count' => $this->apeRegisteredParents,
            'status' => 'active',
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ─── Board Sessions Actions ─────────────────────────────────

    public function openSessionModal(): void
    {
        $this->newSessionDate = Carbon::today()->format('Y-m-d');
        $this->agendaItems = "1. قراءة محضر الجلسة السابقة ومتابعة تنفيذ توصياتها.\n2. المصادقة على مشروع المؤسسة (2026-2029).\n3. عرض ومناقشة النظام الداخلي النموذجي وفق القرار 65/771.\n4. دراسة ظروف انطلاق السنة الدراسية والتنظيم التربوي.";
        $this->deliberationDetails = '';
        $this->decisionsSummary = '';
        $this->isSessionModalOpen = true;
    }

    public function closeSessionModal(): void
    {
        $this->isSessionModalOpen = false;
    }

    public function submitSession(): void
    {
        $this->validate([
            'newSessionDate' => 'required|date',
            'newSessionTime' => 'required|string',
            'presidentName' => 'required|string',
            'rapporteurName' => 'required|string',
            'agendaItems' => 'required|string',
        ]);

        $service = app(SchoolBoardGovernanceService::class);
        $service->recordBoardSession([
            'academic_year' => $this->academicYear,
            'board_type' => $this->newBoardType,
            'session_term' => $this->newSessionTerm,
            'session_date' => Carbon::parse($this->newSessionDate),
            'session_time' => $this->newSessionTime,
            'meeting_place' => $this->newMeetingPlace,
            'president_user_id' => $this->presidentUserId,
            'president_name' => $this->presidentName,
            'rapporteur_user_id' => $this->rapporteurUserId,
            'rapporteur_name' => $this->rapporteurName,
            'quorum_required' => $this->quorumRequired,
            'members_present_count' => $this->membersPresentCount,
            'is_quorum_reached' => $this->membersPresentCount >= $this->quorumRequired,
            'agenda_items' => $this->agendaItems,
            'deliberation_details' => $this->deliberationDetails,
            'decisions_summary' => $this->decisionsSummary,
            'status' => SchoolBoardSessionStatus::Deliberated->value,
        ]);

        $this->closeSessionModal();

        Notification::make()
            ->title(__('تم تسجيل دورة المجلس والمصادقة على محضر المداولات بنجاح'))
            ->success()
            ->send();
    }

    public function submitSessionToDirectorate(int $sessionId): void
    {
        $session = SchoolBoardSession::findOrFail($sessionId);
        $session->status = SchoolBoardSessionStatus::SubmittedToDirectorate;
        $session->save();

        Notification::make()
            ->title(__('تم إرسال محضر المداولة رسمياً لمصالح مديرية التربية للتأشير'))
            ->info()
            ->send();
    }

    public function approveSession(int $sessionId): void
    {
        $session = SchoolBoardSession::findOrFail($sessionId);
        $session->status = SchoolBoardSessionStatus::Approved;
        $session->approval_date = Carbon::today();
        $session->save();

        Notification::make()
            ->title(__('تم التأشير والمصادقة الرسمية النهائية على محضر الدورة'))
            ->success()
            ->send();
    }

    // ─── Members Actions ────────────────────────────────────────

    public function openMemberModal(): void
    {
        $this->memberName = '';
        $this->institutionRole = '';
        $this->memberPhone = '';
        $this->isMemberModalOpen = true;
    }

    public function closeMemberModal(): void
    {
        $this->isMemberModalOpen = false;
    }

    public function submitMember(): void
    {
        $this->validate([
            'memberName' => 'required|string',
            'institutionRole' => 'required|string',
            'memberCategory' => 'required|string',
        ]);

        $service = app(SchoolBoardGovernanceService::class);
        $service->addBoardMember([
            'academic_year' => $this->academicYear,
            'member_category' => $this->memberCategory,
            'institution_role' => $this->institutionRole,
            'user_id' => $this->memberUserId,
            'member_name' => $this->memberName,
            'mandate_start_date' => Carbon::parse($this->mandateStartDate),
            'mandate_end_date' => Carbon::parse($this->mandateEndDate),
            'phone_number' => $this->memberPhone,
            'is_active' => true,
        ]);

        $this->closeMemberModal();

        Notification::make()
            ->title(__('تم قيد عضو المجلس في الهيئة التداولية بنجاح'))
            ->success()
            ->send();
    }

    public function bootstrapDefaultMembers(): void
    {
        $service = app(SchoolBoardGovernanceService::class);
        $service->autoBootstrapBoardMembers($this->academicYear, SchoolBoardType::Ceg);

        Notification::make()
            ->title(__('تمت التهيئة التلقائية لأعضاء المجلس بحكم المنصب والتمثيل القانوني'))
            ->success()
            ->send();
    }

    // ─── School Project Actions ─────────────────────────────────

    public function openProjectModal(): void
    {
        $project = SchoolProject::where('project_code', 'PRJ-2026-01')->first();
        if ($project) {
            $this->projectTitle = $project->title;
            $this->projectStartYear = $project->start_year;
            $this->projectEndYear = $project->end_year;
            $this->projectActionPlan = $project->action_plan ?? '';
            $this->projectCompletionRate = (float) $project->completion_rate_percentage;
            $this->projectEvaluationNotes = $project->evaluation_notes ?? '';
        }
        $this->isProjectModalOpen = true;
    }

    public function closeProjectModal(): void
    {
        $this->isProjectModalOpen = false;
    }

    public function submitProject(): void
    {
        $this->validate([
            'projectTitle' => 'required|string',
            'projectStartYear' => 'required|string',
            'projectEndYear' => 'required|string',
        ]);

        $service = app(SchoolBoardGovernanceService::class);
        $service->createOrUpdateSchoolProject([
            'project_code' => 'PRJ-2026-01',
            'title' => $this->projectTitle,
            'start_year' => $this->projectStartYear,
            'end_year' => $this->projectEndYear,
            'action_plan' => $this->projectActionPlan,
            'completion_rate_percentage' => $this->projectCompletionRate,
            'evaluation_notes' => $this->projectEvaluationNotes,
            'status' => SchoolProjectStatus::InProgress->value,
        ]);

        $this->closeProjectModal();

        Notification::make()
            ->title(__('تم تحيين بيانات مشروع المؤسسة ومؤشرات النجاعة بنجاح'))
            ->success()
            ->send();
    }

    // ─── Parents Association Actions ────────────────────────────

    public function openParentsMeetingModal(): void
    {
        $this->parentsMeetingDate = Carbon::today()->format('Y-m-d');
        $this->parentsAgendaTopics = "1. عرض ومناقشة التقرير الأدبي لأنشطة الجمعية.\n2. عرض التقرير المالي للمداخيل والاشتراكات والمصاريف.\n3. انتخاب وتجديد أعضاء المكتب التنفيذي للجمعية.\n4. تسطير برنامج التعاون والشراكة مع إدارة المؤسسة.";
        $this->parentsDeliberationNarrative = '';
        $this->parentsRecommendations = '';
        $this->isParentsMeetingModalOpen = true;
    }

    public function closeParentsMeetingModal(): void
    {
        $this->isParentsMeetingModalOpen = false;
    }

    public function submitParentsMeeting(): void
    {
        $this->validate([
            'parentsMeetingDate' => 'required|date',
            'parentsAgendaTopics' => 'required|string',
        ]);

        $ape = ParentsAssociation::where('academic_year', $this->academicYear)->firstOrFail();
        $service = app(SchoolBoardGovernanceService::class);

        $service->recordParentsMeeting($ape, [
            'meeting_type' => $this->parentsMeetingType,
            'meeting_date' => Carbon::parse($this->parentsMeetingDate),
            'attendees_count' => $this->parentsAttendeesCount,
            'agenda_topics' => $this->parentsAgendaTopics,
            'deliberation_narrative' => $this->parentsDeliberationNarrative,
            'recommendations_and_actions' => $this->parentsRecommendations,
            'principal_visa_notes' => 'تأشيرة واطلاع مدير المؤسسة على مخرجات الجمعية العامة.',
            'status' => 'approved',
        ]);

        $this->closeParentsMeetingModal();

        Notification::make()
            ->title(__('تم تسجيل محضر الجمعية العامة لأولياء التلاميذ بنجاح'))
            ->success()
            ->send();
    }

    public function openApeModal(): void
    {
        $ape = ParentsAssociation::where('academic_year', $this->academicYear)->first();
        if ($ape) {
            $this->apeName = $ape->association_name;
            $this->apeRegNumber = $ape->registration_number ?? '';
            $this->apeAccreditationDate = $ape->accreditation_date?->format('Y-m-d') ?? '';
            $this->apePresidentName = $ape->president_name;
            $this->apePresidentPhone = $ape->president_phone ?? '';
            $this->apePresidentEmail = $ape->president_email ?? '';
            $this->apeRegisteredParents = $ape->registered_parents_count;
        }
        $this->isApeModalOpen = true;
    }

    public function closeApeModal(): void
    {
        $this->isApeModalOpen = false;
    }

    public function submitApeProfile(): void
    {
        $this->validate([
            'apeName' => 'required|string',
            'apePresidentName' => 'required|string',
        ]);

        $service = app(SchoolBoardGovernanceService::class);
        $service->registerParentsAssociation([
            'academic_year' => $this->academicYear,
            'association_name' => $this->apeName,
            'registration_number' => $this->apeRegNumber,
            'accreditation_date' => $this->apeAccreditationDate ? Carbon::parse($this->apeAccreditationDate) : null,
            'president_name' => $this->apePresidentName,
            'president_phone' => $this->apePresidentPhone,
            'president_email' => $this->apePresidentEmail,
            'registered_parents_count' => $this->apeRegisteredParents,
            'status' => 'active',
        ]);

        $this->closeApeModal();

        Notification::make()
            ->title(__('تم تحديث ملف وبيانات جمعية أولياء التلاميذ بنجاح'))
            ->success()
            ->send();
    }

    // ─── Data Accessors ─────────────────────────────────────────

    public function getTelemetryProperty(): array
    {
        return app(SchoolBoardGovernanceService::class)->getGovernanceTelemetry($this->academicYear);
    }

    public function getSessionsProperty()
    {
        $query = SchoolBoardSession::where('academic_year', $this->academicYear)
            ->orderByDesc('session_date');

        if ($this->boardTypeFilter !== 'all') {
            $query->where('board_type', $this->boardTypeFilter);
        }

        if ($this->sessionTermFilter !== 'all') {
            $query->where('session_term', $this->sessionTermFilter);
        }

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('session_code', 'like', "%{$this->searchQuery}%")
                    ->orWhere('agenda_items', 'like', "%{$this->searchQuery}%")
                    ->orWhere('president_name', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->get();
    }

    public function getMembersProperty()
    {
        return SchoolBoardMember::where('academic_year', $this->academicYear)
            ->orderBy('member_category')
            ->orderBy('id')
            ->get();
    }

    public function getSchoolProjectProperty(): ?SchoolProject
    {
        return SchoolProject::where('project_code', 'PRJ-2026-01')->first();
    }

    public function getRegulationsProperty(): ?SchoolInternalRegulation
    {
        return SchoolInternalRegulation::where('academic_year', $this->academicYear)
            ->where('is_current_active', true)
            ->first();
    }

    public function getParentsAssociationProperty(): ?ParentsAssociation
    {
        return ParentsAssociation::with('meetings')->where('academic_year', $this->academicYear)->first();
    }

    public function getUsersProperty()
    {
        return User::orderBy('name')->get();
    }
}
