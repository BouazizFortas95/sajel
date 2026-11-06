<?php

namespace App\Filament\Pages;

use App\Models\PedagogicalCouncilAttendance;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalCouncilService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class PedagogicalCouncilHub extends Page
{
    protected static string $view = 'filament.pages.pedagogical-council-hub';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    public string $activeTab = 'councils';

    public string $academicYear = '2026/2027';

    public ?int $selectedSubjectId = null;

    // Modal state for Creating Council
    public bool $isCouncilModalOpen = false;

    public ?int $councilSubjectId = null;

    public string $councilType = 'subject_council';

    public ?int $councilTerm = 1;

    public string $councilTitle = '';

    public string $councilHeldAt = '';

    public string $councilLocation = 'قاعة الأساتذة';

    public ?int $councilCoordinatorId = null;

    public string $councilAgenda = '';

    public string $councilPacingNotes = '';

    public string $councilAssessments = '';

    public string $councilRecommendations = '';

    /** @var array<int, int|string> */
    public array $councilTeacherIds = [];

    // Modal state for Recording Seminar
    public bool $isSeminarModalOpen = false;

    public ?int $seminarSubjectId = null;

    public string $seminarTitle = '';

    public string $seminarInspectorName = '';

    public string $seminarHostSchool = '';

    public string $seminarDate = '';

    public string $seminarType = 'inspectoral_seminar';

    public string $seminarTarget = '';

    public ?int $seminarAnimatorId = null;

    public string $seminarObjectives = '';

    public string $seminarCallNumber = '';

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج والتفتيش');
    }

    public static function getNavigationLabel(): string
    {
        return __('مجالس التعليم والتنسيق البيداغوجي');
    }

    public function getTitle(): string|Htmlable
    {
        return __('مجالس التعليم والتنسيق البيداغوجي والندوات التربوية');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManagePedagogicalCouncils();
    }

    public function mount(PedagogicalCouncilService $service): void
    {
        $this->academicYear = $service->resolveAcademicYear();
        $this->councilHeldAt = now()->format('Y-m-d\TH:i');
        $this->seminarDate = now()->addDays(7)->format('Y-m-d');
        $this->councilLocation = __('قاعة الأساتذة');

        $user = Auth::user();
        if ($user && $user->isTeacher() && ! $user->isAdmin()) {
            $this->selectedSubjectId = $user->primary_subject_id;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['councils', 'pacing', 'seminars', 'analytics']) ? $tab : 'councils';
    }

    public function openCouncilModal(): void
    {
        $this->resetCouncilForm();
        $this->isCouncilModalOpen = true;
    }

    public function closeCouncilModal(): void
    {
        $this->isCouncilModalOpen = false;
    }

    public function resetCouncilForm(): void
    {
        $this->councilSubjectId = $this->selectedSubjectId;
        $this->councilType = 'subject_council';
        $this->councilTerm = 1;
        $this->councilTitle = __('مجلس التعليم لمادة ').($this->councilSubjectId ? Subject::find($this->councilSubjectId)?->name : '');
        $this->councilHeldAt = now()->format('Y-m-d\TH:i');
        $this->councilLocation = __('قاعة الأساتذة');
        $this->councilCoordinatorId = null;
        $this->councilAgenda = __('دراسة التوجيهات الرسمية، ضبط وتيرة تنفيذ المنهاج، وتوحيد مواضيع وسلالم تنقيط الفروض والاختبارات الفصلية.');
        $this->councilPacingNotes = '';
        $this->councilAssessments = '';
        $this->councilRecommendations = '';
        $this->councilTeacherIds = [];
    }

    public function saveCouncil(PedagogicalCouncilService $service): void
    {
        $this->validate([
            'councilTitle' => ['required', 'string', 'max:255'],
            'councilHeldAt' => ['required'],
            'councilType' => ['required', 'string'],
        ]);

        try {
            $council = $service->createCouncil([
                'academic_year' => $this->academicYear,
                'subject_id' => $this->councilSubjectId,
                'council_type' => $this->councilType,
                'session_term' => $this->councilTerm,
                'title' => $this->councilTitle,
                'held_at' => $this->councilHeldAt,
                'location' => $this->councilLocation,
                'coordinator_id' => $this->councilCoordinatorId,
                'agenda' => $this->councilAgenda,
                'curriculum_pacing_notes' => $this->councilPacingNotes,
                'unified_assessments_agreement' => $this->councilAssessments,
                'recommendations' => $this->councilRecommendations,
                'status' => 'completed',
            ], $this->councilTeacherIds);

            $this->isCouncilModalOpen = false;

            Notification::make()
                ->title(__('تم توثيق محضر مجلس التعليم بنجاح'))
                ->body(__('المرجع: ').$council->pv_reference)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('تعذّر حفظ محضر المجلس'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openSeminarModal(): void
    {
        $this->resetSeminarForm();
        $this->isSeminarModalOpen = true;
    }

    public function closeSeminarModal(): void
    {
        $this->isSeminarModalOpen = false;
    }

    public function resetSeminarForm(): void
    {
        $this->seminarSubjectId = $this->selectedSubjectId;
        $this->seminarTitle = '';
        $this->seminarInspectorName = '';
        $this->seminarHostSchool = SchoolSetting::current()?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي');
        $this->seminarDate = now()->addDays(7)->format('Y-m-d');
        $this->seminarType = 'inspectoral_seminar';
        $this->seminarTarget = __('كافة أساتذة المادة بالمقاطعة');
        $this->seminarAnimatorId = null;
        $this->seminarObjectives = '';
        $this->seminarCallNumber = '';
    }

    public function saveSeminar(PedagogicalCouncilService $service): void
    {
        $this->validate([
            'seminarSubjectId' => ['required', 'exists:subjects,id'],
            'seminarTitle' => ['required', 'string', 'max:255'],
            'seminarDate' => ['required', 'date'],
        ]);

        try {
            $seminar = $service->recordSeminar([
                'academic_year' => $this->academicYear,
                'subject_id' => $this->seminarSubjectId,
                'title' => $this->seminarTitle,
                'inspector_name' => $this->seminarInspectorName,
                'host_school' => $this->seminarHostSchool,
                'scheduled_at' => $this->seminarDate,
                'seminar_type' => $this->seminarType,
                'target_teachers_description' => $this->seminarTarget,
                'animator_teacher_id' => $this->seminarAnimatorId,
                'objectives' => $this->seminarObjectives,
                'official_call_number' => $this->seminarCallNumber,
                'status' => 'planned',
            ]);

            $this->isSeminarModalOpen = false;

            Notification::make()
                ->title(__('تمت برمجة الندوة التربوية بنجاح'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('تعذّر تسجيل الندوة التربوية'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function toggleTeacherAttendance(int $attendanceId): void
    {
        $att = PedagogicalCouncilAttendance::find($attendanceId);
        if ($att) {
            $att->is_present = ! $att->is_present;
            $att->save();

            Notification::make()
                ->title(__('تم تحديث حضور الأستاذ في المجلس'))
                ->success()
                ->send();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $service = app(PedagogicalCouncilService::class);
        $user = Auth::user();

        $overview = $service->getPedagogicalOverview(
            $this->academicYear,
            $this->selectedSubjectId,
            $user
        );

        $subjects = Subject::all();
        $teachers = User::where(function ($q) {
            $q->whereNotNull('primary_subject_id')
                ->orWhereHas('schedules');
        })->get();

        return array_merge($overview, [
            'subjects' => $subjects,
            'teachers' => $teachers,
            'currentUser' => $user,
        ]);
    }
}
