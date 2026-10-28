<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Models\ActiveLearningStrategy;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Lab;
use App\Models\LabSeatingPlan;
use App\Models\LabStation;
use App\Models\Level;
use App\Models\PedagogicalMeeting;
use App\Models\PedagogicalMemo;
use App\Models\PedagogicalRemediation;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentMotivationService;
use Carbon\Carbon;
use Database\Seeders\MiddleSchoolDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MiddleSchoolSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-26 10:30:00', 'Africa/Algiers'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function seedDataset(): void
    {
        $this->seed(MiddleSchoolDatabaseSeeder::class);
    }

    /**
     * @param  Collection<int, Schedule>  $schedules
     */
    private function hasOverlappingLessons(Collection $schedules): bool
    {
        $ordered = $schedules
            ->sortBy(fn (Schedule $schedule): string => $schedule->start_time->format('H:i'))
            ->values();

        for ($index = 1; $index < $ordered->count(); $index++) {
            if (
                $ordered[$index]->start_time->format('H:i')
                < $ordered[$index - 1]->end_time->format('H:i')
            ) {
                return true;
            }
        }

        return false;
    }

    public function test_it_seeds_the_official_subjects_with_distinct_hex_colours(): void
    {
        $this->seedDataset();

        $this->assertSame(13, Subject::query()->count());

        foreach (MiddleSchoolDatabaseSeeder::SUBJECTS as $subject) {
            $this->assertDatabaseHas('subjects', $subject);
        }

        $colours = Subject::query()->pluck('color');

        $this->assertSame(13, $colours->unique()->count());

        $this->assertTrue(
            $colours->every(fn (?string $colour): bool => (bool) preg_match('/^#[0-9a-f]{6}$/i', (string) $colour)),
        );
    }

    public function test_it_seeds_the_default_active_learning_strategies(): void
    {
        $this->seedDataset();

        $this->assertSame(8, ActiveLearningStrategy::query()->count());
        $this->assertDatabaseHas('active_learning_strategies', [
            'slug' => 'think-pair-share',
            'is_active' => true,
        ]);
    }

    public function test_it_seeds_one_phase_four_levels_twenty_classrooms_and_forty_groups(): void
    {
        $this->seedDataset();

        $this->assertSame(1, EducationPhase::query()->count());

        $phase = EducationPhase::query()->where('code', 'CEM')->sole();

        $this->assertSame('التعليم المتوسط', $phase->name);
        $this->assertSame('م', $phase->phase_abbreviation);

        $levels = Level::query()->where('education_phase_id', $phase->id)->get();

        $this->assertSame(4, $levels->count());
        $this->assertSame([1, 2, 3, 4], $levels->pluck('grade_number')->sort()->values()->all());
        $this->assertSame(
            array_values(MiddleSchoolDatabaseSeeder::LEVELS),
            $levels->sortBy('grade_number')->pluck('name')->values()->all(),
        );

        $classrooms = Classroom::query()->get();

        $this->assertSame(20, $classrooms->count());
        $this->assertSame(
            20,
            $classrooms->where('academic_year', MiddleSchoolDatabaseSeeder::ACADEMIC_YEAR)->count(),
        );

        foreach ($levels as $level) {
            $this->assertSame(5, $classrooms->where('level_id', $level->id)->count());
        }

        // Names come from the Classroom model hook, not from the seeder.
        $this->assertContains('1 م 1', $classrooms->pluck('name')->all());
        $this->assertContains('4 م 5', $classrooms->pluck('name')->all());

        $groups = Group::query()->get();

        $this->assertSame(40, $groups->count());
        $this->assertSame(40, $groups->whereIn('name', MiddleSchoolDatabaseSeeder::GROUPS_PER_CLASSROOM)->count());

        foreach ($classrooms as $classroom) {
            $this->assertSame(2, $groups->where('classroom_id', $classroom->id)->count());
        }
    }

    public function test_it_puts_fifteen_students_in_every_group(): void
    {
        $this->seedDataset();

        $this->assertSame(600, Student::query()->count());

        $groups = Group::query()->withCount('students')->get();

        $this->assertSame(40, $groups->count());
        $this->assertTrue($groups->every(fn (Group $group): bool => $group->students_count === 15));
    }

    public function test_student_identities_are_unique_arabic_and_age_appropriate(): void
    {
        $this->seedDataset();

        $this->assertSame(600, Student::query()->distinct()->count('registration_number'));

        $students = Student::query()->get();

        $malformed = $students->filter(
            fn (Student $student): bool => ! preg_match('/^2026-\d{4}$/', (string) $student->registration_number),
        )->count();

        $this->assertSame(0, $malformed);

        $nonArabic = $students->reject(
            fn (Student $student): bool => preg_match('/\p{Arabic}/u', $student->first_name) === 1
                && preg_match('/\p{Arabic}/u', $student->last_name) === 1,
        )->count();

        $this->assertSame(0, $nonArabic);

        $agesForGrade = fn (int $gradeNumber): array => Student::query()
            ->whereHas('group.classroom.level', fn ($query) => $query->where('grade_number', $gradeNumber))
            ->get()
            ->map(fn (Student $student): int => $student->date_of_birth->age)
            ->pipe(fn ($ages): array => [$ages->min(), $ages->max()]);

        $this->assertSame([11, 12], $agesForGrade(1));
        $this->assertSame([12, 13], $agesForGrade(2));
        $this->assertSame([13, 14], $agesForGrade(3));
        $this->assertSame([14, 15], $agesForGrade(4));
    }

    public function test_it_seeds_thirty_teachers_with_arabic_names_and_subject_specializations(): void
    {
        $this->seedDataset();

        $teachers = User::query()->where('role', Role::Teacher->value)->get();
        $subjectIds = Subject::query()->pluck('id')->all();

        $this->assertCount(30, $teachers);
        $this->assertSame(30, $teachers->pluck('email')->unique()->count());
        $this->assertSame(13, $teachers->pluck('primary_subject_id')->unique()->count());
        $this->assertSame(6, $teachers->whereNotNull('secondary_subject_id')->count());

        foreach ($teachers as $teacher) {
            $this->assertContains($teacher->primary_subject_id, $subjectIds);
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $teacher->name);

            if ($teacher->secondary_subject_id !== null) {
                $this->assertContains($teacher->secondary_subject_id, $subjectIds);
                $this->assertNotSame($teacher->primary_subject_id, $teacher->secondary_subject_id);
            }
        }
    }

    public function test_it_seeds_the_default_admin_account(): void
    {
        $this->seedDataset();

        $admin = User::query()->where('email', MiddleSchoolDatabaseSeeder::ADMIN_EMAIL)->sole();

        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check(MiddleSchoolDatabaseSeeder::ADMIN_PASSWORD, $admin->password));
    }

    public function test_it_seeds_the_demo_teacher_with_math_physics_and_twenty_two_hours(): void
    {
        $this->seedDataset();

        $math = Subject::query()->where('name', 'الرياضيات')->sole();
        $physics = Subject::query()->where('name', 'العلوم الفيزيائية والتكنولوجيا')->sole();

        $teacher = User::query()
            ->where('email', MiddleSchoolDatabaseSeeder::DEMO_TEACHER_EMAIL)
            ->sole();

        $this->assertSame(Role::Teacher, $teacher->role);
        $this->assertSame($math->id, $teacher->primary_subject_id);
        $this->assertSame($physics->id, $teacher->secondary_subject_id);
        $this->assertSame(MiddleSchoolDatabaseSeeder::TEACHER_WEEKLY_HOURS, $teacher->weekly_hours_limit);
        $this->assertSame(20, $teacher->schedules()->count());
        $this->assertTrue(Hash::check(MiddleSchoolDatabaseSeeder::ADMIN_PASSWORD, $teacher->password));
    }

    public function test_it_seeds_the_school_identity_and_an_annual_holiday_calendar(): void
    {
        $this->seedDataset();

        $settings = SchoolSetting::current();

        $this->assertSame(MiddleSchoolDatabaseSeeder::SCHOOL_NAME, $settings->school_name);
        $this->assertSame(MiddleSchoolDatabaseSeeder::DIRECTORATE_NAME, $settings->directorate_name);
        $this->assertSame(MiddleSchoolDatabaseSeeder::MUNICIPALITY_NAME, $settings->municipality_name);
        $this->assertSame(MiddleSchoolDatabaseSeeder::ACADEMIC_YEAR, $settings->academic_year);
        $this->assertSame(MiddleSchoolDatabaseSeeder::PRINCIPAL_NAME, $settings->principal_name);
        $this->assertSame(MiddleSchoolDatabaseSeeder::COUNSELOR_NAME, $settings->counselor_name);
        $this->assertSame(MiddleSchoolDatabaseSeeder::SCHOOL_CODE, $settings->school_code);

        $holidays = SchoolHoliday::query()->get();

        $this->assertGreaterThanOrEqual(8, $holidays->count());
        $this->assertTrue($holidays->every(
            fn (SchoolHoliday $holiday): bool => $holiday->start_date->year >= 2026
                && $holiday->end_date->year <= 2027,
        ));

        $windowStart = now()->startOfWeek(Carbon::SUNDAY)->subWeek()->startOfDay();
        $windowEnd = now()->startOfWeek(Carbon::SUNDAY)->addDays(4)->endOfDay();

        $this->assertSame(0, $holidays->filter(
            fn (SchoolHoliday $holiday): bool => $holiday->start_date->lte($windowEnd)
                && $holiday->end_date->gte($windowStart),
        )->count());

        foreach (DailyLog::query()->pluck('date') as $date) {
            $this->assertFalse(SchoolHoliday::isHoliday($date));
        }
    }

    public function test_it_generates_conflict_free_weekly_schedules_for_teachers_and_classrooms(): void
    {
        $this->seedDataset();

        $schedules = Schedule::query()->with(['user', 'group'])->get();

        $this->assertSame(
            MiddleSchoolDatabaseSeeder::TEACHER_COUNT * 20,
            $schedules->count(),
        );
        $this->assertSame(
            Classroom::query()->count() * 30,
            $schedules->count(),
        );

        $validSlots = collect(MiddleSchoolDatabaseSeeder::LESSON_SLOTS)
            ->map(fn (array $slot): string => $slot[0].'-'.$slot[1])
            ->all();

        foreach ($schedules as $schedule) {
            $this->assertContains($schedule->day_of_week->value, MiddleSchoolDatabaseSeeder::SCHOOL_DAYS);
            $this->assertTrue($schedule->user->isTeacher());
            $this->assertNotNull($schedule->classroom_id);
            $this->assertSame($schedule->user->primary_subject_id, $schedule->subject_id);
            $this->assertContains(
                $schedule->start_time->format('H:i').'-'.$schedule->end_time->format('H:i'),
                $validSlots,
            );

            if ($schedule->group_id !== null) {
                $this->assertNotNull($schedule->group);
                $this->assertSame($schedule->classroom_id, $schedule->group->classroom_id);
            }
        }

        $this->assertGreaterThan(0, $schedules->whereNotNull('group_id')->count());

        foreach (
            $schedules->groupBy(
                fn (Schedule $schedule): string => $schedule->user_id.'|'.$schedule->day_of_week->value,
            ) as $teacherDay
        ) {
            $this->assertFalse($this->hasOverlappingLessons($teacherDay));
        }

        foreach (
            $schedules->groupBy(
                fn (Schedule $schedule): string => $schedule->classroom_id.'|'.$schedule->day_of_week->value,
            ) as $classroomDay
        ) {
            $this->assertFalse($this->hasOverlappingLessons($classroomDay));
        }

        foreach (User::query()->where('role', Role::Teacher->value)->get() as $teacher) {
            $this->assertSame(20, $teacher->schedules()->count());
        }

        $labSubjectIds = Subject::query()
            ->whereIn('name', MiddleSchoolDatabaseSeeder::LAB_SUBJECTS)
            ->pluck('id')
            ->all();

        foreach (Classroom::query()->with('groups')->get() as $classroom) {
            $classroomSchedules = $schedules->where('classroom_id', $classroom->id);

            $this->assertSame(30, $classroomSchedules->count());
            $this->assertSame(13, $classroomSchedules->pluck('subject_id')->unique()->count());

            $labSchedules = $classroomSchedules->whereIn('subject_id', $labSubjectIds);

            $this->assertSame(6, $labSchedules->count());

            foreach ($labSubjectIds as $labSubjectId) {
                $sessions = $labSchedules->where('subject_id', $labSubjectId);

                $this->assertSame(2, $sessions->count());
                $this->assertEqualsCanonicalizing(
                    $classroom->groups->pluck('id')->all(),
                    $sessions->pluck('group_id')->all(),
                );
            }
        }
    }

    public function test_it_seeds_two_weeks_of_daily_logs_with_structured_lesson_content(): void
    {
        $this->seedDataset();

        $weekStart = now()->startOfWeek(Carbon::SUNDAY);
        $windowStart = $weekStart->copy()->subWeek()->startOfDay();
        $windowEnd = $weekStart->copy()->addDays(4)->endOfDay();
        $logs = DailyLog::query()->with('schedule:id,end_time')->get();

        // Every weekly slot occurs exactly twice: previous Sunday → current Thursday.
        $this->assertSame(Schedule::query()->count() * 2, $logs->count());
        $this->assertSame(10, $logs->pluck('date')->map->toDateString()->unique()->count());
        $this->assertTrue(
            $logs->groupBy('schedule_id')->every(
                fn (Collection $scheduleLogs): bool => $scheduleLogs->count() === 2,
            ),
        );
        $this->assertSame(
            $logs->count(),
            $logs->unique(
                fn (DailyLog $log): string => $log->schedule_id.'|'.$log->date->toDateString(),
            )->count(),
        );

        foreach ($logs as $log) {
            $this->assertContains($log->status, [DailyLogStatus::Completed, DailyLogStatus::Planned]);
            $this->assertTrue($log->date->between($windowStart, $windowEnd));
            $this->assertStringStartsWith('درس:', $log->title);

            $block = $log->content_data[0] ?? [];

            $this->assertSame('lesson_structure', $block['type'] ?? null);
            $this->assertNotEmpty($block['data']['competence'] ?? null);
            $this->assertSame(
                ['وضعية الانطلاق', 'بناء التعلمات', 'الترسيخ والممارسة', 'التقويم والاستثمار'],
                array_column($block['data']['lesson_phases'] ?? [], 'phase_name'),
            );

            $expectedStatus = $log->date->lt(today())
                || (
                    $log->date->isToday()
                    && $log->schedule->end_time->format('H:i') <= now()->format('H:i')
                )
                    ? DailyLogStatus::Completed
                    : DailyLogStatus::Planned;

            $this->assertSame($expectedStatus, $log->status);
            $this->assertSame(
                $log->status === DailyLogStatus::Completed,
                filled($log->actual_content),
            );
            $this->assertNotEmpty($log->homework);
        }

        $this->assertSame(760, $logs->where('status', DailyLogStatus::Completed)->count());
        $this->assertSame(440, $logs->where('status', DailyLogStatus::Planned)->count());
    }

    public function test_it_seeds_a_curriculum_for_every_subject_and_level_and_maps_each_daily_log(): void
    {
        $this->seedDataset();

        $subjectCount = Subject::query()->count();
        $levelCount = Level::query()->count();

        $this->assertSame($subjectCount * $levelCount * 2, CurriculumDomain::query()->count());
        $this->assertSame($subjectCount * $levelCount * 4, CurriculumSequence::query()->count());
        $this->assertSame($subjectCount * $levelCount * 8, CurriculumResource::query()->count());
        $this->assertSame(0, DailyLog::query()->whereNull('curriculum_resource_id')->count());

        $mismatched = DailyLog::query()
            ->with(['schedule.classroom', 'curriculumResource.sequence.domain'])
            ->get()
            ->filter(function (DailyLog $log): bool {
                $schedule = $log->schedule;
                $domain = $log->curriculumResource?->sequence?->domain;

                return ! $schedule
                    || ! $domain
                    || $domain->subject_id !== $schedule->subject_id
                    || $domain->level_id !== $schedule->classroom?->level_id;
            })
            ->count();

        $this->assertSame(0, $mismatched);
    }

    public function test_it_seeds_assessments_and_grades_for_the_whole_schedule_roster(): void
    {
        $this->seedDataset();

        $schedules = Schedule::query()->get();
        $assessments = Assessment::query()->with('schedule')->get();
        $classroomCount = Classroom::query()->count();

        // Three core subjects × two sequential assessments × every classroom.
        $this->assertSame($classroomCount * 3 * 2, $assessments->count());
        $this->assertSame($assessments->count() * 30, Grade::query()->count());
        $this->assertLessThan($schedules->count(), $assessments->count());

        $studentsByGroup = Student::query()
            ->get(['id', 'group_id'])
            ->groupBy('group_id');
        $classroomByGroup = Group::query()->pluck('classroom_id', 'id');
        $gradeCounts = Grade::query()
            ->selectRaw('assessment_id, COUNT(*) as aggregate')
            ->groupBy('assessment_id')
            ->pluck('aggregate', 'assessment_id');

        $windowStart = now()->startOfWeek(Carbon::SUNDAY)->subWeek()->startOfDay();
        $windowEnd = now()->startOfWeek(Carbon::SUNDAY)->addDays(4)->endOfDay();

        foreach ($assessments as $assessment) {
            $this->assertContains($assessment->type, [AssessmentType::Quiz, AssessmentType::Midterm]);
            $this->assertEquals(20, (float) $assessment->max_score);
            $this->assertTrue($assessment->date->between($windowStart, $windowEnd));

            $schedule = $assessment->schedule;
            $expectedGrades = $schedule->group_id !== null
                ? $studentsByGroup->get($schedule->group_id, collect())->count()
                : $studentsByGroup
                    ->filter(
                        fn (Collection $students, int|string $groupId): bool => $classroomByGroup->get($groupId) === $schedule->classroom_id,
                    )
                    ->sum(fn (Collection $students): int => $students->count());

            $this->assertGreaterThan(0, $expectedGrades);
            $this->assertSame($expectedGrades, (int) $gradeCounts->get($assessment->id, 0));
        }

        $grades = Grade::query()->get();

        $this->assertGreaterThan(0, $grades->count());
        $this->assertSame(
            $grades->count(),
            $grades->unique(
                fn (Grade $grade): string => $grade->assessment_id.'|'.$grade->student_id,
            )->count(),
        );
        $this->assertTrue($grades->every(
            fn (Grade $grade): bool => $grade->score !== null
                && (float) $grade->score >= 0
                && (float) $grade->score <= 20,
        ));
    }

    public function test_it_seeds_sequential_assessments_with_balanced_learner_profiles(): void
    {
        $this->seedDataset();

        $math = Subject::query()->where('name', 'الرياضيات')->sole();
        $assessments = Assessment::query()
            ->with('schedule')
            ->whereHas('schedule', fn ($query) => $query->where('subject_id', $math->id))
            ->get();

        $this->assertCount(Classroom::query()->count() * 2, $assessments);

        $gradesByAssessment = Grade::query()
            ->whereIn('assessment_id', $assessments->pluck('id'))
            ->get()
            ->groupBy('assessment_id');

        $profileCounts = [
            'excellence' => 0,
            'comeback' => 0,
            'steady' => 0,
            'struggling' => 0,
        ];

        foreach (Classroom::query()->get() as $classroom) {
            $pair = $assessments
                ->filter(fn (Assessment $assessment): bool => $assessment->schedule->classroom_id === $classroom->id)
                ->sortBy(fn (Assessment $assessment): string => $assessment->date->toDateString())
                ->values();

            $this->assertCount(2, $pair);
            $this->assertNotSame($pair[0]->date->toDateString(), $pair[1]->date->toDateString());

            $firstGrades = $gradesByAssessment->get($pair[0]->id, collect())->keyBy('student_id');
            $secondGrades = $gradesByAssessment->get($pair[1]->id, collect())->keyBy('student_id');

            $roster = Student::query()
                ->whereIn('group_id', Group::query()->where('classroom_id', $classroom->id)->pluck('id'))
                ->orderBy('id')
                ->pluck('id');

            $this->assertCount(30, $roster);

            foreach ($roster as $studentId) {
                $this->assertArrayHasKey($studentId, $firstGrades->all());
                $this->assertArrayHasKey($studentId, $secondGrades->all());

                $firstScore = (float) $firstGrades[$studentId]->score;
                $secondScore = (float) $secondGrades[$studentId]->score;

                $profile = match (true) {
                    $firstScore >= 16.0 && $firstScore <= 19.5 => 'excellence',
                    $firstScore === 9.5 && $secondScore === 13.5 => 'comeback',
                    $firstScore >= 10.5 && $firstScore <= 15.5 => 'steady',
                    $firstScore >= 5.5 && $firstScore <= 9.0 => 'struggling',
                    default => null,
                };

                $this->assertNotNull($profile);
                $profileCounts[$profile]++;
            }
        }

        $this->assertSame([
            'excellence' => 120,
            'comeback' => 150,
            'steady' => 240,
            'struggling' => 90,
        ], $profileCounts);
    }

    public function test_it_seeds_attendance_with_realistic_distribution_streaks_and_repeat_absentees(): void
    {
        $this->seedDataset();

        $counts = Attendance::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (int|string $count): int => (int) $count);

        $total = (int) $counts->sum();

        $this->assertGreaterThan(20_000, $total);
        $this->assertGreaterThan(0.85, $counts['present'] / $total);
        $this->assertLessThan(0.93, $counts['present'] / $total);
        $this->assertGreaterThan(0.04, $counts['absent'] / $total);
        $this->assertLessThan(0.08, $counts['absent'] / $total);
        $this->assertGreaterThan(0.015, $counts['late'] / $total);
        $this->assertLessThan(0.04, $counts['late'] / $total);
        $this->assertGreaterThan(0.005, $counts['excused'] / $total);
        $this->assertLessThan(0.03, $counts['excused'] / $total);

        $this->assertSame(0, Attendance::query()
            ->where('status', '!=', AttendanceStatus::Present->value)
            ->whereNull('remarks')
            ->count());

        $repeatAbsentees = DB::table('attendances')
            ->join('daily_logs', 'daily_logs.id', '=', 'attendances.daily_log_id')
            ->where('attendances.status', AttendanceStatus::Absent->value)
            ->selectRaw('attendances.student_id, COUNT(DISTINCT daily_logs.date) as absence_days')
            ->groupBy('attendances.student_id')
            ->having('absence_days', '>=', 3)
            ->get()
            ->count();

        $this->assertGreaterThanOrEqual(40, $repeatAbsentees);

        $classroom = Classroom::query()->with('groups')->orderBy('id')->firstOrFail();
        $topStudent = Student::query()
            ->whereIn('group_id', $classroom->groups->pluck('id'))
            ->orderBy('id')
            ->firstOrFail();

        $badges = app(StudentMotivationService::class)->badgesForStudent($topStudent, $classroom);

        $this->assertArrayHasKey('attendance_streak', $badges);
    }

    public function test_it_seeds_three_laboratories_with_stations_and_two_seating_plans(): void
    {
        $this->seedDataset();

        $this->assertSame(3, Lab::query()->count());
        $this->assertSame(60, LabStation::query()->count());
        $this->assertSame(3, Lab::query()->where('capacity', 20)->count());
        $this->assertSame(3, Lab::query()->withCount('stations')->get()->where('stations_count', 20)->count());
        $this->assertSame(54, LabStation::query()->where('status', 'working')->count());
        $this->assertSame(3, LabStation::query()->where('status', 'under_maintenance')->count());
        $this->assertSame(3, LabStation::query()->where('status', 'broken')->count());

        $plans = LabSeatingPlan::query()->withCount('stations')->orderBy('id')->get();

        $this->assertSame(2, $plans->count());
        $this->assertSame(
            ['1 م 1 — الفوج أ', '1 م 1 — الفوج ب'],
            $plans->pluck('name')->all(),
        );

        foreach ($plans as $plan) {
            $this->assertSame(18, $plan->stations_count);
            $this->assertSame(15, $plan->stations()->whereNotNull('student_id')->count());
        }
    }

    public function test_it_seeds_memos_observations_remediations_and_meetings(): void
    {
        $this->seedDataset();

        $this->assertSame(61, PedagogicalMemo::query()->count());
        $this->assertSame(30, PedagogicalMemo::query()->where('is_template', false)->where('is_archived', false)->count());
        $this->assertSame(30, PedagogicalMemo::query()->where('is_template', true)->count());
        $this->assertSame(1, PedagogicalMemo::query()->where('is_archived', true)->count());

        $this->assertSame(120, BehaviorObservation::query()->count());
        $this->assertSame(60, BehaviorObservation::query()->where('category', 'positive')->count());
        $this->assertSame(40, BehaviorObservation::query()->whereIn('category', ['negative', 'disciplinary'])->count());
        $this->assertSame(20, BehaviorObservation::query()->where('category', 'observation')->count());
        $this->assertSame(0, BehaviorObservation::query()->whereNull('recorded_by_id')->count());

        $this->assertSame(120, PedagogicalRemediation::query()->count());
        $this->assertSame(0, PedagogicalRemediation::query()->whereNull('recorded_by_id')->count());
        $this->assertGreaterThan(0, PedagogicalRemediation::query()->where('attended', true)->count());

        $this->assertSame(62, PedagogicalMeeting::query()->count());
        $this->assertSame(30, PedagogicalMeeting::query()->where('meeting_type', 'inspector_seminar')->count());
        $this->assertSame(30, PedagogicalMeeting::query()->where('meeting_type', 'coordination_meeting')->count());
    }

    public function test_the_seeder_wipes_previous_school_and_user_data(): void
    {
        Student::factory()->create(['registration_number' => '9999-9999']);
        User::factory()->create(['email' => 'stray@sajel.test']);

        $this->seedDataset();

        $this->assertDatabaseMissing('students', ['registration_number' => '9999-9999']);
        $this->assertDatabaseMissing('users', ['email' => 'stray@sajel.test']);

        $this->assertSame(600, Student::query()->count());
        $this->assertSame(31, User::query()->count());
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seedDataset();

        $firstScheduleCount = Schedule::query()->count();

        $this->seedDataset();

        $this->assertSame(13, Subject::query()->count());
        $this->assertSame(1, EducationPhase::query()->count());
        $this->assertSame(4, Level::query()->count());
        $this->assertSame(20, Classroom::query()->count());
        $this->assertSame(40, Group::query()->count());
        $this->assertSame(600, Student::query()->count());
        $this->assertSame(30, User::query()->where('role', Role::Teacher->value)->count());
        $this->assertSame(1, User::query()->where('role', Role::Admin->value)->count());
        $this->assertSame(31, User::query()->count());

        // The timetable is regenerated with fresh random slots; it still stays
        // inside the workload target instead of accumulating duplicate rows.
        $this->assertGreaterThanOrEqual(
            MiddleSchoolDatabaseSeeder::TEACHER_COUNT * MiddleSchoolDatabaseSeeder::MIN_TEACHER_LESSONS,
            $firstScheduleCount,
        );
        $this->assertLessThanOrEqual(
            MiddleSchoolDatabaseSeeder::TEACHER_COUNT * MiddleSchoolDatabaseSeeder::MAX_TEACHER_LESSONS,
            $firstScheduleCount,
        );
        $this->assertGreaterThanOrEqual(
            MiddleSchoolDatabaseSeeder::TEACHER_COUNT * MiddleSchoolDatabaseSeeder::MIN_TEACHER_LESSONS,
            Schedule::query()->count(),
        );
        $this->assertLessThanOrEqual(
            MiddleSchoolDatabaseSeeder::TEACHER_COUNT * MiddleSchoolDatabaseSeeder::MAX_TEACHER_LESSONS,
            Schedule::query()->count(),
        );

        // Every classroom still owns exactly its two groups.
        $this->assertSame(
            Classroom::query()->count(),
            Group::query()->selectRaw('classroom_id')->distinct()->count('classroom_id'),
        );
    }

    public function test_the_seeder_stays_within_a_bulk_query_budget(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->seedDataset();

        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        // The two-week dataset — including timetables, 22k+ attendance rows,
        // assessments/grades, curriculum, labs, and follow-up records — should
        // still use a bounded set of bulk writes instead of one query per row.
        $this->assertLessThan(400, $queries, "The seeder ran {$queries} queries.");
    }

    public function test_the_student_factory_generates_arabic_test_identities(): void
    {
        $student = Student::factory()->make();

        $this->assertMatchesRegularExpression('/^2026-\d{4}$/', $student->registration_number);
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $student->first_name);
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $student->last_name);
        $this->assertTrue($student->date_of_birth->between(
            now()->subYears(15)->subDay(),
            now()->subYears(11)->addDay(),
        ));
    }
}
