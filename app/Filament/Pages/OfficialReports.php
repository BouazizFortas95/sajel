<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class OfficialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 25;

    protected static string $view = 'filament.pages.official-reports';

    /**
     * The selected report tab. Kept in the query string so a reload keeps the
     * teacher on the same form.
     */
    #[Url(as: 'report')]
    public string $report = 'timetable';

    public ?int $timetableTeacherId = null;

    public ?int $timetableClassroomId = null;

    public ?int $attendanceClassroomId = null;

    public ?int $attendanceGroupId = null;

    public ?int $attendanceSubjectId = null;

    public ?string $attendanceFrom = null;

    public ?string $attendanceTo = null;

    public ?int $transcriptClassroomId = null;

    public ?int $transcriptGroupId = null;

    public ?int $transcriptSubjectId = null;

    public ?string $transcriptFrom = null;

    public ?string $transcriptTo = null;

    public ?int $reportCardStudentId = null;

    public ?int $reportCardClassroomId = null;

    public ?int $reportCardGroupId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function getNavigationLabel(): string
    {
        return __('التقارير الرسمية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    public function getTitle(): string
    {
        return __('التقارير الرسمية');
    }

    public function mount(): void
    {
        $this->report = in_array($this->report, ['timetable', 'attendance', 'transcript', 'report-card'], true)
            ? $this->report
            : 'timetable';

        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();

        $this->attendanceFrom = $from;
        $this->attendanceTo = $to;
        $this->transcriptFrom = $from;
        $this->transcriptTo = $to;

        $user = Auth::user();
        $this->reportCardClassroomId = array_key_first($this->reportCardClassroomOptions);
        $this->reportCardGroupId = array_key_first($this->reportCardGroupOptions);
        $this->reportCardStudentId = array_key_first($this->reportCardStudentOptions);

        if (! $user?->isTeacher()) {
            return;
        }

        $this->timetableTeacherId = $user->id;

        $schedule = Schedule::query()
            ->forTeacher($user->id)
            ->with('group')
            ->orderBy('id')
            ->first();

        if (! $schedule) {
            return;
        }

        $classroomId = $schedule->classroom_id ?? $schedule->group?->classroom_id;

        $this->attendanceClassroomId = $classroomId;
        $this->attendanceGroupId = $schedule->group_id;
        $this->attendanceSubjectId = $schedule->subject_id;

        $this->transcriptClassroomId = $classroomId;
        $this->transcriptGroupId = $schedule->group_id;
        $this->transcriptSubjectId = $schedule->subject_id;
    }

    public function setReport(string $report): void
    {
        $this->report = in_array($report, ['timetable', 'attendance', 'transcript', 'report-card'], true)
            ? $report
            : 'timetable';
    }

    public function updatedAttendanceClassroomId(): void
    {
        $this->attendanceGroupId = null;
        $this->attendanceSubjectId = null;
    }

    public function updatedTranscriptClassroomId(): void
    {
        $this->transcriptGroupId = null;
        $this->transcriptSubjectId = null;
    }

    public function updatedReportCardClassroomId(): void
    {
        $this->reportCardGroupId = null;
        $this->reportCardStudentId = null;
    }

    public function updatedReportCardGroupId(): void
    {
        $this->reportCardStudentId = null;
    }

    /**
     * @return array<int, string>
     */
    public function getTeacherOptionsProperty(): array
    {
        $user = Auth::user();

        if ($user?->isTeacher()) {
            return [$user->id => $user->name];
        }

        return User::query()
            ->where('role', Role::Teacher->value)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getClassroomOptionsProperty(): array
    {
        $query = Classroom::query()
            ->with('level.educationPhase')
            ->orderBy('name');

        $user = Auth::user();

        if ($user?->isTeacher()) {
            $classroomIds = Schedule::query()
                ->forTeacher($user->id)
                ->with('group')
                ->get(['classroom_id', 'group_id'])
                ->map(fn (Schedule $schedule): ?int => $schedule->classroom_id ?? $schedule->group?->classroom_id)
                ->filter()
                ->unique()
                ->values();

            $query->whereIn('id', $classroomIds);
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->id => $classroom->display_name(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getAttendanceGroupOptionsProperty(): array
    {
        return $this->groupOptions($this->attendanceClassroomId);
    }

    /**
     * @return array<int, string>
     */
    public function getTranscriptGroupOptionsProperty(): array
    {
        return $this->groupOptions($this->transcriptClassroomId);
    }

    /**
     * @return array<int, string>
     */
    public function getAttendanceSubjectOptionsProperty(): array
    {
        return $this->subjectOptions($this->attendanceClassroomId);
    }

    /**
     * @return array<int, string>
     */
    public function getTranscriptSubjectOptionsProperty(): array
    {
        return $this->subjectOptions($this->transcriptClassroomId);
    }

    public function timetablePrintUrl(): string
    {
        return $this->reportUrl('print.timetable', [
            'teacher' => $this->timetableTeacherId,
            'classroom' => $this->timetableClassroomId,
        ]);
    }

    public function timetablePdfUrl(): string
    {
        return $this->reportUrl('print.timetable.pdf', [
            'teacher' => $this->timetableTeacherId,
            'classroom' => $this->timetableClassroomId,
        ]);
    }

    public function attendancePrintUrl(): ?string
    {
        if (blank($this->attendanceClassroomId)) {
            return null;
        }

        return $this->reportUrl('print.attendance-summary', [
            'classroom' => $this->attendanceClassroomId,
            'group' => $this->attendanceGroupId,
            'subject' => $this->attendanceSubjectId,
            'from' => $this->attendanceFrom,
            'to' => $this->attendanceTo,
        ]);
    }

    public function attendancePdfUrl(): ?string
    {
        if (blank($this->attendanceClassroomId)) {
            return null;
        }

        return $this->reportUrl('print.attendance-summary.pdf', [
            'classroom' => $this->attendanceClassroomId,
            'group' => $this->attendanceGroupId,
            'subject' => $this->attendanceSubjectId,
            'from' => $this->attendanceFrom,
            'to' => $this->attendanceTo,
        ]);
    }

    public function transcriptPrintUrl(): ?string
    {
        if (blank($this->transcriptClassroomId) || blank($this->transcriptSubjectId)) {
            return null;
        }

        return $this->reportUrl('print.grade-transcript', [
            'classroom' => $this->transcriptClassroomId,
            'group' => $this->transcriptGroupId,
            'subject' => $this->transcriptSubjectId,
            'from' => $this->transcriptFrom,
            'to' => $this->transcriptTo,
        ]);
    }

    public function transcriptPdfUrl(): ?string
    {
        if (blank($this->transcriptClassroomId) || blank($this->transcriptSubjectId)) {
            return null;
        }

        return $this->reportUrl('print.grade-transcript.pdf', [
            'classroom' => $this->transcriptClassroomId,
            'group' => $this->transcriptGroupId,
            'subject' => $this->transcriptSubjectId,
            'from' => $this->transcriptFrom,
            'to' => $this->transcriptTo,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function getReportCardStudentOptionsProperty(): array
    {
        $query = Student::query()->with(['group.classroom.level']);
        $user = Auth::user();

        if ($user?->isTeacher()) {
            $studentIds = Schedule::query()
                ->forTeacher($user->id)
                ->get()
                ->flatMap(fn (Schedule $schedule) => Student::query()->forSchedule($schedule)->pluck('students.id'))
                ->unique()
                ->values();

            $query->whereKey($studentIds);
        }

        return $query
            ->when($this->reportCardClassroomId, fn (Builder $query): Builder => $query->whereHas(
                'group',
                fn (Builder $query): Builder => $query->where('classroom_id', $this->reportCardClassroomId),
            ))
            ->when($this->reportCardGroupId, fn (Builder $query): Builder => $query->where('group_id', $this->reportCardGroupId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => implode(' — ', array_filter([
                    $student->full_name,
                    $student->group?->classroom?->name,
                    $student->group?->name,
                ])),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getReportCardClassroomOptionsProperty(): array
    {
        return $this->classroomOptions;
    }

    /**
     * @return array<int, string>
     */
    public function getReportCardGroupOptionsProperty(): array
    {
        if (blank($this->reportCardClassroomId)) {
            return [];
        }

        $groups = Group::query()
            ->where('classroom_id', $this->reportCardClassroomId)
            ->when(Auth::user()?->isTeacher(), function (Builder $query): Builder {
                return $query->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('schedules')
                        ->where('schedules.user_id', Auth::id())
                        ->where(function ($query): void {
                            $query->whereColumn('schedules.group_id', 'groups.id')
                                ->orWhere(function ($query): void {
                                    $query->whereNull('schedules.group_id')
                                        ->whereColumn('schedules.classroom_id', 'groups.classroom_id');
                                });
                        });
                });
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        return $groups->all();
    }

    public function reportCardPrintUrl(): ?string
    {
        return $this->reportCardUrl('print.student-report-card');
    }

    public function reportCardPdfUrl(): ?string
    {
        return $this->reportCardUrl('print.student-report-card.pdf');
    }

    /**
     * @return array<int, string>
     */
    private function groupOptions(?int $classroomId): array
    {
        if (blank($classroomId)) {
            return [];
        }

        return Group::query()
            ->where('classroom_id', $classroomId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function subjectOptions(?int $classroomId): array
    {
        if (blank($classroomId)) {
            return [];
        }

        return Subject::query()
            ->whereIn(
                'id',
                Schedule::query()
                    ->when(
                        Auth::user()?->isTeacher(),
                        fn (Builder $query): Builder => $query->where('user_id', Auth::id()),
                    )
                    ->where(function (Builder $query) use ($classroomId): void {
                        $query->where('classroom_id', $classroomId)
                            ->orWhereHas('group', fn (Builder $query): Builder => $query->where('classroom_id', $classroomId));
                    })
                    ->select('subject_id'),
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function reportUrl(string $name, array $parameters): string
    {
        return route($name, array_filter([
            'locale' => app()->getLocale(),
            ...$parameters,
        ], fn (mixed $value): bool => filled($value)));
    }

    private function reportCardUrl(string $name): ?string
    {
        if (blank($this->reportCardStudentId)) {
            return null;
        }

        return $this->reportUrl($name, ['student' => $this->reportCardStudentId]);
    }
}
