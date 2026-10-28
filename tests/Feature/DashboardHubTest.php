<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Livewire\DashboardStats;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\PedagogicalRemediation;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DashboardHubTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_period_bounds_use_algeria_time_and_sunday_first_weeks(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $service = app(DashboardMetricsService::class);

        $this->assertSame('Africa/Algiers', $service->timezone());
        $this->assertSame('2026-10-28', $service->today()->toDateString());

        $today = $service->periodBounds('today');
        $this->assertSame('2026-10-28', $today['start']->toDateString());
        $this->assertSame('2026-10-28', $today['end']->toDateString());

        $week = $service->periodBounds('week');
        $this->assertSame('2026-10-25', $week['start']->toDateString());
        $this->assertSame('2026-10-31', $week['end']->toDateString());

        $month = $service->periodBounds('month');
        $this->assertSame('2026-10-01', $month['start']->toDateString());
        $this->assertSame('2026-10-31', $month['end']->toDateString());
    }

    public function test_teacher_tabs_omit_institutional_and_admin_tabs_include_it(): void
    {
        $teacher = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $teacherHtml = Livewire::actingAs($teacher)->test(DashboardStats::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('dashboard-tab-overview', $teacherHtml);
        $this->assertStringContainsString('dashboard-tab-lessons', $teacherHtml);
        $this->assertStringContainsString('dashboard-tab-support', $teacherHtml);
        $this->assertStringNotContainsString('dashboard-tab-institutional', $teacherHtml);

        $adminHtml = Livewire::actingAs($admin)->test(DashboardStats::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('dashboard-tab-institutional', $adminHtml);
    }

    public function test_teacher_cannot_read_institutional_data_directly_or_via_query_string(): void
    {
        $teacher = User::factory()->create();

        $this->expectHttpException(403, function () use ($teacher): void {
            app(DashboardMetricsService::class)->institutional($teacher);
        });

        $this->actingAs($teacher);
        app()->setLocale('en');

        $response = $this->get('/en/auth?tab=institutional')->assertOk();

        // The tab is hidden and the request falls back to the overview rather
        // than exposing institutional data.
        $response->assertDontSee('dashboard-tab-institutional', false);
        $response->assertDontSee('Official identity');
    }

    public function test_forged_classroom_and_subject_filters_are_rejected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $ownClassroom = Classroom::factory()->create();
        $otherClassroom = Classroom::factory()->create();
        $ownSubject = Subject::factory()->create(['name' => 'Own subject']);
        $otherSubject = Subject::factory()->create(['name' => 'Other subject']);

        $this->schedule($teacher, $ownClassroom, $ownSubject, DayOfWeek::Wednesday);
        $this->schedule($otherTeacher, $otherClassroom, $otherSubject, DayOfWeek::Wednesday);

        $this->actingAs($teacher);

        $this->get('/en/auth?classroom='.$otherClassroom->id)->assertForbidden();
        $this->get('/en/auth?subject='.$otherSubject->id)->assertForbidden();
        $this->get('/en/auth?classroom=999999')->assertForbidden();
        $this->get('/en/auth?subject=999999')->assertForbidden();

        $this->get('/en/auth?classroom='.$ownClassroom->id.'&subject='.$ownSubject->id)
            ->assertOk();

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/en/auth?classroom='.$otherClassroom->id.'&subject='.$otherSubject->id)
            ->assertOk();
    }

    public function test_teacher_metrics_and_filter_options_are_scoped_to_their_own_schedule(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $ownClassroom = Classroom::factory()->create();
        $otherClassroom = Classroom::factory()->create();
        $ownSubject = Subject::factory()->create(['name' => 'Own subject']);
        $otherSubject = Subject::factory()->create(['name' => 'Other subject']);

        $ownSchedule = $this->schedule($teacher, $ownClassroom, $ownSubject, DayOfWeek::Wednesday);
        $otherSchedule = $this->schedule($otherTeacher, $otherClassroom, $otherSubject, DayOfWeek::Wednesday);

        $ownLog = $this->log($ownSchedule, '2026-10-28', DailyLogStatus::Completed);
        $otherLog = $this->log($otherSchedule, '2026-10-28', DailyLogStatus::Completed);

        $service = app(DashboardMetricsService::class);
        $own = $service->overview($teacher);
        $other = $service->overview($otherTeacher);
        $admin = $service->overview(User::factory()->admin()->create());

        $this->assertSame(1, $own['logs_today']);
        $this->assertSame(1, $own['completed_today']);
        $this->assertSame(1, $own['weekly_sessions']);
        $this->assertSame(1, $own['subjects_count']);
        $this->assertSame($ownLog->id, $service->latestLogForToday($teacher)?->id);

        $this->assertSame(1, $other['logs_today']);
        $this->assertSame($otherLog->id, $service->latestLogForToday($otherTeacher)?->id);

        $this->assertSame(2, $admin['logs_today']);
        $this->assertSame(2, $admin['completed_today']);

        $ownOptions = $service->filterOptions($teacher);
        $this->assertSame([$ownClassroom->id], array_keys($ownOptions['classrooms']));
        $this->assertSame([$ownSubject->id], array_keys($ownOptions['subjects']));

        $adminOptions = $service->filterOptions(User::factory()->admin()->create());
        $this->assertArrayHasKey($ownClassroom->id, $adminOptions['classrooms']);
        $this->assertArrayHasKey($otherClassroom->id, $adminOptions['classrooms']);
    }

    public function test_group_targeted_schedule_exposes_its_classroom_to_the_teacher(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Group subject']);
        $group = $classroom->groups()->create(['name' => 'Group A']);

        $this->schedule(
            $teacher,
            null,
            $subject,
            DayOfWeek::Wednesday,
            '08:00',
            '09:00',
            $group,
        );

        $service = app(DashboardMetricsService::class);

        $options = $service->filterOptions($teacher);
        $this->assertArrayHasKey($classroom->id, $options['classrooms']);
        $this->assertSame(1, $service->overview($teacher)['weekly_sessions']);
    }

    public function test_attendance_metrics_distinguish_due_recorded_and_not_recorded_sessions(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Attendance subject']);
        $student = Student::factory()->create();

        $days = [
            ['2026-10-25', DayOfWeek::Sunday, AttendanceStatus::Absent],
            ['2026-10-26', DayOfWeek::Monday, AttendanceStatus::Absent],
            ['2026-10-27', DayOfWeek::Tuesday, AttendanceStatus::Absent],
            ['2026-10-28', DayOfWeek::Wednesday, AttendanceStatus::Present],
        ];

        foreach ($days as [$date, $day, $status]) {
            $schedule = $this->schedule($teacher, $classroom, $subject, $day);
            $log = $this->log($schedule, $date, DailyLogStatus::Completed);
            Attendance::factory()->for($log)->for($student)->create(['status' => $status]);
        }

        // A due session with no log is not recorded, but is not an absence.
        $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '09:00', '10:00');

        // A future lesson is not due yet even if it has a completed log.
        $future = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '10:00', '11:00');
        $futureLog = $this->log($future, '2026-10-28', DailyLogStatus::Completed);
        Attendance::factory()->for($futureLog)->for($student)->create([
            'status' => AttendanceStatus::Present,
        ]);

        $service = app(DashboardMetricsService::class);

        $today = $service->attendance($teacher, 'today');
        $this->assertSame(2, $today['due_sessions']);
        $this->assertSame(1, $today['recorded_sessions']);
        $this->assertSame(1, $today['not_recorded_sessions']);
        $this->assertSame(1, $today['status_counts'][AttendanceStatus::Present->value]);
        $this->assertSame(0, $today['status_counts'][AttendanceStatus::Absent->value]);
        $this->assertSame(1, $today['attendance_rows']);
        $this->assertSame(0, $today['repeat_absentees']);

        $week = $service->attendance($teacher, 'week');
        $this->assertSame(5, $week['due_sessions']);
        $this->assertSame(4, $week['recorded_sessions']);
        $this->assertSame(1, $week['not_recorded_sessions']);
        $this->assertSame(4, $week['attendance_rows']);
        $this->assertSame(3, $week['status_counts'][AttendanceStatus::Absent->value]);
        $this->assertSame(1, $week['repeat_absentees']);
        $this->assertSame(
            $week['due_sessions'] - $week['recorded_sessions'],
            $week['not_recorded_sessions'],
        );
    }

    public function test_holidays_remove_sessions_from_overview_and_attendance(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Holiday subject']);
        $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '08:00', '09:00');

        $service = app(DashboardMetricsService::class);

        $this->assertSame(1, $service->overview($teacher)['pending_count']);
        $this->assertSame(1, $service->attendance($teacher, 'today')['not_recorded_sessions']);

        SchoolHoliday::factory()->create([
            'title' => 'Autumn break',
            'start_date' => '2026-10-28',
            'end_date' => '2026-10-28',
        ]);

        $overview = $service->overview($teacher);
        $attendance = $service->attendance($teacher, 'today');

        $this->assertNotNull($overview['holiday']);
        $this->assertSame(0, $overview['sessions_today']);
        $this->assertNull($overview['next_lesson']);
        $this->assertSame(0, $overview['pending_count']);
        $this->assertSame(0, $attendance['due_sessions']);
        $this->assertSame(0, $attendance['not_recorded_sessions']);
    }

    public function test_pending_sessions_include_missing_and_planned_but_exclude_resolved_and_future(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Pending subject']);

        $missing = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '08:00', '09:00');
        $plannedSchedule = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '09:00', '10:00');
        $this->log($plannedSchedule, '2026-10-28', DailyLogStatus::Planned);
        $future = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '10:00', '11:00');
        $this->log($future, '2026-10-28', DailyLogStatus::Completed);
        $completed = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '07:00', '08:00');
        $this->log($completed, '2026-10-28', DailyLogStatus::Completed);
        $cancelled = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday, '06:00', '07:00');
        $this->log($cancelled, '2026-10-28', DailyLogStatus::Cancelled);

        $service = app(DashboardMetricsService::class);
        $lessons = $service->lessons($teacher, 'week');

        $this->assertSame(2, $lessons['pending_count']);
        $this->assertSame(1, $lessons['missing_count']);
        $this->assertSame(1, $lessons['awaiting_completion_count']);
        $this->assertSame(
            [$missing->id, $plannedSchedule->id],
            $lessons['pending']->pluck('schedule.id')->all(),
        );
    }

    public function test_assessment_metrics_report_grade_entry_progress_without_averages(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Assessment subject']);
        $schedule = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday);
        $otherSchedule = $this->schedule(
            $otherTeacher,
            Classroom::factory()->create(),
            Subject::factory()->create(['name' => 'Other assessment subject']),
            DayOfWeek::Wednesday,
        );

        $todayAssessment = $this->assessment($schedule, '2026-10-28');
        $yesterdayAssessment = $this->assessment($schedule, '2026-10-27');
        $this->assessment($otherSchedule, '2026-10-28');

        $student = Student::factory()->create();
        $secondStudent = Student::factory()->create();
        Grade::factory()->for($todayAssessment)->for($student)->graded(15)->create();
        Grade::factory()->for($todayAssessment)->for($secondStudent)->create();

        for ($offset = 1; $offset <= 7; $offset++) {
            $this->assessment($schedule, Carbon::create(2026, 10, 28)->addDays($offset)->toDateString());
        }

        $service = app(DashboardMetricsService::class);

        $today = $service->assessments($teacher, 'today');
        $this->assertSame(1, $today['recent_count']);
        $this->assertSame(2, $today['grade_rows']);
        $this->assertSame(1, $today['scored_rows']);
        $this->assertSame(0, $today['not_started_count']);
        $this->assertSame(1, $today['assessments_with_missing_scores']);
        $this->assertSame(7, $today['upcoming_count']);
        $this->assertCount(5, $today['upcoming']);

        $week = $service->assessments($teacher, 'week');
        $this->assertSame(2, $week['recent_count']);
        $this->assertSame(2, $week['grade_rows']);
        $this->assertSame(1, $week['scored_rows']);
        $this->assertSame(1, $week['not_started_count']);
        $this->assertSame(1, $week['assessments_with_missing_scores']);

        $admin = $service->assessments(User::factory()->admin()->create(), 'today');
        $this->assertSame(2, $admin['recent_count']);
    }

    public function test_curriculum_metrics_delegate_to_the_progress_service_and_scope_teachers(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->for($level)->create();
        $subject = Subject::factory()->create(['name' => 'Curriculum subject']);
        $schedule = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday);

        $domain = CurriculumDomain::factory()->for($subject)->for($level)->create();
        $sequence = CurriculumSequence::factory()->for($domain, 'domain')->create();
        $first = CurriculumResource::factory()->for($sequence, 'sequence')->create();
        $second = CurriculumResource::factory()->for($sequence, 'sequence')->create();
        CurriculumResource::factory()->for($sequence, 'sequence')->create();

        $this->log($schedule, '2026-10-28', DailyLogStatus::Completed, $first);
        $plannedSchedule = $this->schedule(
            $teacher,
            $classroom,
            $subject,
            DayOfWeek::Wednesday,
            '09:00',
            '10:00',
        );
        $this->log($plannedSchedule, '2026-10-28', DailyLogStatus::Planned, $second);

        $otherLevel = Level::factory()->create();
        $otherClassroom = Classroom::factory()->for($otherLevel)->create();
        $otherSubject = Subject::factory()->create(['name' => 'Other curriculum subject']);
        $this->schedule($otherTeacher, $otherClassroom, $otherSubject, DayOfWeek::Wednesday);
        $otherDomain = CurriculumDomain::factory()->for($otherSubject)->for($otherLevel)->create();
        $otherSequence = CurriculumSequence::factory()->for($otherDomain, 'domain')->create();
        CurriculumResource::factory()->for($otherSequence, 'sequence')->create();

        $service = app(DashboardMetricsService::class);

        $own = $service->curriculum($teacher);
        $this->assertSame(1, $own['row_count']);
        $this->assertSame(3, $own['total_resources']);
        $this->assertSame(1, $own['completed_resources']);
        $this->assertSame(33, $own['percentage']);
        $this->assertSame(0, $own['rows']->first()['completed_sequences']);

        $admin = $service->curriculum(User::factory()->admin()->create());
        $this->assertSame(2, $admin['row_count']);

        $filtered = $service->curriculum(User::factory()->admin()->create(), $classroom->id, $subject->id);
        $this->assertSame(1, $filtered['row_count']);
        $this->assertSame(3, $filtered['total_resources']);
    }

    public function test_support_metrics_are_aggregated_and_teacher_scoped(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Support subject']);
        $schedule = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday);
        $otherSchedule = $this->schedule(
            $otherTeacher,
            Classroom::factory()->create(),
            Subject::factory()->create(['name' => 'Other support subject']),
            DayOfWeek::Wednesday,
        );
        $student = Student::factory()->create();

        $this->observation($schedule, $student, $teacher, 'negative', '2026-10-28', false);
        $this->observation($schedule, $student, $teacher, 'positive', '2026-10-28', true);
        $this->observation($schedule, $student, $teacher, 'observation', '2026-10-27', false);
        $this->observation($otherSchedule, $student, $otherTeacher, 'negative', '2026-10-28', false);

        $this->remediation($schedule, $student, $teacher, 'planned', '2026-10-28');
        $this->remediation($schedule, $student, $teacher, 'completed', '2026-10-27');
        $this->remediation($otherSchedule, $student, $otherTeacher, 'planned', '2026-10-28');

        $service = app(DashboardMetricsService::class);

        $today = $service->support($teacher, 'today');
        $this->assertSame(2, $today['behavior_total']);
        $this->assertSame(1, $today['positive_count']);
        $this->assertSame(1, $today['open_concerns']);
        $this->assertSame(1, $today['remediation_total']);
        $this->assertSame(1, $today['open_remediations']);

        $week = $service->support($teacher, 'week');
        $this->assertSame(3, $week['behavior_total']);
        $this->assertSame(2, $week['open_concerns']);
        $this->assertSame(2, $week['remediation_total']);
        $this->assertSame(1, $week['open_remediations']);

        $admin = $service->support(User::factory()->admin()->create(), 'today');
        $this->assertSame(3, $admin['behavior_total']);
        $this->assertSame(2, $admin['remediation_total']);
    }

    public function test_a_teacher_sees_a_follow_up_they_recorded_without_a_timetable_link(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $student = Student::factory()->create();

        BehaviorObservation::factory()->create([
            'student_id' => $student->id,
            'schedule_id' => null,
            'category' => 'negative',
            'notes' => 'Recorded by the teacher',
            'date' => '2026-10-28',
            'is_followed_up' => false,
        ])->forceFill(['recorded_by_id' => $teacher->id])->save();

        $service = app(DashboardMetricsService::class);

        $this->assertSame(1, $service->support($teacher, 'today')['behavior_total']);
        $this->assertSame(0, $service->support($otherTeacher, 'today')['behavior_total']);
    }

    public function test_url_state_selects_the_tab_and_filters_and_clear_filters_resets_them(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'URL state subject']);
        $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday);

        $this->actingAs($teacher);
        app()->setLocale('en');

        $response = $this->get(
            '/en/auth?tab=attendance&period=week&classroom='.$classroom->id.'&subject='.$subject->id,
        )->assertOk();

        $response->assertSee('id="dashboard-tab-attendance"', false);
        $response->assertSee('aria-selected="true"', false);
        $response->assertSee('Documentation rate');
        $response->assertDontSee('Smart log');

        Livewire::actingAs($teacher)->test(DashboardStats::class)
            ->set('period', 'week')
            ->set('classroomId', $classroom->id)
            ->set('subjectId', $subject->id)
            ->call('clearFilters')
            ->assertSet('period', 'today')
            ->assertSet('classroomId', null)
            ->assertSet('subjectId', null)
            ->assertSet('activeTab', 'overview');
    }

    public function test_unknown_period_values_fall_back_to_today(): void
    {
        $teacher = User::factory()->create();

        Livewire::actingAs($teacher)->test(DashboardStats::class)
            ->set('period', 'year')
            ->assertSet('period', 'today');
    }

    public function test_empty_tabs_render_clear_states_without_errors(): void
    {
        $teacher = User::factory()->create();

        app()->setLocale('en');

        $component = Livewire::actingAs($teacher)->test(DashboardStats::class)
            ->assertOk()
            ->assertSee('No lessons remaining today');

        $component->call('setTab', 'attendance')
            ->assertOk()
            ->assertSee('No attendance data for this period');

        $component->call('setTab', 'lessons')
            ->assertOk()
            ->assertSee('All lessons documented');

        $component->call('setTab', 'assessments')
            ->assertOk()
            ->assertSee('No upcoming assessments');

        $component->call('setTab', 'curriculum')
            ->assertOk()
            ->assertSee('No curriculum data for this scope');

        $component->call('setTab', 'support')
            ->assertOk()
            ->assertSee('No support data for this period');
    }

    public function test_localization_and_layout_direction_follow_the_active_locale(): void
    {
        $teacher = User::factory()->create();

        app()->setLocale('ar');
        $arabic = Livewire::actingAs($teacher)->test(DashboardStats::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('dir="rtl"', $arabic);
        $this->assertStringContainsString('نظرة عامة', $arabic);
        $this->assertStringContainsString('الحضور والغياب', $arabic);
        $this->assertStringContainsString('Africa/Algiers', $arabic);

        app()->setLocale('en');
        $english = Livewire::actingAs($teacher)->test(DashboardStats::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('dir="ltr"', $english);
        $this->assertStringContainsString('Overview', $english);
        $this->assertStringContainsString('Attendance', $english);

        app()->setLocale('fr');
        $french = Livewire::actingAs($teacher)->test(DashboardStats::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('dir="ltr"', $french);
        $this->assertStringContainsString('Vue d’ensemble', $french);
        $this->assertStringContainsString('Présence et absence', $french);
    }

    public function test_dashboard_rendering_is_read_only_and_does_not_poll(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Read-only subject']);
        $schedule = $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday);
        $log = $this->log($schedule, '2026-10-28', DailyLogStatus::Completed);
        $student = Student::factory()->create();
        Attendance::factory()->for($log)->for($student)->create();

        $countsBefore = [
            'logs' => DailyLog::query()->count(),
            'attendances' => Attendance::query()->count(),
            'assessments' => Assessment::query()->count(),
            'grades' => Grade::query()->count(),
            'observations' => BehaviorObservation::query()->count(),
            'remediations' => PedagogicalRemediation::query()->count(),
        ];

        $component = Livewire::actingAs($teacher)->test(DashboardStats::class)->assertOk();

        foreach (['schedule', 'attendance', 'lessons', 'assessments', 'curriculum', 'support'] as $tab) {
            $component->call('setTab', $tab)->assertOk();
        }

        $html = $component->html();

        $this->assertStringNotContainsString('wire:poll', $html);
        $this->assertSame($countsBefore, [
            'logs' => DailyLog::query()->count(),
            'attendances' => Attendance::query()->count(),
            'assessments' => Assessment::query()->count(),
            'grades' => Grade::query()->count(),
            'observations' => BehaviorObservation::query()->count(),
            'remediations' => PedagogicalRemediation::query()->count(),
        ]);
    }

    public function test_forged_livewire_tab_and_filter_properties_are_rejected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $otherClassroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Forged filter subject']);
        $this->schedule($otherTeacher, $otherClassroom, $subject, DayOfWeek::Wednesday);

        Livewire::actingAs($teacher)
            ->test(DashboardStats::class)
            ->call('setTab', 'institutional')
            ->assertForbidden();

        Livewire::actingAs($teacher)
            ->test(DashboardStats::class)
            ->set('classroomId', $otherClassroom->id)
            ->assertForbidden();
    }

    public function test_dashboard_query_count_does_not_grow_with_the_number_of_sessions(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Bounded query subject']);
        $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday);

        $component = Livewire::actingAs($teacher)->test(DashboardStats::class)->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->call('setTab', 'attendance')->assertOk();
        $baseline = count(DB::getQueryLog());

        for ($index = 0; $index < 20; $index++) {
            $schedule = $this->schedule(
                $teacher,
                $classroom,
                $subject,
                DayOfWeek::Wednesday,
                '08:00',
                '09:00',
            );
            $this->log($schedule, '2026-10-28', DailyLogStatus::Completed);
        }

        DB::flushQueryLog();
        $component->call('setTab', 'attendance')->assertOk();
        $withManySessions = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $baseline,
            $withManySessions,
            'Rendering an aggregate tab must not add queries per session.',
        );
    }

    public function test_curriculum_list_is_capped_while_counts_cover_every_pair(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $admin = User::factory()->admin()->create();
        $level = Level::factory()->create();

        for ($index = 0; $index < 8; $index++) {
            $classroom = Classroom::factory()->for($level)->create([
                'name' => 'Capped class '.$index,
            ]);
            $subject = Subject::factory()->create(['name' => 'Capped subject '.$index]);
            $teacher = User::factory()->create();

            $this->schedule(
                $teacher,
                $classroom,
                $subject,
                DayOfWeek::Wednesday,
                '08:00',
                '09:00',
            );

            $domain = CurriculumDomain::factory()->for($subject)->for($level)->create();
            $sequence = CurriculumSequence::factory()->for($domain, 'domain')->create();
            CurriculumResource::factory()->for($sequence, 'sequence')->create();
        }

        $data = app(DashboardMetricsService::class)->curriculum($admin);

        $this->assertSame(8, $data['row_count']);
        $this->assertCount(6, $data['rows']);
        $this->assertSame(8, $data['total_resources']);
        $this->assertSame(0, $data['completed_resources']);
    }

    public function test_period_control_only_appears_on_period_scoped_tabs(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0, 'Africa/Algiers'));

        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Contextual filter subject']);
        $this->schedule($teacher, $classroom, $subject, DayOfWeek::Wednesday);

        $this->actingAs($teacher);
        app()->setLocale('en');

        $this->get('/en/auth?tab=schedule')
            ->assertOk()
            ->assertDontSee('wire:model.live="period"', false)
            ->assertSee('wire:model.live="classroomId"', false);

        $this->get('/en/auth?tab=attendance')
            ->assertOk()
            ->assertSee('wire:model.live="period"', false);

        $this->actingAs(User::factory()->admin()->create());

        $this->get('/en/auth?tab=institutional')
            ->assertOk()
            ->assertDontSee('wire:model.live="classroomId"', false)
            ->assertSee('Official identity');
    }

    private function schedule(
        User $teacher,
        ?Classroom $classroom,
        Subject $subject,
        DayOfWeek $day,
        string $start = '08:00',
        string $end = '09:00',
        ?Group $group = null,
    ): Schedule {
        return Schedule::factory()->for($teacher)->for($subject)->create([
            'classroom_id' => $group ? null : $classroom?->id,
            'group_id' => $group?->id,
            'day_of_week' => $day->value,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    private function log(
        Schedule $schedule,
        string $date,
        DailyLogStatus $status,
        ?CurriculumResource $resource = null,
    ): DailyLog {
        return DailyLog::factory()->for($schedule)->create([
            'date' => $date,
            'status' => $status->value,
            'curriculum_resource_id' => $resource?->id,
        ]);
    }

    private function assessment(Schedule $schedule, string $date): Assessment
    {
        return Assessment::factory()->for($schedule)->create([
            'date' => $date,
            'title' => 'Assessment '.$date.' '.$schedule->id,
        ]);
    }

    private function observation(
        Schedule $schedule,
        Student $student,
        User $recorder,
        string $category,
        string $date,
        bool $followedUp,
    ): BehaviorObservation {
        $observation = BehaviorObservation::factory()->create([
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'category' => $category,
            'notes' => 'Observation',
            'date' => $date,
            'is_followed_up' => $followedUp,
        ]);

        $observation->forceFill(['recorded_by_id' => $recorder->id])->save();

        return $observation;
    }

    private function remediation(
        Schedule $schedule,
        Student $student,
        User $recorder,
        string $status,
        string $date,
    ): PedagogicalRemediation {
        $remediation = PedagogicalRemediation::factory()->create([
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'reason' => 'Remediation',
            'activities' => 'Activities',
            'session_date' => $date,
            'attended' => true,
            'status' => $status,
        ]);

        $remediation->forceFill(['recorded_by_id' => $recorder->id])->save();

        return $remediation;
    }

    private function expectHttpException(int $status, callable $callback): void
    {
        try {
            $callback();
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());

            return;
        }

        $this->fail('Expected an HTTP '.$status.' exception.');
    }
}
