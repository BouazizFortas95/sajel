<?php

namespace App\Filament\Pages;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubActivityType;
use App\Enums\ClubMemberRole;
use App\Enums\DayOfWeek;
use App\Models\ClubActivity;
use App\Models\ClubMembership;
use App\Models\SchoolClub;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolLifeService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class SchoolLifeHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.school-life-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'clubs'; // 'clubs', 'members', 'activities', 'bilan'

    #[Url(as: 'year')]
    public string $academicYear = '2026-2027';

    #[Url(as: 'type')]
    public ?string $selectedType = null;

    #[Url(as: 'club')]
    public ?int $selectedClubId = null;

    #[Url(as: 'q')]
    public string $searchQuery = '';

    // Modal state: Create/Edit Club
    public bool $isClubModalOpen = false;

    public ?int $editingClubId = null;

    public string $clubName = '';

    public string $clubCode = '';

    public string $clubType = 'scientific';

    public ?string $clubDescription = null;

    public ?string $clubObjectives = null;

    public ?int $clubLeadTeacherId = null;

    public ?int $clubAssistantTeacherId = null;

    public ?string $clubMeetingDay = null;

    public ?string $clubMeetingTime = '16:30 - 17:30';

    public ?string $clubMeetingLocation = null;

    public int $clubMaxMembers = 30;

    public string $clubStatus = 'active';

    // Modal state: Enroll Student
    public bool $isEnrollModalOpen = false;

    public ?int $enrollingClubId = null;

    public ?int $enrollingStudentId = null;

    public string $enrollingRole = 'active_member';

    public ?string $enrollingNotes = null;

    // Modal state: Record Activity
    public bool $isActivityModalOpen = false;

    public ?int $activityClubId = null;

    public string $activityTitle = '';

    public string $activityType = 'workshop';

    public ?string $activityDate = null;

    public ?string $activityStartTime = '14:00';

    public ?string $activityEndTime = '16:00';

    public ?string $activityLocation = null;

    public ?string $activityObjectives = null;

    public ?string $activitySummary = null;

    public int $activityAttendeesCount = 0;

    public bool $activityIsPublic = true;

    public string $activityStatus = 'completed';

    public ?string $activitySupervisorNotes = null;

    public function mount(): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('/', '-', $setting->academic_year);
        }

        $this->activityDate = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isEducationStaff() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('الحياة المدرسية والنوادي');
    }

    public function getTitle(): string
    {
        return __('منظومة الحياة المدرسية والنوادي التربوية');
    }

    public function getHeading(): string
    {
        return __('منظومة الحياة المدرسية والنوادي التربوية اللاصفية');
    }

    public function getSubheading(): ?string
    {
        return __('إدارة ومتابعة النوادي المدرسية (العلمية، البيئية، الثقافية والرياضية)، تشكيلة المكاتب، الأنشطة والشهادات التقديرية');
    }

    /*
     * -------------------------------------------------------------
     * Actions: Club Creation / Editing
     * -------------------------------------------------------------
     */

    public function openNewClubModal(): void
    {
        if (! Auth::user()?->canSuperviseSchoolLife()) {
            abort(403);
        }

        $this->resetClubModal();
        $this->isClubModalOpen = true;
    }

    public function openEditClubModal(int $clubId): void
    {
        $club = SchoolClub::find($clubId);
        if (! $club) {
            return;
        }

        $user = Auth::user();
        if (! $user?->isAdmin() && $club->lead_teacher_id !== $user?->id) {
            abort(403);
        }

        $this->editingClubId = $club->id;
        $this->clubName = $club->name;
        $this->clubCode = $club->code ?? '';
        $this->clubType = $club->type?->value ?? 'scientific';
        $this->clubDescription = $club->description;
        $this->clubObjectives = $club->objectives;
        $this->clubLeadTeacherId = $club->lead_teacher_id;
        $this->clubAssistantTeacherId = $club->assistant_teacher_id;
        $this->clubMeetingDay = $club->meeting_day?->value;
        $this->clubMeetingTime = $club->meeting_time ?? '16:30 - 17:30';
        $this->clubMeetingLocation = $club->meeting_location;
        $this->clubMaxMembers = $club->max_members;
        $this->clubStatus = $club->status?->value ?? 'active';

        $this->isClubModalOpen = true;
    }

    public function closeClubModal(): void
    {
        $this->isClubModalOpen = false;
        $this->resetClubModal();
    }

    protected function resetClubModal(): void
    {
        $this->editingClubId = null;
        $this->clubName = '';
        $this->clubCode = '';
        $this->clubType = 'scientific';
        $this->clubDescription = null;
        $this->clubObjectives = null;
        $this->clubLeadTeacherId = Auth::user()?->id;
        $this->clubAssistantTeacherId = null;
        $this->clubMeetingDay = DayOfWeek::Tuesday->value;
        $this->clubMeetingTime = '16:30 - 17:30';
        $this->clubMeetingLocation = null;
        $this->clubMaxMembers = 30;
        $this->clubStatus = 'active';
    }

    public function saveClub(SchoolLifeService $service): void
    {
        $data = [
            'name' => $this->clubName,
            'code' => $this->clubCode ?: null,
            'type' => $this->clubType,
            'academic_year' => $this->academicYear,
            'description' => $this->clubDescription,
            'objectives' => $this->clubObjectives,
            'lead_teacher_id' => $this->clubLeadTeacherId ?? Auth::id(),
            'assistant_teacher_id' => $this->clubAssistantTeacherId,
            'meeting_day' => $this->clubMeetingDay,
            'meeting_time' => $this->clubMeetingTime,
            'meeting_location' => $this->clubMeetingLocation,
            'max_members' => $this->clubMaxMembers,
            'status' => $this->clubStatus,
        ];

        if ($this->editingClubId) {
            $club = SchoolClub::find($this->editingClubId);
            if ($club) {
                $service->updateClub($club, $data);
                Notification::make()
                    ->title(__('تم تحديث بيانات النادي بنجاح'))
                    ->success()
                    ->send();
            }
        } else {
            $service->createClub($data);
            Notification::make()
                ->title(__('تم تأسيس واعتماد النادي المدرسي بنجاح'))
                ->success()
                ->send();
        }

        $this->closeClubModal();
    }

    /*
     * -------------------------------------------------------------
     * Actions: Enroll Student
     * -------------------------------------------------------------
     */

    public function openEnrollModal(int $clubId): void
    {
        $this->enrollingClubId = $clubId;
        $this->enrollingStudentId = null;
        $this->enrollingRole = ClubMemberRole::ActiveMember->value;
        $this->enrollingNotes = null;
        $this->isEnrollModalOpen = true;
    }

    public function closeEnrollModal(): void
    {
        $this->isEnrollModalOpen = false;
        $this->enrollingClubId = null;
        $this->enrollingStudentId = null;
        $this->enrollingRole = ClubMemberRole::ActiveMember->value;
        $this->enrollingNotes = null;
    }

    public function confirmEnrollStudent(SchoolLifeService $service): void
    {
        if (! $this->enrollingClubId || ! $this->enrollingStudentId) {
            return;
        }

        $club = SchoolClub::find($this->enrollingClubId);
        if (! $club) {
            $this->closeEnrollModal();

            return;
        }

        $role = ClubMemberRole::tryFrom($this->enrollingRole) ?? ClubMemberRole::ActiveMember;

        $service->enrollStudent($club, $this->enrollingStudentId, $role, $this->enrollingNotes);

        Notification::make()
            ->title(__('تم تسجيل انخراط التلميذ بالنادي بنجاح'))
            ->success()
            ->send();

        $this->closeEnrollModal();
    }

    public function deleteMembership(int $membershipId): void
    {
        $membership = ClubMembership::find($membershipId);
        if ($membership) {
            $membership->delete();
            Notification::make()
                ->title(__('تم إلغاء انخراط التلميذ'))
                ->info()
                ->send();
        }
    }

    /*
     * -------------------------------------------------------------
     * Actions: Record Activity
     * -------------------------------------------------------------
     */

    public function openActivityModal(int $clubId): void
    {
        $this->activityClubId = $clubId;
        $this->activityTitle = '';
        $this->activityType = ClubActivityType::Workshop->value;
        $this->activityDate = now()->toDateString();
        $this->activityStartTime = '14:00';
        $this->activityEndTime = '16:00';
        $this->activityLocation = null;
        $this->activityObjectives = null;
        $this->activitySummary = null;
        $this->activityAttendeesCount = 0;
        $this->activityIsPublic = true;
        $this->activityStatus = ClubActivityStatus::Completed->value;
        $this->activitySupervisorNotes = null;

        $this->isActivityModalOpen = true;
    }

    public function closeActivityModal(): void
    {
        $this->isActivityModalOpen = false;
        $this->activityClubId = null;
    }

    public function confirmRecordActivity(SchoolLifeService $service): void
    {
        if (! $this->activityClubId || empty($this->activityTitle)) {
            return;
        }

        $club = SchoolClub::find($this->activityClubId);
        if (! $club) {
            $this->closeActivityModal();

            return;
        }

        $service->recordActivity($club, [
            'title' => $this->activityTitle,
            'activity_type' => $this->activityType,
            'activity_date' => $this->activityDate,
            'start_time' => $this->activityStartTime,
            'end_time' => $this->activityEndTime,
            'location' => $this->activityLocation,
            'objectives' => $this->activityObjectives,
            'summary' => $this->activitySummary,
            'attendees_count' => $this->activityAttendeesCount,
            'is_public' => $this->activityIsPublic,
            'status' => $this->activityStatus,
            'supervisor_notes' => $this->activitySupervisorNotes,
        ]);

        Notification::make()
            ->title(__('تم توثيق نشاط النادي بنجاح'))
            ->success()
            ->send();

        $this->closeActivityModal();
    }

    /*
     * -------------------------------------------------------------
     * View Data Provider
     * -------------------------------------------------------------
     */

    public function getViewData(): array
    {
        $service = app(SchoolLifeService::class);
        $overview = $service->getClubsOverview($this->academicYear);

        // Filter clubs by type if selected
        $filteredClubs = $overview['clubs'];
        if ($this->selectedType) {
            $filteredClubs = $filteredClubs->where('type.value', $this->selectedType);
        }

        if ($this->searchQuery) {
            $q = mb_strtolower($this->searchQuery);
            $filteredClubs = $filteredClubs->filter(
                fn (SchoolClub $c): bool => str_contains(mb_strtolower($c->name), $q) ||
                    str_contains(mb_strtolower((string) $c->code), $q) ||
                    str_contains(mb_strtolower((string) $c->leadTeacher?->name), $q)
            );
        }

        // Available students for enrollment (searchable / listed)
        $students = Student::query()
            ->with('group.classroom')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(150)
            ->get();

        // Teachers for club coordination
        $teachers = User::query()
            ->where('role', 'teacher')
            ->orderBy('name')
            ->get();

        // Memberships list for the members tab
        $membershipsQuery = ClubMembership::query()
            ->with(['club', 'student.group.classroom.level'])
            ->whereHas('club', fn ($q) => $q->where('academic_year', $this->academicYear));

        if ($this->selectedClubId) {
            $membershipsQuery->where('school_club_id', $this->selectedClubId);
        }

        $memberships = $membershipsQuery->latest('id')->get();

        // Activities list for activities tab
        $activitiesQuery = ClubActivity::query()
            ->with(['club.leadTeacher'])
            ->whereHas('club', fn ($q) => $q->where('academic_year', $this->academicYear));

        if ($this->selectedClubId) {
            $activitiesQuery->where('school_club_id', $this->selectedClubId);
        }

        $activities = $activitiesQuery->orderByDesc('activity_date')->get();

        return array_merge($overview, [
            'filteredClubs' => $filteredClubs,
            'students' => $students,
            'teachers' => $teachers,
            'memberships' => $memberships,
            'activities' => $activities,
            'isAdmin' => (bool) Auth::user()?->canSuperviseSchoolLife(),
        ]);
    }
}
