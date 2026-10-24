<?php

namespace Database\Seeders;

use App\Enums\AssessmentType;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Wipes the school data and rebuilds a complete, realistic Algerian middle
 * school (التعليم المتوسط):
 *
 *   13 official subjects · 1 phase · 4 levels · 20 classrooms · 40 groups
 *   600 students · 30 teachers · a conflict-free Sunday–Thursday timetable,
 *   two weeks of daily logs, and a seeded quiz/midterm gradebook.
 *
 * The seeder is deliberately destructive: run it only on development or test
 * databases. It is the root seeder behind `php artisan migrate:fresh --seed`.
 */
class MiddleSchoolDatabaseSeeder extends Seeder
{
    /**
     * Academic year stamped on every generated classroom.
     */
    public const ACADEMIC_YEAR = '2025-2026';

    /**
     * The four middle-school years, keyed by grade number.
     *
     * @var array<int, string>
     */
    public const LEVELS = [
        1 => 'السنة الأولى',
        2 => 'السنة الثانية',
        3 => 'السنة الثالثة',
        4 => 'السنة الرابعة',
    ];

    public const CLASSROOMS_PER_LEVEL = 5;

    /**
     * @var list<string>
     */
    public const GROUPS_PER_CLASSROOM = ['الفوج أ', 'الفوج ب'];

    public const STUDENTS_PER_GROUP = 15;

    /**
     * Number of calendar days covered by historical class-register entries.
     */
    public const DAILY_LOG_HISTORY_DAYS = 14;

    /**
     * Share of timetable slots that receive an assessment.
     */
    public const ASSESSMENT_RATIO = 0.25;

    public const TEACHER_COUNT = 30;

    public const TEACHER_WEEKLY_HOURS = 22;

    /**
     * Share of teachers who also teach a secondary subject (6 of 30).
     */
    public const SECONDARY_SUBJECT_RATIO = 0.2;

    public const MIN_TEACHER_LESSONS = 18;

    public const MAX_TEACHER_LESSONS = 22;

    /**
     * Percentage of lessons attached to one group rather than the whole class.
     */
    private const GROUP_LESSON_CHANCE = 20;

    public const ADMIN_EMAIL = 'admin@sajel.test';

    public const ADMIN_PASSWORD = 'password';

    /**
     * The Algerian school week and the standard one-hour lesson grid.
     *
     * @var list<string>
     */
    public const SCHOOL_DAYS = [
        DayOfWeek::Sunday->value,
        DayOfWeek::Monday->value,
        DayOfWeek::Tuesday->value,
        DayOfWeek::Wednesday->value,
        DayOfWeek::Thursday->value,
    ];

    /**
     * @var list<array{0: string, 1: string}>
     */
    public const LESSON_SLOTS = [
        ['08:00', '09:00'],
        ['09:00', '10:00'],
        ['10:00', '11:00'],
        ['11:00', '12:00'],
        ['14:00', '15:00'],
        ['15:00', '16:00'],
        ['16:00', '17:00'],
    ];

    /**
     * Official middle-school subjects, each with a distinct hex colour that
     * is compatible with the subject ColorPicker and the schedule views.
     *
     * @var list<array{name: string, color: string}>
     */
    public const SUBJECTS = [
        ['name' => 'اللغة العربية', 'color' => '#dc2626'],
        ['name' => 'الرياضيات', 'color' => '#2563eb'],
        ['name' => 'العلوم الفيزيائية والتكنولوجيا', 'color' => '#7c3aed'],
        ['name' => 'علوم الطبيعة والحياة', 'color' => '#16a34a'],
        ['name' => 'التاريخ والجغرافيا', 'color' => '#d97706'],
        ['name' => 'التربية الإسلامية', 'color' => '#0d9488'],
        ['name' => 'التربية المدنية', 'color' => '#0891b2'],
        ['name' => 'اللغة الفرنسية', 'color' => '#4f46e5'],
        ['name' => 'اللغة الإنجليزية', 'color' => '#c026d3'],
        ['name' => 'الإعلام الآلي', 'color' => '#0284c7'],
        ['name' => 'التربية البدنية والرياضية', 'color' => '#ea580c'],
        ['name' => 'اللغة الأمازيغية', 'color' => '#65a30d'],
        ['name' => 'التربية الفنية', 'color' => '#db2777'],
    ];

    /**
     * A small pool of realistic lesson topics for each official subject.
     *
     * @var array<string, list<string>>
     */
    private const LESSON_TOPICS = [
        'اللغة العربية' => ['فهم النص السردي', 'قواعد الإعراب', 'التعبير الكتابي'],
        'الرياضيات' => ['الأعداد النسبية', 'حل المعادلات', 'النسب المثلثية'],
        'العلوم الفيزيائية والتكنولوجيا' => ['الدارة الكهربائية', 'الحركة والسكون', 'المادة وتحولاتها'],
        'علوم الطبيعة والحياة' => ['التغذية عند الإنسان', 'التنفس والتبادل الغازي', 'التوازن الغذائي'],
        'التاريخ والجغرافيا' => ['الثورة التحريرية', 'المجال الجغرافي', 'المقاومة الشعبية'],
        'التربية الإسلامية' => ['أحكام الصلاة', 'الزكاة وأحكامها', 'الأخلاق الإسلامية'],
        'التربية المدنية' => ['المؤسسات الدستورية', 'الحقوق والواجبات', 'المواطنة المسؤولة'],
        'اللغة الفرنسية' => ['Compréhension de l\'écrit', 'Grammaire et conjugaison', 'Production écrite'],
        'اللغة الإنجليزية' => ['Reading comprehension', 'Grammar in context', 'Guided writing'],
        'الإعلام الآلي' => ['الخوارزميات', 'الجداول الممتدة', 'أمن المعلومات'],
        'التربية البدنية والرياضية' => ['كرة القدم', 'اللياقة البدنية', 'السباحة'],
        'اللغة الأمازيغية' => ['فهم النص الأمازيغي', 'القواعد اللغوية', 'التعبير الشفهي'],
        'التربية الفنية' => ['الرسم والخطوط', 'الموسيقى والإيقاع', 'الأشغال اليدوية'],
    ];

    /**
     * Tables removed before every run, children first so the seeder also works
     * while foreign-key enforcement is active (for example inside tests).
     *
     * @var list<string>
     */
    private const WIPE_TABLES = [
        'grades',
        'attendances',
        'daily_logs',
        'curriculum_resources',
        'curriculum_sequences',
        'curriculum_domains',
        'assessments',
        'schedules',
        'students',
        'groups',
        'classrooms',
        'levels',
        'education_phases',
        'subjects',
        'failed_import_rows',
        'imports',
        'exports',
        'sessions',
        'password_reset_tokens',
        'users',
    ];

    public function run(): void
    {
        // SQLite ignores a PRAGMA change made inside a transaction, so turn the
        // checks off before opening the transaction and restore them afterwards.
        Schema::disableForeignKeyConstraints();

        try {
            $stats = DB::transaction(function (): array {
                $this->wipe();

                $this->call(ActiveLearningStrategySeeder::class);

                $subjects = $this->seedSubjects();
                $structure = $this->seedStructure($this->seedPhase());

                $students = $this->seedStudents($structure['classrooms']);

                $this->seedAdmin();

                $teachers = $this->seedTeachers($subjects);
                $scheduleCount = $this->seedSchedules(
                    $teachers,
                    $structure['levels'],
                    $structure['classrooms'],
                );

                $curriculum = $this->seedCurriculum($subjects, $structure['levels']);
                $classroomLevels = $structure['classrooms']
                    ->mapWithKeys(fn (Classroom $classroom): array => [$classroom->id => $classroom->level_id])
                    ->all();

                // Re-read the inserted slots once so the log and gradebook
                // generators can work entirely in memory, without one query per
                // schedule or student.
                $schedules = Schedule::query()->orderBy('id')->get();
                $subjectsById = $subjects->keyBy('id');

                $dailyLogCount = $this->seedDailyLogs(
                    $schedules,
                    $subjectsById,
                    $curriculum['resources_by_pair'],
                    $classroomLevels,
                );
                $gradebook = $this->seedAssessmentsAndGrades($schedules, $subjectsById);

                return [
                    'subjects' => $subjects->count(),
                    'levels' => $structure['levels']->count(),
                    'classrooms' => $structure['classrooms']->count(),
                    'groups' => $structure['classrooms']->sum(
                        fn (Classroom $classroom): int => $classroom->groups->count(),
                    ),
                    'students' => $students,
                    'teachers' => $teachers->count(),
                    'schedules' => $scheduleCount,
                    'daily_logs' => $dailyLogCount,
                    'curriculum_domains' => $curriculum['domains'],
                    'curriculum_sequences' => $curriculum['sequences'],
                    'curriculum_resources' => $curriculum['resources'],
                    'assessments' => $gradebook['assessments'],
                    'grades' => $gradebook['grades'],
                ];
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command?->info(sprintf(
            'Middle-school dataset ready: %d subjects, %d levels, %d classrooms, %d groups, %d students, %d teachers, %d schedules, %d daily logs, %d curriculum domains, %d sequences, %d resources, %d assessments, %d grades.',
            $stats['subjects'],
            $stats['levels'],
            $stats['classrooms'],
            $stats['groups'],
            $stats['students'],
            $stats['teachers'],
            $stats['schedules'],
            $stats['daily_logs'],
            $stats['curriculum_domains'],
            $stats['curriculum_sequences'],
            $stats['curriculum_resources'],
            $stats['assessments'],
            $stats['grades'],
        ));
    }

    private function wipe(): void
    {
        foreach (self::WIPE_TABLES as $table) {
            DB::table($table)->truncate();
        }
    }

    /**
     * @return EloquentCollection<int, Subject>
     */
    private function seedSubjects(): EloquentCollection
    {
        $timestamp = now();

        $rows = array_map(
            fn (array $subject): array => $subject + [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            self::SUBJECTS,
        );

        Subject::query()->insert($rows);

        return Subject::query()->orderBy('id')->get();
    }

    /**
     * The single middle-school education phase. Its "م" abbreviation feeds
     * the Classroom naming hook (e.g. "1 م 1").
     */
    private function seedPhase(): EducationPhase
    {
        return EducationPhase::create([
            'name' => 'التعليم المتوسط',
            'code' => 'CEM',
            'phase_abbreviation' => 'م',
            'settings' => [
                'period_minutes' => 50,
                'default_status' => DailyLogStatus::Planned->value,
            ],
        ]);
    }

    /**
     * Create levels → classrooms → groups. Classrooms go through the model so
     * the saving hook generates their names; the rest is bulk-inserted.
     *
     * @return array{
     *     levels: EloquentCollection<int, Level>,
     *     classrooms: EloquentCollection<int, Classroom>
     * }
     */
    private function seedStructure(EducationPhase $phase): array
    {
        $timestamp = now();
        $levelRows = [];

        foreach (self::LEVELS as $gradeNumber => $levelName) {
            $levelRows[] = [
                'education_phase_id' => $phase->id,
                'name' => $levelName,
                'grade_number' => $gradeNumber,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        Level::query()->insert($levelRows);

        $levels = Level::query()
            ->where('education_phase_id', $phase->id)
            ->orderBy('grade_number')
            ->get();

        foreach ($levels as $level) {
            for ($classNumber = 1; $classNumber <= self::CLASSROOMS_PER_LEVEL; $classNumber++) {
                // Do not set "name": Classroom::saving generates "{grade} {abbr} {number}".
                Classroom::create([
                    'level_id' => $level->id,
                    'class_number' => $classNumber,
                    'academic_year' => self::ACADEMIC_YEAR,
                ]);
            }
        }

        $classrooms = Classroom::query()
            ->whereIn('level_id', $levels->pluck('id'))
            ->with('level')
            ->orderBy('level_id')
            ->orderBy('class_number')
            ->get();

        $groupRows = [];

        foreach ($classrooms as $classroom) {
            foreach (self::GROUPS_PER_CLASSROOM as $groupName) {
                $groupRows[] = [
                    'classroom_id' => $classroom->id,
                    'name' => $groupName,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        Group::query()->insert($groupRows);

        $classrooms->load('groups');

        return [
            'levels' => $levels,
            'classrooms' => $classrooms,
        ];
    }

    /**
     * Attach 15 factory-built students to every group and write them in six
     * 100-row inserts instead of 600 individual queries.
     *
     * @param  EloquentCollection<int, Classroom>  $classrooms
     */
    private function seedStudents(EloquentCollection $classrooms): int
    {
        $timestamp = now();
        $sequence = 0;
        $rows = [];

        foreach ($classrooms as $classroom) {
            $gradeNumber = (int) ($classroom->level?->grade_number ?? 1);

            foreach ($classroom->groups as $group) {
                for ($index = 0; $index < self::STUDENTS_PER_GROUP; $index++) {
                    $sequence++;

                    $student = Student::factory()->make([
                        'group_id' => $group->id,
                        // "2026-0001" … "2026-0600": unique and predictable.
                        'registration_number' => sprintf('2026-%04d', $sequence),
                        'date_of_birth' => $this->birthDateFor($gradeNumber),
                    ]);

                    $rows[] = $student->getAttributes() + [
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Student::query()->insert($chunk);
        }

        return $sequence;
    }

    private function seedAdmin(): void
    {
        User::factory()->admin()->create([
            'name' => 'مدير المنصة',
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make(self::ADMIN_PASSWORD),
        ]);
    }

    /**
     * Create the teaching staff. Every subject is guaranteed at least one
     * primary teacher; exactly 20% also receive a different secondary subject.
     *
     * @param  EloquentCollection<int, Subject>  $subjects
     * @return EloquentCollection<int, User>
     */
    private function seedTeachers(EloquentCollection $subjects): EloquentCollection
    {
        $primarySubjectIds = $subjects->pluck('id')->all();

        while (count($primarySubjectIds) < self::TEACHER_COUNT) {
            $primarySubjectIds[] = $subjects->random()->id;
        }

        shuffle($primarySubjectIds);

        $secondaryIndexes = range(0, self::TEACHER_COUNT - 1);
        shuffle($secondaryIndexes);
        $secondaryIndexes = array_slice(
            $secondaryIndexes,
            0,
            (int) round(self::TEACHER_COUNT * self::SECONDARY_SUBJECT_RATIO),
        );

        $faker = fake('ar_SA');
        $teachers = new EloquentCollection;

        for ($index = 0; $index < self::TEACHER_COUNT; $index++) {
            $primarySubjectId = $primarySubjectIds[$index];

            $secondarySubjectId = in_array($index, $secondaryIndexes, true)
                ? $subjects->where('id', '!=', $primarySubjectId)->random()->id
                : null;

            $teacher = User::factory()->teacher()->create([
                'name' => trim(sprintf(
                    '%s %s',
                    $faker->boolean() ? $faker->firstNameFemale() : $faker->firstNameMale(),
                    $faker->randomElement(StudentFactory::FAMILY_NAMES),
                )),
                'email' => sprintf('teacher.%02d@sajel.test', $index + 1),
                'primary_subject_id' => $primarySubjectId,
                'secondary_subject_id' => $secondarySubjectId,
                'weekly_hours_limit' => self::TEACHER_WEEKLY_HOURS,
            ]);

            $teachers->push($teacher);
        }

        return $teachers;
    }

    /**
     * Build a conflict-free timetable with 18–22 primary-subject lessons per
     * teacher.
     *
     * A shuffled teacher queue is drained in rounds. The quota for each slot is
     * `ceil(remaining lessons / remaining slots)`, capped by the number of
     * classrooms, so every slot is filled evenly instead of exhausting the
     * timetable in one long burst. Selected teachers are returned to the back
     * of the queue after the slot, giving everyone a fair share.
     *
     * Busy maps are nested as `[$id][$day][$startTime]`, making a teacher or
     * classroom double-booking impossible. Every lesson uses the teacher's
     * primary subject; classrooms in the teacher's assigned grade are preferred
     * and the least-loaded room wins ties.
     *
     * @param  EloquentCollection<int, User>  $teachers
     * @param  EloquentCollection<int, Level>  $levels
     * @param  EloquentCollection<int, Classroom>  $classrooms
     */
    private function seedSchedules(
        EloquentCollection $teachers,
        EloquentCollection $levels,
        EloquentCollection $classrooms,
    ): int {
        $timestamp = now();
        $rows = [];
        $teacherBusy = [];
        $classroomBusy = [];
        $classroomLoads = $classrooms
            ->mapWithKeys(fn (Classroom $classroom): array => [$classroom->id => 0])
            ->all();
        $levelIds = $levels->pluck('id')->values()->all();
        $teachersById = $teachers->keyBy('id');

        $slots = [];

        foreach (self::SCHOOL_DAYS as $day) {
            foreach (self::LESSON_SLOTS as [$start, $end]) {
                $slots[] = ['day' => $day, 'start' => $start, 'end' => $end];
            }
        }

        shuffle($slots);

        /** @var array<int, int> $remaining */
        $remaining = [];
        /** @var array<int, int> $preferredLevelIds */
        $preferredLevelIds = [];

        foreach ($teachers as $index => $teacher) {
            $remaining[$teacher->id] = min(
                (int) $teacher->weekly_hours_limit,
                random_int(self::MIN_TEACHER_LESSONS, self::MAX_TEACHER_LESSONS),
            );
            $preferredLevelIds[$teacher->id] = $levelIds[$index % count($levelIds)];
        }

        /**
         * Place one lesson and update every busy/load map. Returning false
         * leaves the state untouched, so the caller can retry the teacher in a
         * later slot.
         */
        $placeLesson = function (User $teacher, array $slot) use (
            &$rows,
            &$teacherBusy,
            &$classroomBusy,
            &$classroomLoads,
            $classrooms,
            $preferredLevelIds,
            $timestamp,
        ): bool {
            $day = $slot['day'];
            $start = $slot['start'];

            if (isset($teacherBusy[$teacher->id][$day][$start])) {
                return false;
            }

            $freeClassrooms = $classrooms->filter(
                fn (Classroom $classroom): bool => ! isset($classroomBusy[$classroom->id][$day][$start]),
            );

            if ($freeClassrooms->isEmpty()) {
                return false;
            }

            $preferredClassrooms = $freeClassrooms->filter(
                fn (Classroom $classroom): bool => $classroom->level_id === $preferredLevelIds[$teacher->id],
            );

            if ($preferredClassrooms->isNotEmpty()) {
                $freeClassrooms = $preferredClassrooms;
            }

            $lowestLoad = $freeClassrooms->min(
                fn (Classroom $classroom): int => $classroomLoads[$classroom->id],
            );

            $classroom = $freeClassrooms
                ->filter(fn (Classroom $classroom): bool => $classroomLoads[$classroom->id] === $lowestLoad)
                ->random();

            $groupId = null;

            if (
                $classroom->groups->isNotEmpty()
                && random_int(1, 100) <= self::GROUP_LESSON_CHANCE
            ) {
                $groupId = $classroom->groups->random()->id;
            }

            $rows[] = [
                'user_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'group_id' => $groupId,
                'subject_id' => $teacher->primary_subject_id,
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $slot['end'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $teacherBusy[$teacher->id][$day][$start] = true;
            $classroomBusy[$classroom->id][$day][$start] = true;
            $classroomLoads[$classroom->id]++;

            return true;
        };

        $queue = $teachers->pluck('id')->all();
        shuffle($queue);
        $remainingSlots = count($slots);

        foreach ($slots as $slot) {
            $remainingLessons = array_sum($remaining);

            if ($remainingLessons === 0) {
                break;
            }

            $quota = max(1, min(
                $classrooms->count(),
                (int) ceil($remainingLessons / $remainingSlots),
            ));

            $selectedTeacherIds = [];

            while ($queue !== [] && count($selectedTeacherIds) < $quota) {
                $teacherId = array_shift($queue);

                if (($remaining[$teacherId] ?? 0) <= 0) {
                    continue;
                }

                if (isset($teacherBusy[$teacherId][$slot['day']][$slot['start']])) {
                    $queue[] = $teacherId;

                    continue;
                }

                $selectedTeacherIds[] = $teacherId;
            }

            foreach ($selectedTeacherIds as $teacherId) {
                $teacher = $teachersById->get($teacherId);

                if ($teacher !== null && $placeLesson($teacher, $slot)) {
                    $remaining[$teacherId]--;
                }

                if ($remaining[$teacherId] > 0) {
                    $queue[] = $teacherId;
                }
            }

            $remainingSlots--;
        }

        // Safety net for an unlucky queue order: fill any teacher who is still
        // short into a free classroom at a time when that teacher is available.
        if (array_sum($remaining) > 0) {
            foreach ($slots as $slot) {
                foreach ($teachers as $teacher) {
                    if ($remaining[$teacher->id] <= 0) {
                        continue;
                    }

                    if ($placeLesson($teacher, $slot)) {
                        $remaining[$teacher->id]--;
                    }
                }

                if (array_sum($remaining) === 0) {
                    break;
                }
            }
        }

        if (array_sum($remaining) > 0) {
            throw new \RuntimeException(sprintf(
                'Unable to complete the timetable: %d teacher lessons remain unscheduled.',
                array_sum($remaining),
            ));
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Schedule::query()->insert($chunk);
        }

        return count($rows);
    }

    /**
     * Seed a compact annual plan for every subject/level pair.
     *
     * Each pair receives two domains, four learning sequences, and eight
     * knowledge resources. The returned map lets the daily-log generator attach
     * every lesson to a resource without extra queries.
     *
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  EloquentCollection<int, Level>  $levels
     * @return array{
     *     domains: int,
     *     sequences: int,
     *     resources: int,
     *     resources_by_pair: array<string, list<CurriculumResource>>
     * }
     */
    private function seedCurriculum(EloquentCollection $subjects, EloquentCollection $levels): array
    {
        $timestamp = now();
        $domainNames = ['الميدان الأول', 'الميدان الثاني'];
        $sequenceNames = ['المقطع الأول', 'المقطع الثاني', 'المقطع الثالث', 'المقطع الرابع'];
        $domainRows = [];

        foreach ($subjects as $subject) {
            foreach ($levels as $level) {
                foreach ($domainNames as $domainIndex => $domainName) {
                    $domainRows[] = [
                        'subject_id' => $subject->id,
                        'level_id' => $level->id,
                        'name' => $domainName,
                        'code' => 'D'.($domainIndex + 1),
                        'description' => null,
                        'sort_order' => $domainIndex + 1,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        foreach (array_chunk($domainRows, 120) as $chunk) {
            DB::table('curriculum_domains')->insert($chunk);
        }

        $domains = CurriculumDomain::query()
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->whereIn('level_id', $levels->pluck('id'))
            ->get()
            ->keyBy(fn (CurriculumDomain $domain): string => $domain->subject_id.':'.$domain->level_id.':'.$domain->name);

        $sequenceRows = [];

        foreach ($subjects as $subject) {
            foreach ($levels as $level) {
                foreach ($domainNames as $domainIndex => $domainName) {
                    $domain = $domains->get($subject->id.':'.$level->id.':'.$domainName);

                    if (! $domain) {
                        continue;
                    }

                    foreach ([1, 2] as $sequenceOffset) {
                        $sequenceIndex = ($domainIndex * 2) + $sequenceOffset;

                        $sequenceRows[] = [
                            'curriculum_domain_id' => $domain->id,
                            'name' => $sequenceNames[$sequenceIndex - 1],
                            'code' => 'S'.$sequenceIndex,
                            'description' => null,
                            'sort_order' => $sequenceIndex,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }
                }
            }
        }

        foreach (array_chunk($sequenceRows, 130) as $chunk) {
            DB::table('curriculum_sequences')->insert($chunk);
        }

        $sequences = CurriculumSequence::query()
            ->whereIn('curriculum_domain_id', $domains->pluck('id'))
            ->get()
            ->keyBy(fn (CurriculumSequence $sequence): string => $sequence->curriculum_domain_id.':'.$sequence->name);

        $resourceRows = [];

        foreach ($subjects as $subject) {
            $topics = self::LESSON_TOPICS[$subject->name] ?? ['مراجعة المكتسبات', 'وضعية إدماجية'];

            foreach ($levels as $level) {
                foreach ($domainNames as $domainIndex => $domainName) {
                    $domain = $domains->get($subject->id.':'.$level->id.':'.$domainName);

                    if (! $domain) {
                        continue;
                    }

                    foreach ([1, 2] as $sequenceOffset) {
                        $sequenceIndex = ($domainIndex * 2) + $sequenceOffset;
                        $sequence = $sequences->get($domain->id.':'.$sequenceNames[$sequenceIndex - 1]);

                        if (! $sequence) {
                            continue;
                        }

                        foreach ([1, 2] as $resourceIndex) {
                            $topic = $topics[($sequenceIndex + $resourceIndex - 2) % count($topics)];

                            $resourceRows[] = [
                                'curriculum_sequence_id' => $sequence->id,
                                'name' => $topic.' — مورد '.$resourceIndex,
                                'code' => 'R'.$resourceIndex,
                                'description' => null,
                                'sort_order' => $resourceIndex,
                                'created_at' => $timestamp,
                                'updated_at' => $timestamp,
                            ];
                        }
                    }
                }
            }
        }

        foreach (array_chunk($resourceRows, 140) as $chunk) {
            DB::table('curriculum_resources')->insert($chunk);
        }

        $domainsById = $domains->keyBy('id');
        $sequencesById = $sequences->keyBy('id');
        $resources = CurriculumResource::query()
            ->whereIn('curriculum_sequence_id', $sequences->pluck('id'))
            ->orderBy('id')
            ->get();

        $resourcesByPair = [];

        foreach ($resources as $resource) {
            $sequence = $sequencesById->get($resource->curriculum_sequence_id);
            $domain = $sequence ? $domainsById->get($sequence->curriculum_domain_id) : null;

            if (! $domain) {
                continue;
            }

            $resourcesByPair[$domain->subject_id.':'.$domain->level_id][] = $resource;
        }

        return [
            'domains' => count($domainRows),
            'sequences' => count($sequenceRows),
            'resources' => count($resourceRows),
            'resources_by_pair' => $resourcesByPair,
        ];
    }

    /**
     * Generate two occurrences (one per week) of every timetable slot across
     * the last fourteen calendar days. Content is built entirely in memory and
     * inserted in chunks, so no schedule or subject relation is queried per row.
     *
     * @param  EloquentCollection<int, Schedule>  $schedules
     * @param  EloquentCollection<int, Subject>  $subjectsById
     * @param  array<string, list<CurriculumResource>>  $resourcesByPair
     * @param  array<int, int>  $classroomLevels
     */
    private function seedDailyLogs(
        EloquentCollection $schedules,
        EloquentCollection $subjectsById,
        array $resourcesByPair,
        array $classroomLevels,
    ): int {
        $today = now();
        $start = $today->copy()->subDays(self::DAILY_LOG_HISTORY_DAYS - 1)->startOfDay();
        $end = $today->copy()->endOfDay();
        $schedulesByDay = $schedules->groupBy(
            fn (Schedule $schedule): string => $schedule->day_of_week->value,
        );
        $timestamp = now();
        $rows = [];
        $curriculumCursor = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $day = DayOfWeek::cases()[$date->dayOfWeek]->value;

            foreach ($schedulesByDay->get($day, collect()) as $schedule) {
                $subjectName = $subjectsById->get($schedule->subject_id)?->name;
                $topics = self::LESSON_TOPICS[$subjectName] ?? ['مراجعة المكتسبات', 'وضعية إدماجية'];
                $topic = $topics[array_rand($topics)];
                $title = 'درس: '.$topic;
                $competence = sprintf(
                    'يتمكن التلميذ من استثمار مكتسباته في %s والتعبير عنها في وضعيات دالة.',
                    $topic,
                );

                $phases = [
                    [
                        'phase_name' => 'التمهيد',
                        'content' => 'وضعية انطلاق قصيرة لربط الدرس بالمكتسبات السابقة وإثارة اهتمام التلاميذ.',
                    ],
                    [
                        'phase_name' => 'الاكتشاف',
                        'content' => 'أنشطة استكشافية فردية وفوجية حول '.$topic.'.',
                    ],
                    [
                        'phase_name' => 'الترسيخ',
                        'content' => 'مناقشة النتائج وبناء المفاهيم الأساسية ثم تدوين الخلاصة.',
                    ],
                    [
                        'phase_name' => 'التقويم',
                        'content' => 'تمارين تطبيقية ووضعية إدماجية قصيرة للتحقق من بلوغ الكفاءة.',
                    ],
                ];

                $status = random_int(1, 100) <= 70
                    ? DailyLogStatus::Completed
                    : DailyLogStatus::Planned;

                $homework = random_int(1, 100) <= 60
                    ? 'حل التمارين المقترحة في الكتاب المدرسي وتلخيص عناصر الدرس.'
                    : null;

                $contentData = [[
                    'type' => 'lesson_structure',
                    'data' => [
                        'competence' => $competence,
                        'lesson_phases' => $phases,
                    ],
                ]];

                $levelId = $classroomLevels[$schedule->classroom_id] ?? null;
                $pairKey = filled($levelId)
                    ? $schedule->subject_id.':'.$levelId
                    : null;
                $pairResources = $pairKey ? ($resourcesByPair[$pairKey] ?? []) : [];
                $curriculumResourceId = null;

                if ($pairKey && $pairResources !== []) {
                    $cursor = $curriculumCursor[$pairKey] ?? 0;
                    $curriculumResourceId = $pairResources[$cursor % count($pairResources)]->id;
                    $curriculumCursor[$pairKey] = $cursor + 1;
                }

                $rows[] = [
                    'schedule_id' => $schedule->id,
                    'curriculum_resource_id' => $curriculumResourceId,
                    'date' => $date->toDateString(),
                    'title' => $title,
                    'content_data' => json_encode($contentData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'actual_content' => $status === DailyLogStatus::Completed
                        ? $this->buildLessonSummary($title, $competence, $phases, $homework)
                        : null,
                    'homework' => $homework,
                    'observations' => null,
                    'status' => $status->value,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        // 80 rows × 10 columns stays below SQLite's 999-variable legacy limit
        // while still avoiding one query per daily log.
        foreach (array_chunk($rows, 80) as $chunk) {
            DB::table('daily_logs')->insert($chunk);
        }

        return count($rows);
    }

    /**
     * @param  list<array{phase_name: string, content: string}>  $phases
     */
    private function buildLessonSummary(
        string $title,
        string $competence,
        array $phases,
        ?string $homework,
    ): string {
        $lines = [
            '<p><strong>العنوان:</strong> '.e($title).'</p>',
            '<p><strong>الكفاءة المستهدفة:</strong> '.e($competence).'</p>',
        ];

        foreach ($phases as $phase) {
            $lines[] = '<p><strong>'.e($phase['phase_name']).':</strong> '.e($phase['content']).'</p>';
        }

        if (filled($homework)) {
            $lines[] = '<p><strong>الواجب المنزلي:</strong> '.e($homework).'</p>';
        }

        return implode('', $lines);
    }

    /**
     * Create one quiz or midterm for roughly a quarter of the timetable, then
     * materialise the initial grades for the exact roster of each schedule.
     *
     * The grade insert is idempotent thanks to the unique
     * (assessment_id, student_id) index and `insertOrIgnore`.
     *
     * @param  EloquentCollection<int, Schedule>  $schedules
     * @param  EloquentCollection<int, Subject>  $subjectsById
     * @return array{assessments: int, grades: int}
     */
    private function seedAssessmentsAndGrades(
        EloquentCollection $schedules,
        EloquentCollection $subjectsById,
    ): array {
        if ($schedules->isEmpty()) {
            return ['assessments' => 0, 'grades' => 0];
        }

        $timestamp = now();
        $assessmentCount = max(1, (int) round($schedules->count() * self::ASSESSMENT_RATIO));
        $assessmentRows = [];

        foreach ($schedules->shuffle()->take($assessmentCount) as $schedule) {
            $type = random_int(1, 2) === 1
                ? AssessmentType::Quiz
                : AssessmentType::Midterm;

            $title = $type === AssessmentType::Quiz ? 'اختبار قصير' : 'فرض الفصل';
            $subjectName = $subjectsById->get($schedule->subject_id)?->name;

            if (filled($subjectName)) {
                $title .= ' - '.$subjectName;
            }

            $assessmentRows[] = [
                'schedule_id' => $schedule->id,
                'title' => $title,
                'type' => $type->value,
                'date' => now()
                    ->subDays(random_int(0, self::DAILY_LOG_HISTORY_DAYS - 1))
                    ->toDateString(),
                'max_score' => 20,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        Assessment::query()->insert($assessmentRows);

        $assessments = Assessment::query()->orderBy('id')->get();
        $schedulesById = $schedules->keyBy('id');

        // Preload the roster maps once: group → students, group → classroom,
        // and classroom → students. A group lesson gets its 15 students; a
        // whole-class lesson gets the 30 students from both groups.
        $studentsByGroup = Student::query()
            ->get(['id', 'group_id'])
            ->groupBy('group_id');
        $classroomByGroup = Group::query()->pluck('classroom_id', 'id');
        $studentsByClassroom = [];

        foreach ($studentsByGroup as $groupId => $students) {
            $classroomId = $classroomByGroup->get($groupId);

            if ($classroomId === null) {
                continue;
            }

            $studentsByClassroom[$classroomId] = array_merge(
                $studentsByClassroom[$classroomId] ?? [],
                $students->pluck('id')->all(),
            );
        }

        $gradeRows = [];

        foreach ($assessments as $assessment) {
            $schedule = $schedulesById->get($assessment->schedule_id);

            if ($schedule === null) {
                continue;
            }

            $studentIds = $schedule->group_id !== null
                ? $studentsByGroup->get($schedule->group_id, collect())->pluck('id')->all()
                : ($studentsByClassroom[$schedule->classroom_id] ?? []);

            foreach ($studentIds as $studentId) {
                $gradeRows[] = [
                    'assessment_id' => $assessment->id,
                    'student_id' => $studentId,
                    'score' => round(random_int(0, 200) / 10, 1),
                    'remarks' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        foreach (array_chunk($gradeRows, 200) as $chunk) {
            DB::table('grades')->insertOrIgnore($chunk);
        }

        return [
            'assessments' => count($assessmentRows),
            'grades' => count($gradeRows),
        ];
    }

    /**
     * A realistic date of birth for the grade: 1AM students are 11–12 years
     * old and 4AM students are 14–15, so birth dates are spread across the
     * two-year window that ends at the grade's minimum age.
     */
    private function birthDateFor(int $gradeNumber): string
    {
        $minimumAge = 10 + $gradeNumber;

        // Keep the window strictly inside the age range: the earliest date is
        // one day past the (minimum + 2)-year boundary so a student cannot
        // already be min + 2, and the latest is the min-age anniversary so a
        // student cannot still be min - 1.
        return fake()->dateTimeBetween(
            now()->subYears($minimumAge + 2)->addDay()->startOfDay(),
            now()->subYears($minimumAge)->endOfDay(),
        )->format('Y-m-d');
    }
}
