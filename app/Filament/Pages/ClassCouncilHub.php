<?php

namespace App\Filament\Pages;

use App\Models\ClassCouncilSession;
use App\Models\Classroom;
use App\Models\CurriculumPacingAudit;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\SubjectCoordinationProtocol;
use App\Services\ClassCouncilDeliberationService;
use App\Services\ClassCouncilService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ClassCouncilHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.class-council-hub';

    public string $activeTab = 'class_council'; // class_council | subject_coordination | pacing_audit

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    public ?int $groupId = null;

    public int $trimester = 1;

    public string $academicYear = '2026/2027';

    // Coordination state
    public ?int $coordinationSubjectId = null;

    public int $coordinationTrimester = 1;

    public bool $isCoordinationModalOpen = false;

    public ?int $newSubjectId = null;

    public string $newTopic = '';

    public string $newMeetingDate = '';

    public ?int $newCoordinatorId = null;

    public string $newPacingNotes = '';

    public string $newUnifiedExamsNotes = '';

    public string $newDifficulties = '';

    public string $newRecommendations = '';

    /** @var array<int, int|string> */
    public array $newAttendeeIds = [];

    // Pacing Audit state
    public int $auditTrimester = 1;

    public ?int $auditLevelId = null;

    public function mount(): void
    {
        $year = SchoolSetting::current()?->academic_year ?? '2026/2027';
        $this->academicYear = str_replace('-', '/', $year);
        $this->newMeetingDate = now(config('app.timezone', 'Africa/Algiers'))->format('Y-m-d');

        if ($this->classroomId === null) {
            $classroom = $this->getClassroomOptionsProperty()->first();
            $this->classroomId = $classroom?->id;
        }

        $firstSubject = Subject::first();
        $this->coordinationSubjectId = $firstSubject?->id;
        $this->newSubjectId = $firstSubject?->id;
    }

    public function updatedClassroomId(): void
    {
        $this->groupId = null;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التقويمات والامتحانات والمجالس');
    }

    public static function getNavigationLabel(): string
    {
        return __('مجلس القسم');
    }

    public function getClassroomOptionsProperty()
    {
        $user = Auth::user();
        $query = Classroom::query()
            ->with('level.educationPhase')
            ->orderBy('name');

        if ($user && $user->isTeacher()) {
            $query->where(function (Builder $query) use ($user): void {
                $query->whereHas('groups.schedules', fn (Builder $q) => $q->where('user_id', $user->id))
                    ->orWhereHas('schedules', fn (Builder $q) => $q->where('user_id', $user->id));
            });
        }

        return $query->get();
    }

    public function getClassroomProperty(): ?Classroom
    {
        if (! $this->classroomId) {
            return null;
        }

        $classroom = Classroom::find($this->classroomId);
        if (! $classroom) {
            return null;
        }

        $classroom->loadMissing('level.educationPhase');

        $user = Auth::user();
        if ($user && $user->isTeacher()) {
            $hasSchedule = $classroom->groups()
                ->whereHas('schedules', fn (Builder $query) => $query->where('user_id', $user->id))
                ->exists()
                || $classroom->schedules()
                    ->where('user_id', $user->id)
                    ->exists();

            if (! $hasSchedule) {
                $classroom = null;
            }
        }

        return $classroom;
    }

    public function getReportDataProperty(): array
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return [];
        }

        $service = app(ClassCouncilService::class);

        return $service->viewData($classroom, $this->groupId);
    }

    public function getClassroomPrintUrl(): ?string
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return null;
        }

        return route('print.class-council', ['locale' => app()->getLocale(), 'classroom' => $classroom]);
    }

    public function getClassroomPdfUrl(): ?string
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return null;
        }

        return route('print.class-council.pdf', ['locale' => app()->getLocale(), 'classroom' => $classroom]);
    }

    // ─── Phase 47 Statutory Methods ─────────────────────────────

    public function getCurrentSessionProperty(): ?ClassCouncilSession
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return null;
        }

        return ClassCouncilSession::with(['decisions.student', 'president', 'mainTeacher'])
            ->where('classroom_id', $classroom->id)
            ->where('trimester', $this->trimester)
            ->where('academic_year', $this->academicYear)
            ->first();
    }

    public function syncSessionDecisions(): void
    {
        $classroom = $this->getClassroomProperty();
        if (! $classroom) {
            return;
        }

        $service = app(ClassCouncilDeliberationService::class);
        $session = $service->getOrCreateSession($classroom, $this->trimester, $this->academicYear, Auth::user());
        $service->syncDecisionsFromGrades($session);

        Notification::make()
            ->title(__('تم حساب المداولات واستيراد العلامات بنجاح'))
            ->success()
            ->send();
    }

    public function certifyCurrentSession(): void
    {
        $session = $this->getCurrentSessionProperty();
        if (! $session) {
            return;
        }

        $service = app(ClassCouncilDeliberationService::class);
        $service->certifySession($session);

        Notification::make()
            ->title(__('تمت المصادقة الرسمية على محضر مجلس القسم'))
            ->success()
            ->send();
    }

    public function getSessionPvUrlProperty(): ?string
    {
        $session = $this->getCurrentSessionProperty();

        return $session
            ? route('print.class-council-deliberation.pv', ['locale' => app()->getLocale(), 'session' => $session])
            : null;
    }

    public function getSessionPvPdfUrlProperty(): ?string
    {
        $session = $this->getCurrentSessionProperty();

        return $session
            ? route('print.class-council-deliberation.pv.pdf', ['locale' => app()->getLocale(), 'session' => $session])
            : null;
    }

    // ─── Coordination Methods ───────────────────────────────────

    public function openCoordinationModal(): void
    {
        $this->isCoordinationModalOpen = true;
    }

    public function closeCoordinationModal(): void
    {
        $this->isCoordinationModalOpen = false;
    }

    public function saveCoordinationProtocol(): void
    {
        $this->validate([
            'newSubjectId' => 'required|exists:subjects,id',
            'newTopic' => 'required|string|max:255',
            'newMeetingDate' => 'required|date',
        ]);

        $service = app(ClassCouncilDeliberationService::class);
        $service->recordSubjectCoordination([
            'academic_year' => $this->academicYear,
            'subject_id' => $this->newSubjectId,
            'trimester' => $this->coordinationTrimester,
            'meeting_date' => $this->newMeetingDate,
            'coordinator_id' => $this->newCoordinatorId ?? Auth::id(),
            'attendees' => $this->newAttendeeIds,
            'topic' => $this->newTopic,
            'syllabus_pacing_notes' => $this->newPacingNotes,
            'unified_exams_notes' => $this->newUnifiedExamsNotes,
            'difficulties_and_remediations' => $this->newDifficulties,
            'recommendations' => $this->newRecommendations,
            'recorded_by' => Auth::id(),
        ]);

        $this->closeCoordinationModal();

        $this->newTopic = '';
        $this->newPacingNotes = '';
        $this->newUnifiedExamsNotes = '';
        $this->newDifficulties = '';
        $this->newRecommendations = '';
        $this->newAttendeeIds = [];

        Notification::make()
            ->title(__('تم تسجيل محضر جلسة التنسيق البيداغوجي بنجاح'))
            ->success()
            ->send();
    }

    public function getCoordinationProtocolsProperty()
    {
        return SubjectCoordinationProtocol::with(['subject', 'coordinator'])
            ->where('academic_year', $this->academicYear)
            ->where('trimester', $this->coordinationTrimester)
            ->when($this->coordinationSubjectId, fn ($q) => $q->where('subject_id', $this->coordinationSubjectId))
            ->orderByDesc('meeting_date')
            ->get();
    }

    // ─── Pacing Audit Methods ───────────────────────────────────

    public function runPacingAudit(): void
    {
        $service = app(ClassCouncilDeliberationService::class);
        $classrooms = Classroom::query()
            ->when($this->auditLevelId, fn ($q) => $q->where('level_id', $this->auditLevelId))
            ->get();

        $subjects = Subject::all();

        foreach ($classrooms as $classroom) {
            foreach ($subjects as $subject) {
                $service->auditCurriculumPacing(
                    $classroom,
                    $subject,
                    $this->auditTrimester,
                    $this->academicYear,
                    Auth::user()
                );
            }
        }

        Notification::make()
            ->title(__('تم تشغيل التدقيق الآلي وتحديث وضعية تدرج المناهج'))
            ->success()
            ->send();
    }

    public function getPacingAuditsProperty()
    {
        return CurriculumPacingAudit::with(['classroom.level', 'subject', 'teacher'])
            ->where('academic_year', $this->academicYear)
            ->where('trimester', $this->auditTrimester)
            ->when($this->auditLevelId, fn ($q) => $q->whereHas('classroom', fn ($c) => $c->where('level_id', $this->auditLevelId)))
            ->orderBy('classroom_id')
            ->get();
    }
}
