<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Filament\Pages\SchoolSupervisionHub;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\SchoolSupervisionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSupervisionHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-25 12:00:00', 'Africa/Algiers'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_access_hub_and_view_accurate_school_metrics(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create();
        $group = $classroom->groups()->create(['name' => 'الفوج أ']);
        $subject = Subject::factory()->create();
        $chronicStudent = Student::factory()->create(['group_id' => $group->id]);
        $lateStudent = Student::factory()->create(['group_id' => $group->id]);

        $completedSchedule = $this->makeSchedule($teacher, $classroom, $subject, '08:00', '09:00');
        $plannedSchedule = $this->makeSchedule($teacher, $classroom, $subject, '09:00', '10:00');
        $missingSchedule = $this->makeSchedule($teacher, $classroom, $subject, '10:00', '11:00');

        $completedLog = DailyLog::factory()->completed()->create([
            'schedule_id' => $completedSchedule->id,
            'date' => '2026-10-25',
        ]);
        DailyLog::factory()->create([
            'schedule_id' => $plannedSchedule->id,
            'date' => '2026-10-25',
            'status' => DailyLogStatus::Planned->value,
        ]);

        Attendance::factory()->create([
            'daily_log_id' => $completedLog->id,
            'student_id' => $chronicStudent->id,
            'status' => AttendanceStatus::Absent->value,
        ]);
        Attendance::factory()->create([
            'daily_log_id' => $completedLog->id,
            'student_id' => $lateStudent->id,
            'status' => AttendanceStatus::Late->value,
        ]);

        foreach (['2026-10-23', '2026-10-24'] as $absenceDate) {
            $priorLog = DailyLog::factory()->completed()->create([
                'schedule_id' => $completedSchedule->id,
                'date' => $absenceDate,
            ]);
            Attendance::factory()->create([
                'daily_log_id' => $priorLog->id,
                'student_id' => $chronicStudent->id,
                'status' => AttendanceStatus::Absent->value,
            ]);
        }

        $openObservation = $this->makeObservation($chronicStudent, 'disciplinary', false);
        $negativeObservation = $this->makeObservation($lateStudent, 'negative', false);
        $this->makeObservation($lateStudent, 'disciplinary', true);
        $this->makeObservation($lateStudent, 'positive', false);

        $overview = app(SchoolSupervisionService::class)->overview(
            '2026-10-25',
            $classroom->id,
            $classroom->level_id,
        );

        $this->assertSame(33, $overview['metrics']['daily_compliance_percentage']);
        $this->assertSame(3, $overview['metrics']['due_sessions_count']);
        $this->assertSame(2, $overview['metrics']['overdue_sessions_count']);
        $this->assertSame(2, $overview['metrics']['attendance_alerts_count']);
        $this->assertSame(2, $overview['metrics']['open_behavior_count']);
        $this->assertCount(1, $overview['repeatAbsentees']);
        $this->assertSame($chronicStudent->id, $overview['repeatAbsentees'][0]['student']->id);
        $this->assertSame(3, $overview['repeatAbsentees'][0]['absence_count']);
        $this->assertCount(2, $overview['observations']);
        $this->assertEqualsCanonicalizing(
            [$openObservation->id, $negativeObservation->id],
            $overview['observations']->pluck('id')->all(),
        );
        $missingSession = collect($overview['sessions'])
            ->first(fn (array $session): bool => $session['schedule']->id === $missingSchedule->id);
        $this->assertSame('missing', $missingSession['status']);

        $this->actingAs($admin)
            ->get('/ar/auth/school-supervision')
            ->assertOk()
            ->assertSee('المراقبة العامة والتفتيش')
            ->assertSee('نسبة توثيق الدفاتر اليومية')
            ->assertSee('33%')
            ->assertSee('مراقبة الغيابات والتأخرات')
            ->assertSee('السجل الانضباطي والسلوك');
    }

    public function test_admin_can_access_hub_in_all_supported_locales(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $labels = [
            'ar' => 'المراقبة العامة والتفتيش',
            'en' => 'General Supervision & Inspection',
            'fr' => 'Supervision générale et inspection',
        ];

        foreach ($labels as $locale => $label) {
            $this->actingAs($admin)
                ->get("/{$locale}/auth/school-supervision")
                ->assertOk()
                ->assertSee($label);
        }
    }

    public function test_teacher_receives_forbidden_response_for_supervision_hub(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($teacher);
        $this->assertFalse(SchoolSupervisionHub::shouldRegisterNavigation());

        $this->get('/ar/auth/school-supervision')
            ->assertForbidden();
    }

    public function test_compliance_uses_selected_weekday_and_reports_only_elapsed_unlogged_sessions(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $completedSchedule = $this->makeSchedule($teacher, $classroom, $subject, '08:00', '09:00');
        $futurePlannedSchedule = $this->makeSchedule($teacher, $classroom, $subject, '13:00', '14:00');
        $missingSchedule = $this->makeSchedule($teacher, $classroom, $subject, '10:00', '11:00');
        $otherWeekday = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => null,
            'subject_id' => $subject->id,
            'day_of_week' => 'monday',
        ]);

        DailyLog::factory()->completed()->create([
            'schedule_id' => $completedSchedule->id,
            'date' => '2026-10-25',
        ]);
        $plannedLog = DailyLog::factory()->create([
            'schedule_id' => $futurePlannedSchedule->id,
            'date' => '2026-10-25',
            'status' => DailyLogStatus::Planned->value,
        ]);

        $overview = app(SchoolSupervisionService::class)->overview('2026-10-25', $classroom->id);

        $this->assertSame(3, $overview['complianceSummary']['total_scheduled_sessions']);
        $this->assertSame(1, $overview['complianceSummary']['completed_sessions']);
        $this->assertSame(33, $overview['complianceSummary']['percentage']);
        $this->assertCount(1, $overview['overdueSessions']);
        $this->assertSame($missingSchedule->id, $overview['overdueSessions'][0]['schedule']->id);
        $statuses = collect($overview['sessions'])->keyBy(fn (array $session): int => $session['schedule']->id);
        $this->assertSame('missing', $statuses[$missingSchedule->id]['status']);
        $this->assertSame(DailyLogStatus::Planned->value, $statuses[$futurePlannedSchedule->id]['status']);
        $this->assertFalse($statuses[$futurePlannedSchedule->id]['overdue']);
        $this->assertArrayNotHasKey($otherWeekday->id, $statuses);

        $this->assertSame($plannedLog->id, $statuses[$futurePlannedSchedule->id]['log']->id);

        DailyLog::factory()->completed()->create([
            'schedule_id' => $completedSchedule->id,
            'date' => '2026-10-18',
        ]);
        $pastOverview = app(SchoolSupervisionService::class)->overview('2026-10-18', $classroom->id);
        $this->assertSame(2, $pastOverview['complianceSummary']['overdue_unlogged_sessions']);
        $this->assertSame([], app(SchoolSupervisionService::class)->overview('2026-10-24')['sessions']);
    }

    public function test_attendance_alerts_and_repeat_absences_are_scoped_to_selected_classroom_and_distinct_dates(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $classroom = Classroom::factory()->create();
        $group = $classroom->groups()->create(['name' => 'الفوج أ']);
        $otherClassroom = Classroom::factory()->create();
        $otherGroup = $otherClassroom->groups()->create(['name' => 'الفوج ب']);
        $subject = Subject::factory()->create();
        $chronicStudent = Student::factory()->create(['group_id' => $group->id]);
        $otherStudent = Student::factory()->create(['group_id' => $otherGroup->id]);
        $schedules = collect(range(1, 5))
            ->map(fn (): Schedule => $this->makeSchedule($teacher, $classroom, Subject::factory()->create(), '08:00', '09:00'));
        $otherSchedule = $this->makeSchedule($teacher, $otherClassroom, $subject, '08:00', '09:00');

        foreach ([
            [$schedules[0], '2026-10-25', $chronicStudent, AttendanceStatus::Absent],
            [$schedules[1], '2026-10-25', $chronicStudent, AttendanceStatus::Absent],
            [$schedules[2], '2026-10-18', $chronicStudent, AttendanceStatus::Absent],
            [$schedules[3], '2026-10-01', $chronicStudent, AttendanceStatus::Absent],
            [$schedules[4], '2026-09-25', $chronicStudent, AttendanceStatus::Absent],
            [$otherSchedule, '2026-10-25', $otherStudent, AttendanceStatus::Late],
        ] as [$schedule, $date, $student, $status]) {
            $log = DailyLog::factory()->completed()->create([
                'schedule_id' => $schedule->id,
                'date' => $date,
            ]);
            Attendance::factory()->create([
                'daily_log_id' => $log->id,
                'student_id' => $student->id,
                'status' => $status->value,
            ]);
        }

        $overview = app(SchoolSupervisionService::class)->overview('2026-10-25', $classroom->id);

        $this->assertCount(2, $overview['attendanceAlerts']);
        $this->assertCount(1, $overview['repeatAbsentees']);
        $this->assertSame($chronicStudent->id, $overview['repeatAbsentees'][0]['student']->id);
        $this->assertSame(3, $overview['repeatAbsentees'][0]['absence_count']);
    }

    public function test_admin_can_resolve_an_open_behavior_follow_up_with_notes(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $student = Student::factory()->create();
        $observation = $this->makeObservation($student, 'disciplinary', false);

        Livewire::actingAs($admin)
            ->test(SchoolSupervisionHub::class)
            ->call('resolveBehaviorObservation', $observation->id, 'تم التواصل مع الولي.')
            ->assertNotified(__('تم تسجيل متابعة الحالة'));

        $this->assertDatabaseHas('behavior_observations', [
            'id' => $observation->id,
            'is_followed_up' => true,
            'follow_up_notes' => 'تم التواصل مع الولي.',
        ]);
    }

    private function makeSchedule(
        User $teacher,
        Classroom $classroom,
        Subject $subject,
        string $startTime,
        string $endTime,
    ): Schedule {
        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => null,
            'subject_id' => $subject->id,
            'day_of_week' => 'sunday',
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    private function makeObservation(Student $student, string $category, bool $followedUp): BehaviorObservation
    {
        return BehaviorObservation::query()->create([
            'student_id' => $student->id,
            'category' => $category,
            'notes' => 'متابعة الحالة المدرسية.',
            'date' => '2026-10-25',
            'is_followed_up' => $followedUp,
        ]);
    }
}
