<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\MiddleSchoolDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MiddleSchoolSeederTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertSame(20, $classrooms->where('academic_year', '2025-2026')->count());

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

    public function test_it_generates_conflict_free_weekly_schedules_for_teachers_and_classrooms(): void
    {
        $this->seedDataset();

        $schedules = Schedule::query()->with(['user', 'group'])->get();

        $this->assertGreaterThanOrEqual(
            MiddleSchoolDatabaseSeeder::TEACHER_COUNT * MiddleSchoolDatabaseSeeder::MIN_TEACHER_LESSONS,
            $schedules->count(),
        );
        $this->assertLessThanOrEqual(
            MiddleSchoolDatabaseSeeder::TEACHER_COUNT * MiddleSchoolDatabaseSeeder::MAX_TEACHER_LESSONS,
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
            $lessons = $teacher->schedules()->count();

            $this->assertGreaterThanOrEqual(MiddleSchoolDatabaseSeeder::MIN_TEACHER_LESSONS, $lessons);
            $this->assertLessThanOrEqual(MiddleSchoolDatabaseSeeder::MAX_TEACHER_LESSONS, $lessons);
        }
    }

    public function test_it_seeds_two_weeks_of_daily_logs_with_structured_lesson_content(): void
    {
        $this->seedDataset();

        $logs = DailyLog::query()->get();
        $windowStart = now()
            ->subDays(MiddleSchoolDatabaseSeeder::DAILY_LOG_HISTORY_DAYS - 1)
            ->startOfDay();
        $windowEnd = now()->endOfDay();

        // Every weekly slot occurs exactly twice in a 14-day window.
        $this->assertSame(Schedule::query()->count() * 2, $logs->count());
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
                ['التمهيد', 'الاكتشاف', 'الترسيخ', 'التقويم'],
                array_column($block['data']['lesson_phases'] ?? [], 'phase_name'),
            );
            $this->assertSame(
                $log->status === DailyLogStatus::Completed,
                filled($log->actual_content),
            );
        }
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

        $this->assertGreaterThan(0, $assessments->count());
        $this->assertLessThan($schedules->count(), $assessments->count());

        $studentsByGroup = Student::query()
            ->get(['id', 'group_id'])
            ->groupBy('group_id');
        $classroomByGroup = Group::query()->pluck('classroom_id', 'id');
        $gradeCounts = Grade::query()
            ->selectRaw('assessment_id, COUNT(*) as aggregate')
            ->groupBy('assessment_id')
            ->pluck('aggregate', 'assessment_id');

        foreach ($assessments as $assessment) {
            $this->assertContains($assessment->type, [AssessmentType::Quiz, AssessmentType::Midterm]);
            $this->assertEquals(20, (float) $assessment->max_score);
            $this->assertTrue($assessment->date->between(
                now()->subDays(MiddleSchoolDatabaseSeeder::DAILY_LOG_HISTORY_DAYS - 1)->startOfDay(),
                now()->endOfDay(),
            ));

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

        // The ~6,000-record dataset, including the curriculum plan, should
        // still use a bounded set of bulk writes instead of one query per row.
        $this->assertLessThan(215, $queries, "The seeder ran {$queries} queries.");
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
