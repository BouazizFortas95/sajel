<?php

namespace Database\Seeders;

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\LabStatus;
use App\Enums\LessonPhaseType;
use App\Enums\Role;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Lab;
use App\Models\LabSeatingPlan;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\StudentFactory;
use Database\Seeders\Support\AlgerianCurriculumCatalog;
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
 *   600 students · 30 teachers · a deterministic Sunday–Thursday timetable,
 *   two academic weeks of daily logs, attendance, assessments, labs, and the
 *   full pedagogical follow-up record set.
 *
 * The seeder is deliberately destructive: run it only on development or test
 * databases. It is the root seeder behind `php artisan migrate:fresh --seed`.
 */
class MiddleSchoolDatabaseSeeder extends Seeder
{
    /**
     * Academic year stamped on every generated classroom.
     */
    public const ACADEMIC_YEAR = '2026-2027';

    public const SCHOOL_NAME = 'متوسطة الشهيد العربي بن مهيدي';

    public const DIRECTORATE_NAME = 'مديرية التربية لولاية الوادي';

    public const MUNICIPALITY_NAME = 'بلدية أم الطيور';

    public const PRINCIPAL_NAME = 'الأستاذ عبد الحميد بن عمر';

    public const COUNSELOR_NAME = 'الأستاذة سعاد بوزيد';

    public const SCHOOL_CODE = '30-1102-0007';

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

    public const TEACHER_COUNT = 30;

    public const TEACHER_WEEKLY_HOURS = 22;

    public const MIN_TEACHER_LESSONS = 18;

    public const MAX_TEACHER_LESSONS = 22;

    public const ADMIN_EMAIL = 'admin@sajel.test';

    public const ADMIN_PASSWORD = 'password';

    public const DEMO_TEACHER_EMAIL = 'teacher@sajel.test';

    /**
     * The Algerian school week.
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
     * The standard one-hour lesson grid. The deterministic timetable uses the
     * first six slots (08:00–12:00 and 14:00–16:00).
     *
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

    public const SCHEDULE_SLOTS_PER_DAY = 6;

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
     * Ordered teaching blueprint. The first Mathematics teacher is the demo
     * account; six teachers own a related secondary subject.
     *
     * @var list<array{subject: string, count: int, secondary: array<int, string>}>
     */
    public const TEACHER_BLUEPRINTS = [
        ['subject' => 'اللغة العربية', 'count' => 5, 'secondary' => [0 => 'التربية الإسلامية']],
        ['subject' => 'الرياضيات', 'count' => 4, 'secondary' => [0 => 'العلوم الفيزيائية والتكنولوجيا']],
        ['subject' => 'اللغة الفرنسية', 'count' => 3, 'secondary' => [0 => 'اللغة الإنجليزية']],
        ['subject' => 'اللغة الإنجليزية', 'count' => 3, 'secondary' => []],
        ['subject' => 'التربية الإسلامية', 'count' => 2, 'secondary' => []],
        ['subject' => 'التاريخ والجغرافيا', 'count' => 2, 'secondary' => [0 => 'التربية المدنية']],
        ['subject' => 'التربية البدنية والرياضية', 'count' => 2, 'secondary' => []],
        ['subject' => 'علوم الطبيعة والحياة', 'count' => 2, 'secondary' => [0 => 'العلوم الفيزيائية والتكنولوجيا']],
        ['subject' => 'العلوم الفيزيائية والتكنولوجيا', 'count' => 2, 'secondary' => [0 => 'الإعلام الآلي']],
        ['subject' => 'الإعلام الآلي', 'count' => 2, 'secondary' => []],
        ['subject' => 'اللغة الأمازيغية', 'count' => 1, 'secondary' => []],
        ['subject' => 'التربية الفنية', 'count' => 1, 'secondary' => []],
        ['subject' => 'التربية المدنية', 'count' => 1, 'secondary' => []],
    ];

    /**
     * Subjects assessed twice per classroom.
     *
     * @var list<string>
     */
    public const CORE_ASSESSMENT_SUBJECTS = [
        'اللغة العربية',
        'الرياضيات',
        'اللغة الفرنسية',
    ];

    /**
     * Subjects whose sessions are delivered to one group in a laboratory.
     *
     * @var list<string>
     */
    public const LAB_SUBJECTS = [
        'العلوم الفيزيائية والتكنولوجيا',
        'علوم الطبيعة والحياة',
        'الإعلام الآلي',
    ];

    /**
     * The three school laboratories and their station device descriptions.
     *
     * @var list<array{name: string, prefix: string, device: string, notes: string}>
     */
    public const LABS = [
        [
            'name' => 'مخبر الإعلام الآلي 1',
            'prefix' => 'PC',
            'device' => 'computer',
            'notes' => 'مخبر مجهز بأجهزة إعلام آلي حديثة موصولة بالشبكة الداخلية.',
        ],
        [
            'name' => 'مخبر العلوم الفيزيائية',
            'prefix' => 'PH',
            'device' => 'device',
            'notes' => 'مخبر للأنشطة التجريبية في الكهرباء والميكانيك والكيمياء.',
        ],
        [
            'name' => 'مخبر علوم الطبيعة والحياة',
            'prefix' => 'SVT',
            'device' => 'device',
            'notes' => 'مخبر مجهز بالمجاسم والوثائق والعتاد المخبري لعلوم الطبيعة.',
        ],
    ];

    /**
     * A small pool of Arabic first names used for the teaching staff.
     *
     * @var list<string>
     */
    private const TEACHER_FIRST_NAMES = [
        'عبد الحميد', 'سعاد', 'محمد', 'فاطمة', 'يوسف', 'خديجة', 'أحمد', 'مريم',
        'عبد القادر', 'نادية', 'كريم', 'سميرة', 'رشيد', 'ليلى', 'صالح', 'أمينة',
        'طارق', 'حنان', 'بلال', 'سهام', 'عمر', 'زينب', 'إلياس', 'نورة',
        'سفيان', 'وردة', 'أنيس', 'هدى', 'جمال', 'سلمى',
    ];

    /**
     * @var list<string>
     */
    private const ADMIN_NOTES = [
        'دفتر منظم ومطابق للتدرج السنوي — بالتوفيق.',
        'محتوى الحصة واضح، ووُظفت الوسائل المناسبة.',
        'يُستحسن إضافة وضعية إدماجية في الحصة القادمة.',
        'التوثيق جيد، مع ضرورة متابعة التلاميذ المتعثرين.',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ATTENDANCE_REMARKS = [
        AttendanceStatus::Absent->value => [
            'غياب غير مبرر',
            'غياب بسبب مرض عارض',
            'تغيب دون إشعار مسبق',
        ],
        AttendanceStatus::Late->value => [
            'تأخر صباحي',
            'تأخر بسبب النقل المدرسي',
            'تأخر متكرر في الحصة الأولى',
        ],
        AttendanceStatus::Excused->value => [
            'مبرر بشهادة طبية',
            'غياب مبرر بطلب من الولي',
            'مشاركة في نشاط رياضي خارج المؤسسة',
        ],
    ];

    /**
     * Tables removed before every run, children first so the seeder also works
     * while foreign-key enforcement is active (for example inside tests).
     *
     * @var list<string>
     */
    private const WIPE_TABLES = [
        'lab_seating_plan_stations',
        'lab_seating_plans',
        'lab_stations',
        'labs',
        'pedagogical_remediations',
        'behavior_observations',
        'pedagogical_meetings',
        'pedagogical_memos',
        'grades',
        'attendances',
        'assessments',
        'daily_logs',
        'curriculum_resources',
        'curriculum_sequences',
        'curriculum_domains',
        'schedules',
        'students',
        'groups',
        'classrooms',
        'levels',
        'education_phases',
        'subjects',
        'school_holidays',
        'school_settings',
        'notifications',
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
                SchoolSetting::flushCache();

                $this->call(ActiveLearningStrategySeeder::class);

                $this->seedSchoolSetting();
                $this->call(SchoolHolidaySeeder::class);

                $subjects = $this->seedSubjects();
                $phase = $this->seedPhase();
                $structure = $this->seedStructure($phase);

                $this->seedStudents($structure['classrooms']);
                $studentMaps = $this->studentMaps();

                $users = $this->seedUsers($subjects);
                $teachers = $users['teachers'];

                $scheduleCount = $this->seedSchedules(
                    $teachers,
                    $subjects,
                    $structure['classrooms'],
                );

                $curriculum = $this->seedCurriculum($subjects, $structure['levels']);
                $schedules = Schedule::query()->orderBy('id')->get();

                $dailyLogCount = $this->seedDailyLogs(
                    $schedules,
                    $subjects,
                    $curriculum['resources_by_pair'],
                    $structure['classrooms'],
                    $users['admin'],
                    $teachers,
                );

                $attendance = $this->seedAttendance(
                    $structure['classrooms'],
                    $studentMaps['by_group'],
                    $studentMaps['by_classroom'],
                );

                $gradebook = $this->seedAssessmentsAndGrades(
                    $structure['classrooms'],
                    $schedules,
                    $subjects,
                    $studentMaps['by_classroom'],
                );

                $laboratory = $this->seedLabsAndSeating(
                    $structure['classrooms'],
                    $studentMaps['by_group'],
                );

                $pedagogy = $this->seedPedagogicalData(
                    $teachers,
                    $subjects,
                    $structure['levels'],
                    $structure['classrooms'],
                    $studentMaps['by_classroom'],
                    $curriculum['resources_by_pair'],
                    $schedules,
                    $gradebook['struggling_by_classroom'],
                );

                return [
                    'subjects' => $subjects->count(),
                    'levels' => $structure['levels']->count(),
                    'classrooms' => $structure['classrooms']->count(),
                    'groups' => $structure['classrooms']->sum(
                        fn (Classroom $classroom): int => $classroom->groups->count(),
                    ),
                    'students' => Student::query()->count(),
                    'teachers' => $teachers->count(),
                    'schedules' => $scheduleCount,
                    'daily_logs' => $dailyLogCount,
                    'attendance' => $attendance['count'],
                    'curriculum_domains' => $curriculum['domains'],
                    'curriculum_sequences' => $curriculum['sequences'],
                    'curriculum_resources' => $curriculum['resources'],
                    'assessments' => $gradebook['assessments'],
                    'grades' => $gradebook['grades'],
                    'labs' => $laboratory['labs'],
                    'stations' => $laboratory['stations'],
                    'memos' => $pedagogy['memos'],
                    'observations' => $pedagogy['observations'],
                    'remediations' => $pedagogy['remediations'],
                    'meetings' => $pedagogy['meetings'],
                ];
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command?->info(sprintf(
            'Middle-school dataset ready: %d subjects, %d levels, %d classrooms, %d groups, %d students, %d teachers, %d schedules, %d daily logs, %d attendance rows, %d curriculum domains, %d sequences, %d resources, %d assessments, %d grades, %d labs, %d stations, %d memos, %d observations, %d remediations, %d meetings.',
            $stats['subjects'],
            $stats['levels'],
            $stats['classrooms'],
            $stats['groups'],
            $stats['students'],
            $stats['teachers'],
            $stats['schedules'],
            $stats['daily_logs'],
            $stats['attendance'],
            $stats['curriculum_domains'],
            $stats['curriculum_sequences'],
            $stats['curriculum_resources'],
            $stats['assessments'],
            $stats['grades'],
            $stats['labs'],
            $stats['stations'],
            $stats['memos'],
            $stats['observations'],
            $stats['remediations'],
            $stats['meetings'],
        ));
    }

    private function wipe(): void
    {
        foreach (self::WIPE_TABLES as $table) {
            DB::table($table)->truncate();
        }
    }

    private function seedSchoolSetting(): void
    {
        SchoolSetting::create([
            'school_name' => self::SCHOOL_NAME,
            'directorate_name' => self::DIRECTORATE_NAME,
            'municipality_name' => self::MUNICIPALITY_NAME,
            'academic_year' => self::ACADEMIC_YEAR,
            'principal_name' => self::PRINCIPAL_NAME,
            'counselor_name' => self::COUNSELOR_NAME,
            'school_code' => self::SCHOOL_CODE,
        ]);
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
            ->with(['level', 'groups'])
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
    private function seedStudents(EloquentCollection $classrooms): void
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
    }

    /**
     * Load the student roster once and build the group/classroom indexes used
     * by attendance, grading, labs, and pedagogical follow-up.
     *
     * @return array{
     *     by_group: array<int, list<int>>,
     *     by_classroom: array<int, list<int>>
     * }
     */
    private function studentMaps(): array
    {
        $students = Student::query()
            ->orderBy('id')
            ->get(['id', 'group_id']);
        $classroomByGroup = Group::query()->pluck('classroom_id', 'id');

        $byGroup = [];
        $byClassroom = [];

        foreach ($students as $student) {
            $groupId = (int) $student->group_id;
            $classroomId = $classroomByGroup->get($groupId);

            if ($classroomId === null) {
                continue;
            }

            $byGroup[$groupId][] = (int) $student->id;
            $byClassroom[(int) $classroomId][] = (int) $student->id;
        }

        return [
            'by_group' => $byGroup,
            'by_classroom' => $byClassroom,
        ];
    }

    /**
     * Bulk-insert the administrator and the 30 subject teachers.
     *
     * @param  EloquentCollection<int, Subject>  $subjects
     * @return array{admin: User, teachers: EloquentCollection<int, User>}
     */
    private function seedUsers(EloquentCollection $subjects): array
    {
        $timestamp = now();
        $password = Hash::make(self::ADMIN_PASSWORD);
        $subjectIdsByName = $subjects->pluck('id', 'name')->all();
        $emailCounter = 2;
        $teacherIndex = 0;
        $rows = [[
            'name' => 'مدير المنصة',
            'email' => self::ADMIN_EMAIL,
            'email_verified_at' => $timestamp,
            'password' => $password,
            'role' => Role::Admin->value,
            'primary_subject_id' => null,
            'secondary_subject_id' => null,
            'weekly_hours_limit' => self::TEACHER_WEEKLY_HOURS,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]];

        foreach (self::TEACHER_BLUEPRINTS as $blueprint) {
            for ($offset = 0; $offset < $blueprint['count']; $offset++) {
                $isDemo = $blueprint['subject'] === 'الرياضيات' && $offset === 0;
                $email = $isDemo
                    ? self::DEMO_TEACHER_EMAIL
                    : sprintf('teacher.%02d@sajel.test', $emailCounter++);

                $secondaryName = $blueprint['secondary'][$offset] ?? null;

                $rows[] = [
                    'name' => $this->teacherFullName($teacherIndex),
                    'email' => $email,
                    'email_verified_at' => $timestamp,
                    'password' => $password,
                    'role' => Role::Teacher->value,
                    'primary_subject_id' => $subjectIdsByName[$blueprint['subject']] ?? null,
                    'secondary_subject_id' => $secondaryName !== null
                        ? ($subjectIdsByName[$secondaryName] ?? null)
                        : null,
                    'weekly_hours_limit' => self::TEACHER_WEEKLY_HOURS,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                $teacherIndex++;
            }
        }

        if ($teacherIndex !== self::TEACHER_COUNT) {
            throw new \RuntimeException(sprintf(
                'The teaching blueprint creates %d teachers instead of %d.',
                $teacherIndex,
                self::TEACHER_COUNT,
            ));
        }

        User::query()->insert($rows);

        $users = User::query()->orderBy('id')->get();
        $admin = $users->firstWhere('email', self::ADMIN_EMAIL);
        $teachers = $users
            ->filter(fn (User $user): bool => $user->isTeacher())
            ->values();

        if (! $admin instanceof User) {
            throw new \RuntimeException('The administrator account was not created.');
        }

        return [
            'admin' => $admin,
            'teachers' => $teachers,
        ];
    }

    private function teacherFullName(int $index): string
    {
        $firstNames = self::TEACHER_FIRST_NAMES;
        $familyNames = StudentFactory::FAMILY_NAMES;

        return sprintf(
            '%s %s',
            $firstNames[$index % count($firstNames)],
            $familyNames[($index * 7) % count($familyNames)],
        );
    }

    /**
     * Build a deterministic, conflict-free timetable:
     *
     *   30 blocks (5 days × first 6 slots) × 20 classrooms.
     *   Teacher for block k / classroom c is teachers[(k + c) % 30].
     *
     * Every teacher teaches exactly one lesson to every classroom (20 h), and
     * every classroom receives the full subject mix. Physics, Science, and
     * Informatics split their two weekly sessions between group A and group B.
     *
     * @param  EloquentCollection<int, User>  $teachers
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  EloquentCollection<int, Classroom>  $classrooms
     */
    private function seedSchedules(
        EloquentCollection $teachers,
        EloquentCollection $subjects,
        EloquentCollection $classrooms,
    ): int {
        $timestamp = now();
        $slots = [];

        foreach (self::SCHOOL_DAYS as $day) {
            foreach (array_slice(self::LESSON_SLOTS, 0, self::SCHEDULE_SLOTS_PER_DAY) as [$start, $end]) {
                $slots[] = ['day' => $day, 'start' => $start, 'end' => $end];
            }
        }

        if (count($slots) !== 30) {
            throw new \RuntimeException('The timetable must contain exactly 30 weekly blocks.');
        }

        $teacherList = $teachers->values()->all();
        $classroomList = $classrooms->values()->all();
        $labSubjectIds = $subjects
            ->whereIn('name', self::LAB_SUBJECTS)
            ->pluck('id')
            ->flip()
            ->all();
        $rows = [];
        $labOccurrences = [];

        foreach ($slots as $blockIndex => $slot) {
            foreach ($classroomList as $classroomIndex => $classroom) {
                $teacher = $teacherList[($blockIndex + $classroomIndex) % count($teacherList)];
                $subjectId = (int) $teacher->primary_subject_id;
                $groupId = null;

                if (isset($labSubjectIds[$subjectId])) {
                    $groups = $classroom->groups->sortBy('id')->values();
                    $key = $classroom->id.':'.$subjectId;
                    $occurrence = $labOccurrences[$key] ?? 0;

                    if ($groups->isNotEmpty()) {
                        $groupId = $groups[$occurrence % $groups->count()]->id;
                    }

                    $labOccurrences[$key] = $occurrence + 1;
                }

                $rows[] = [
                    'user_id' => $teacher->id,
                    'classroom_id' => $classroom->id,
                    'group_id' => $groupId,
                    'subject_id' => $subjectId,
                    'day_of_week' => $slot['day'],
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Schedule::query()->insert($chunk);
        }

        return count($rows);
    }

    /**
     * Seed the authentic annual plan for every subject/level pair.
     *
     * Each pair receives two domains, four learning sequences, and eight
     * knowledge resources. The returned map lets the daily-log generator attach
     * every lesson to a real resource without extra queries.
     *
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  EloquentCollection<int, Level>  $levels
     * @return array{
     *     domains: int,
     *     sequences: int,
     *     resources: int,
     *     resources_by_pair: array<string, list<array<string, mixed>>>
     * }
     */
    private function seedCurriculum(EloquentCollection $subjects, EloquentCollection $levels): array
    {
        $timestamp = now();
        $domainRows = [];

        foreach ($subjects as $subject) {
            foreach ($levels as $level) {
                $program = AlgerianCurriculumCatalog::program(
                    $subject->name,
                    (int) $level->grade_number,
                );

                foreach ($program as $domain) {
                    $domainRows[] = [
                        'subject_id' => $subject->id,
                        'level_id' => $level->id,
                        'name' => $domain['name'],
                        'code' => $domain['code'],
                        'description' => $domain['description'],
                        'sort_order' => $domain['sort_order'],
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
                $program = AlgerianCurriculumCatalog::program(
                    $subject->name,
                    (int) $level->grade_number,
                );

                foreach ($program as $domain) {
                    $domainModel = $domains->get(
                        $subject->id.':'.$level->id.':'.$domain['name'],
                    );

                    if (! $domainModel instanceof CurriculumDomain) {
                        continue;
                    }

                    foreach ($domain['sequences'] as $sequence) {
                        $sequenceRows[] = [
                            'curriculum_domain_id' => $domainModel->id,
                            'name' => $sequence['name'],
                            'code' => $sequence['code'],
                            'description' => $sequence['description'],
                            'sort_order' => $sequence['sort_order'],
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
        $sequencesById = $sequences->keyBy('id');
        $domainsById = $domains->keyBy('id');

        $resourceRows = [];
        $resourceMetadata = [];

        foreach ($subjects as $subject) {
            foreach ($levels as $level) {
                $program = AlgerianCurriculumCatalog::program(
                    $subject->name,
                    (int) $level->grade_number,
                );

                foreach ($program as $domain) {
                    $domainModel = $domains->get(
                        $subject->id.':'.$level->id.':'.$domain['name'],
                    );

                    if (! $domainModel instanceof CurriculumDomain) {
                        continue;
                    }

                    foreach ($domain['sequences'] as $sequence) {
                        $sequenceModel = $sequences->get($domainModel->id.':'.$sequence['name']);

                        if (! $sequenceModel instanceof CurriculumSequence) {
                            continue;
                        }

                        foreach ($sequence['resources'] as $resourceIndex => $resource) {
                            $resourceRows[] = [
                                'curriculum_sequence_id' => $sequenceModel->id,
                                'name' => $resource['title'],
                                'code' => $sequence['code'].'-R'.($resourceIndex + 1),
                                'description' => $sequence['name'].' — '.$resource['title'],
                                'sort_order' => $resourceIndex + 1,
                                'created_at' => $timestamp,
                                'updated_at' => $timestamp,
                            ];

                            $resourceMetadata[$sequenceModel->id.':'.$resource['title']] = $resource;
                        }
                    }
                }
            }
        }

        foreach (array_chunk($resourceRows, 140) as $chunk) {
            DB::table('curriculum_resources')->insert($chunk);
        }

        $resources = CurriculumResource::query()
            ->whereIn('curriculum_sequence_id', $sequences->pluck('id'))
            ->orderBy('id')
            ->get();

        $resourcesByPair = [];

        foreach ($resources as $resource) {
            $sequence = $sequencesById->get($resource->curriculum_sequence_id);

            if (! $sequence instanceof CurriculumSequence) {
                continue;
            }

            $domain = $domainsById->get($sequence->curriculum_domain_id);

            if (! $domain instanceof CurriculumDomain) {
                continue;
            }

            $metadata = $resourceMetadata[$sequence->id.':'.$resource->name] ?? null;

            if (! is_array($metadata)) {
                continue;
            }

            $resourcesByPair[$domain->subject_id.':'.$domain->level_id][] = [
                'id' => (int) $resource->id,
                'title' => $metadata['title'],
                'competence' => $metadata['competence'],
                'starting_situation' => $metadata['starting_situation'],
                'phases' => $metadata['phases'],
                'actual_content' => $metadata['actual_content'],
                'homework' => $metadata['homework'],
            ];
        }

        return [
            'domains' => count($domainRows),
            'sequences' => count($sequenceRows),
            'resources' => count($resourceRows),
            'resources_by_pair' => $resourcesByPair,
        ];
    }

    /**
     * Generate two academic weeks of prepared lessons.
     *
     * Window: previous Sunday → current Thursday. Each weekly schedule occurs
     * exactly twice. Past sessions are Completed; sessions later today and
     * Tuesday–Thursday of the current week are Planned.
     *
     * @param  EloquentCollection<int, Schedule>  $schedules
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  array<string, list<array<string, mixed>>>  $resourcesByPair
     * @param  EloquentCollection<int, Classroom>  $classrooms
     * @param  EloquentCollection<int, User>  $teachers
     */
    private function seedDailyLogs(
        EloquentCollection $schedules,
        EloquentCollection $subjects,
        array $resourcesByPair,
        EloquentCollection $classrooms,
        User $admin,
        EloquentCollection $teachers,
    ): int {
        $now = now();
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $start = $weekStart->copy()->subWeek()->startOfDay();
        $end = $weekStart->copy()->addDays(4)->endOfDay();
        $timestamp = $now->copy();
        $schedulesByDay = $schedules->groupBy(
            fn (Schedule $schedule): string => $schedule->day_of_week->value,
        );
        $classroomLevels = $classrooms
            ->mapWithKeys(fn (Classroom $classroom): array => [$classroom->id => $classroom->level_id])
            ->all();
        $teacherIdsBySubject = $teachers
            ->groupBy('primary_subject_id')
            ->map(fn (EloquentCollection $group): array => $group->pluck('id')->all())
            ->all();
        $subjectsById = $subjects->keyBy('id');
        $logs = [];
        $cursor = [];
        $logIndex = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isFriday() || $date->isSaturday()) {
                continue;
            }

            $day = DayOfWeek::cases()[$date->dayOfWeek]->value;

            foreach ($schedulesByDay->get($day, collect()) as $schedule) {
                $logIndex++;
                $levelId = $classroomLevels[$schedule->classroom_id] ?? null;
                $pairKey = $levelId !== null
                    ? $schedule->subject_id.':'.$levelId
                    : null;
                $pairResources = $pairKey !== null
                    ? ($resourcesByPair[$pairKey] ?? [])
                    : [];

                if ($pairResources === []) {
                    throw new \RuntimeException(sprintf(
                        'No curriculum resource for schedule %d (subject %d, level %s).',
                        $schedule->id,
                        $schedule->subject_id,
                        $levelId ?? 'null',
                    ));
                }

                $cursorKey = $schedule->classroom_id.':'.$schedule->subject_id;
                $resourceCursor = $cursor[$cursorKey] ?? 0;
                $resource = $pairResources[$resourceCursor % count($pairResources)];
                $cursor[$cursorKey] = $resourceCursor + 1;

                $title = 'درس: '.$resource['title'];
                $blocks = $this->lessonBlocks($resource);
                $status = DailyLogStatus::Planned;
                $isPast = $date->lt($now->copy()->startOfDay());
                $isToday = $date->isSameDay($now);
                $slotPassed = $schedule->end_time->format('H:i') <= $now->format('H:i');

                if ($isPast || ($isToday && $slotPassed)) {
                    $status = DailyLogStatus::Completed;
                }

                $reviewerId = null;
                $reviewedAt = null;
                $adminNotes = null;

                if ($status === DailyLogStatus::Completed && ($logIndex % 100) < 65) {
                    $reviewerId = $admin->id;
                    $reviewAt = $date->copy()->setTime(16, 0);

                    if ($reviewAt->gt($now)) {
                        $reviewAt = $now->copy();
                    }

                    $reviewedAt = $reviewAt->format('Y-m-d H:i:s');
                    $adminNotes = self::ADMIN_NOTES[$logIndex % count(self::ADMIN_NOTES)];
                }

                $substituteId = null;

                if ($logIndex % 50 === 0) {
                    $candidates = array_values(array_filter(
                        $teacherIdsBySubject[$schedule->subject_id] ?? [],
                        fn (int $teacherId): bool => $teacherId !== (int) $schedule->user_id,
                    ));

                    if ($candidates !== []) {
                        $substituteId = $candidates[intdiv($logIndex, 50) % count($candidates)];
                    }
                }

                $logs[] = [
                    'substitute_teacher_id' => $substituteId,
                    'schedule_id' => $schedule->id,
                    'curriculum_resource_id' => $resource['id'],
                    'date' => $date->toDateString(),
                    'title' => $title,
                    'content_data' => json_encode(
                        $blocks,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                    ),
                    'actual_content' => $status === DailyLogStatus::Completed
                        ? $resource['actual_content']
                        : null,
                    'homework' => $resource['homework'],
                    'observations' => null,
                    'status' => $status->value,
                    'reviewed_by_id' => $reviewerId,
                    'reviewed_at' => $reviewedAt,
                    'admin_notes' => $adminNotes,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                if (count($logs) >= 80) {
                    DB::table('daily_logs')->insert($logs);
                    $logs = [];
                }
            }
        }

        if ($logs !== []) {
            DB::table('daily_logs')->insert($logs);
        }

        return $logIndex;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    private function lessonBlocks(array $resource): array
    {
        $phaseTypes = [
            LessonPhaseType::Warmup->value,
            LessonPhaseType::Discovery->value,
            LessonPhaseType::Institutionalization->value,
            LessonPhaseType::Evaluation->value,
        ];
        $durations = ['10', '20', '20', '10'];
        $strategies = ['العمل الجماعي', 'الاستقصاء والعمل الفوجي', 'الحوار والمناقشة', 'التقويم التكويني'];
        $middleSchoolPhases = [];

        foreach ($resource['phases'] as $index => $phase) {
            $middleSchoolPhases[] = [
                'type' => $phaseTypes[$index] ?? LessonPhaseType::Application->value,
                'duration' => $durations[$index] ?? '10',
                'description' => $phase['content'],
                'strategy' => $strategies[$index] ?? 'العمل الفوجي',
            ];
        }

        return [
            [
                'type' => 'lesson_structure',
                'data' => [
                    'competence' => $resource['competence'],
                    'starting_situation' => $resource['starting_situation'],
                    'lesson_phases' => array_map(
                        fn (array $phase): array => [
                            'phase_name' => $phase['phase_name'],
                            'content' => $phase['content'],
                        ],
                        $resource['phases'],
                    ),
                ],
            ],
            [
                'type' => 'middle_school_lesson',
                'data' => [
                    'lesson_title' => $resource['title'],
                    'competence' => $resource['competence'],
                    'pedagogical_situation' => $resource['starting_situation'],
                    'phases' => $middleSchoolPhases,
                ],
            ],
        ];
    }

    /**
     * Materialise attendance for every completed log.
     *
     * A student × school-date status map is built first so all sessions on the
     * same day agree. Four students per classroom are present every day, two
     * repeat absentees miss three distinct dates, and the rest follow the
     * 90/5/3/2 distribution.
     *
     * @param  EloquentCollection<int, Classroom>  $classrooms
     * @param  array<int, list<int>>  $studentsByGroup
     * @param  array<int, list<int>>  $studentsByClassroom
     * @return array{count: int, statuses: array<string, int>}
     */
    private function seedAttendance(
        EloquentCollection $classrooms,
        array $studentsByGroup,
        array $studentsByClassroom,
    ): array {
        $completedLogs = DailyLog::query()
            ->where('status', DailyLogStatus::Completed->value)
            ->with('schedule:id,classroom_id,group_id,subject_id')
            ->orderBy('id')
            ->get();

        if ($completedLogs->isEmpty()) {
            return ['count' => 0, 'statuses' => []];
        }

        $groupClassroom = Group::query()->pluck('classroom_id', 'id');
        $datesByClassroom = [];

        foreach ($completedLogs as $log) {
            $classroomId = $log->schedule?->classroom_id
                ?? $groupClassroom->get($log->schedule?->group_id);

            if ($classroomId !== null) {
                $datesByClassroom[(int) $classroomId][$log->date->toDateString()] = true;
            }
        }

        $statusByStudentDate = [];
        $topStudents = [];
        $repeatAbsentees = [];

        foreach ($classrooms as $classroom) {
            $roster = $studentsByClassroom[$classroom->id] ?? [];
            $dates = array_keys($datesByClassroom[$classroom->id] ?? []);
            sort($dates);
            $topStudents[$classroom->id] = array_slice($roster, 0, 4);
            $repeatAbsentees[$classroom->id] = array_slice($roster, -2);

            foreach ($topStudents[$classroom->id] as $studentId) {
                foreach ($dates as $date) {
                    $statusByStudentDate[$studentId][$date] = AttendanceStatus::Present->value;
                }
            }

            foreach ($repeatAbsentees[$classroom->id] as $repeatIndex => $studentId) {
                $absenceDates = array_slice($dates, $repeatIndex * 2, 3);

                foreach ($dates as $date) {
                    $statusByStudentDate[$studentId][$date] = in_array($date, $absenceDates, true)
                        ? AttendanceStatus::Absent->value
                        : AttendanceStatus::Present->value;
                }
            }

            $specialStudents = array_merge(
                $topStudents[$classroom->id],
                $repeatAbsentees[$classroom->id],
            );

            foreach ($roster as $studentId) {
                if (in_array($studentId, $specialStudents, true)) {
                    continue;
                }

                foreach ($dates as $date) {
                    $roll = random_int(1, 100);

                    $statusByStudentDate[$studentId][$date] = match (true) {
                        $roll <= 90 => AttendanceStatus::Present->value,
                        $roll <= 95 => AttendanceStatus::Absent->value,
                        $roll <= 98 => AttendanceStatus::Late->value,
                        default => AttendanceStatus::Excused->value,
                    };
                }
            }
        }

        $timestamp = now();
        $rows = [];
        $statusCounts = [
            AttendanceStatus::Present->value => 0,
            AttendanceStatus::Absent->value => 0,
            AttendanceStatus::Late->value => 0,
            AttendanceStatus::Excused->value => 0,
        ];

        foreach ($completedLogs as $log) {
            $schedule = $log->schedule;

            if ($schedule === null) {
                continue;
            }

            $classroomId = $schedule->classroom_id
                ?? $groupClassroom->get($schedule->group_id);

            if ($classroomId === null) {
                continue;
            }

            $roster = $schedule->group_id !== null
                ? ($studentsByGroup[$schedule->group_id] ?? [])
                : ($studentsByClassroom[(int) $classroomId] ?? []);
            $date = $log->date->toDateString();

            foreach ($roster as $studentId) {
                $status = $statusByStudentDate[$studentId][$date]
                    ?? AttendanceStatus::Present->value;
                $remarks = null;

                if ($status !== AttendanceStatus::Present->value) {
                    $pool = self::ATTENDANCE_REMARKS[$status] ?? [];
                    $remarks = $pool !== []
                        ? $pool[($log->id + $studentId) % count($pool)]
                        : null;
                }

                $rows[] = [
                    'daily_log_id' => $log->id,
                    'student_id' => $studentId,
                    'status' => $status,
                    'remarks' => $remarks,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $statusCounts[$status]++;

                if (count($rows) >= 150) {
                    DB::table('attendances')->insertOrIgnore($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('attendances')->insertOrIgnore($rows);
        }

        return [
            'count' => array_sum($statusCounts),
            'statuses' => $statusCounts,
        ];
    }

    /**
     * Create two sequential assessments for Arabic, Mathematics, and French in
     * every classroom, with a full 30-student grade sheet and four realistic
     * learner profiles.
     *
     * @param  EloquentCollection<int, Classroom>  $classrooms
     * @param  EloquentCollection<int, Schedule>  $schedules
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  array<int, list<int>>  $studentsByClassroom
     * @return array{
     *     assessments: int,
     *     grades: int,
     *     struggling_by_classroom: array<int, list<int>>
     * }
     */
    private function seedAssessmentsAndGrades(
        EloquentCollection $classrooms,
        EloquentCollection $schedules,
        EloquentCollection $subjects,
        array $studentsByClassroom,
    ): array {
        $subjectsByName = $subjects->keyBy('name');
        $schedulesByClassroomSubject = [];

        foreach ($schedules as $schedule) {
            if ($schedule->group_id !== null || $schedule->classroom_id === null) {
                continue;
            }

            $schedulesByClassroomSubject[$schedule->classroom_id][$schedule->subject_id][] = $schedule;
        }

        $weekStart = now()->copy()->startOfWeek(Carbon::SUNDAY);
        $assessmentDates = [
            $weekStart->copy()->subWeek()->toDateString(),
            $weekStart->toDateString(),
        ];
        $timestamp = now();
        $assessmentRows = [];

        foreach ($classrooms as $classroom) {
            foreach (self::CORE_ASSESSMENT_SUBJECTS as $subjectName) {
                $subject = $subjectsByName->get($subjectName);

                if (! $subject instanceof Subject) {
                    continue;
                }

                $schedule = $schedulesByClassroomSubject[$classroom->id][$subject->id][0] ?? null;

                if (! $schedule instanceof Schedule) {
                    continue;
                }

                foreach ([0, 1] as $index) {
                    $assessmentRows[] = [
                        'schedule_id' => $schedule->id,
                        'title' => sprintf(
                            'الفرض المحروس %s — %s',
                            $index === 0 ? 'الأول' : 'الثاني',
                            $subjectName,
                        ),
                        'type' => $index === 0
                            ? AssessmentType::Midterm->value
                            : AssessmentType::Quiz->value,
                        'date' => $assessmentDates[$index],
                        'max_score' => 20,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        Assessment::query()->insert($assessmentRows);

        $assessments = Assessment::query()->orderBy('id')->get();
        $schedulesById = $schedules->keyBy('id');
        $assessmentsByPair = [];

        foreach ($assessments as $assessment) {
            $schedule = $schedulesById->get($assessment->schedule_id);

            if (! $schedule instanceof Schedule) {
                continue;
            }

            $pairKey = $schedule->classroom_id.':'.$schedule->subject_id;
            $assessmentsByPair[$pairKey][] = $assessment;
        }

        $profileByStudent = [];
        $strugglingByClassroom = [];

        foreach ($classrooms as $classroomIndex => $classroom) {
            $roster = $studentsByClassroom[$classroom->id] ?? [];
            $comebackCount = $classroomIndex % 2 === 0 ? 8 : 7;
            $strugglingCount = $classroomIndex % 2 === 0 ? 4 : 5;
            $profileSequence = array_merge(
                array_fill(0, 6, 'excellence'),
                array_fill(0, $comebackCount, 'comeback'),
                array_fill(0, 12, 'steady'),
                array_fill(0, $strugglingCount, 'struggling'),
            );

            foreach ($roster as $index => $studentId) {
                $profile = $profileSequence[$index] ?? 'steady';
                $profileByStudent[$studentId] = $profile;

                if ($profile === 'struggling') {
                    $strugglingByClassroom[$classroom->id][] = $studentId;
                }
            }
        }

        $scoreBases = [];

        foreach ($profileByStudent as $studentId => $profile) {
            $scoreBases[$studentId] = [
                $this->profileScore($profile, 0),
                $this->profileScore($profile, 1),
            ];
        }

        $gradeJitter = [
            'اللغة العربية' => 0.0,
            'الرياضيات' => 0.2,
            'اللغة الفرنسية' => -0.2,
        ];
        $gradeRows = [];

        foreach ($classrooms as $classroom) {
            $roster = $studentsByClassroom[$classroom->id] ?? [];

            foreach (self::CORE_ASSESSMENT_SUBJECTS as $subjectName) {
                $subject = $subjectsByName->get($subjectName);

                if (! $subject instanceof Subject) {
                    continue;
                }

                $pairKey = $classroom->id.':'.$subject->id;
                $pairAssessments = $assessmentsByPair[$pairKey] ?? [];

                usort(
                    $pairAssessments,
                    fn (Assessment $a, Assessment $b): int => [
                        $a->date->toDateString(),
                        $a->id,
                    ] <=> [
                        $b->date->toDateString(),
                        $b->id,
                    ],
                );

                foreach ($pairAssessments as $index => $assessment) {
                    foreach ($roster as $studentId) {
                        $profile = $profileByStudent[$studentId] ?? 'steady';
                        $base = $scoreBases[$studentId][$index] ?? 10.0;

                        $score = $profile === 'comeback'
                            ? $base
                            : $this->clampProfileScore($profile, $base + $gradeJitter[$subjectName]);

                        $gradeRows[] = [
                            'assessment_id' => $assessment->id,
                            'student_id' => $studentId,
                            'score' => $score,
                            'remarks' => $this->gradeRemark($profile, $index),
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];

                        if (count($gradeRows) >= 150) {
                            DB::table('grades')->insert($gradeRows);
                            $gradeRows = [];
                        }
                    }
                }
            }
        }

        if ($gradeRows !== []) {
            DB::table('grades')->insert($gradeRows);
        }

        return [
            'assessments' => count($assessmentRows),
            'grades' => count($assessmentRows) * 30,
            'struggling_by_classroom' => $strugglingByClassroom,
        ];
    }

    private function profileScore(string $profile, int $assessmentIndex): float
    {
        return match ($profile) {
            'excellence' => round(random_int(160, 195) / 10, 1),
            'comeback' => $assessmentIndex === 0 ? 9.5 : 13.5,
            'steady' => round(random_int(105, 155) / 10, 1),
            'struggling' => round(random_int(55, 90) / 10, 1),
            default => 10.0,
        };
    }

    private function clampProfileScore(string $profile, float $score): float
    {
        [$minimum, $maximum] = match ($profile) {
            'excellence' => [16.0, 19.5],
            'steady' => [10.5, 15.5],
            'struggling' => [5.5, 9.0],
            default => [0.0, 20.0],
        };

        return round(min($maximum, max($minimum, $score)), 1);
    }

    private function gradeRemark(string $profile, int $assessmentIndex): string
    {
        return match ($profile) {
            'excellence' => 'ممتاز',
            'comeback' => $assessmentIndex === 0 ? 'يحتاج معالجة بيداغوجية' : 'تحسن ملحوظ',
            'steady' => 'مستقر',
            'struggling' => 'يحتاج معالجة بيداغوجية',
            default => 'مستقر',
        };
    }

    /**
     * Seed three 20-station laboratories and two seating plans for the first
     * group of classroom 1 م 1.
     *
     * @param  EloquentCollection<int, Classroom>  $classrooms
     * @param  array<int, list<int>>  $studentsByGroup
     * @return array{labs: int, stations: int, plan_stations: int}
     */
    private function seedLabsAndSeating(
        EloquentCollection $classrooms,
        array $studentsByGroup,
    ): array {
        $timestamp = now();
        $labRows = [];

        foreach (self::LABS as $lab) {
            $labRows[] = [
                'level_id' => null,
                'name' => $lab['name'],
                'academic_year' => self::ACADEMIC_YEAR,
                'capacity' => 20,
                'notes' => $lab['notes'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        Lab::query()->insert($labRows);

        $labs = Lab::query()->orderBy('id')->get();
        $stationRows = [];

        foreach ($labs as $index => $lab) {
            $definition = self::LABS[$index] ?? self::LABS[0];

            for ($stationNumber = 1; $stationNumber <= 20; $stationNumber++) {
                $status = match (true) {
                    $stationNumber === 19 => LabStatus::UnderMaintenance,
                    $stationNumber === 20 => LabStatus::Broken,
                    default => LabStatus::Working,
                };

                $stationRows[] = [
                    'lab_id' => $lab->id,
                    'station_number' => $stationNumber,
                    'device_type' => $definition['device'],
                    'status' => $status->value,
                    'notes' => sprintf('%s-%02d', $definition['prefix'], $stationNumber),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::table('lab_stations')->insert($stationRows);

        $stations = DB::table('lab_stations')
            ->orderBy('lab_id')
            ->orderBy('station_number')
            ->get()
            ->groupBy('lab_id');

        $informaticsLab = $labs->firstWhere('name', 'مخبر الإعلام الآلي 1');
        $classroom = $classrooms->firstWhere('name', '1 م 1');
        $planRows = [];
        $groups = [];

        if ($informaticsLab !== null && $classroom !== null) {
            foreach (self::GROUPS_PER_CLASSROOM as $groupName) {
                $group = $classroom->groups->firstWhere('name', $groupName);

                if ($group === null) {
                    continue;
                }

                $groups[] = $group;
                $planRows[] = [
                    'lab_id' => $informaticsLab->id,
                    'group_id' => $group->id,
                    'name' => $classroom->name.' — '.$groupName,
                    'notes' => 'مخطط جلوس نموذجي لاستغلال محطات المخبر.',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($planRows !== []) {
            LabSeatingPlan::query()->insert($planRows);
        }

        $plans = LabSeatingPlan::query()
            ->where('lab_id', $informaticsLab?->id)
            ->orderBy('id')
            ->get();
        $positions = [
            [0, 0], [0, 1], [0, 2], [0, 3], [0, 4], [0, 5],
            [1, 6], [2, 6], [3, 6], [4, 6], [5, 6],
            [5, 5], [5, 4], [5, 3], [5, 2], [5, 1], [5, 0],
            [4, 6],
        ];
        $informaticsStations = collect($stations->get($informaticsLab?->id, []))
            ->sortBy('station_number')
            ->take(18)
            ->values();
        $planStationRows = [];

        foreach ($plans as $planIndex => $plan) {
            $group = $groups[$planIndex] ?? null;
            $students = $group !== null
                ? ($studentsByGroup[$group->id] ?? [])
                : [];

            foreach ($informaticsStations as $stationIndex => $station) {
                $planStationRows[] = [
                    'seating_plan_id' => $plan->id,
                    'lab_station_id' => $station->id,
                    'student_id' => $stationIndex < 15
                        ? ($students[$stationIndex] ?? null)
                        : null,
                    'position_x' => $positions[$stationIndex][0] ?? 0,
                    'position_y' => $positions[$stationIndex][1] ?? 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($planStationRows !== []) {
            DB::table('lab_seating_plan_stations')->insert($planStationRows);
        }

        return [
            'labs' => $labs->count(),
            'stations' => count($stationRows),
            'plan_stations' => count($planStationRows),
        ];
    }

    /**
     * Seed pedagogical memos, behavior observations, remediation sessions, and
     * teacher meetings.
     *
     * @param  EloquentCollection<int, User>  $teachers
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  EloquentCollection<int, Level>  $levels
     * @param  EloquentCollection<int, Classroom>  $classrooms
     * @param  array<int, list<int>>  $studentsByClassroom
     * @param  array<string, list<array<string, mixed>>>  $resourcesByPair
     * @param  EloquentCollection<int, Schedule>  $schedules
     * @param  array<int, list<int>>  $strugglingByClassroom
     * @return array{memos: int, observations: int, remediations: int, meetings: int}
     */
    private function seedPedagogicalData(
        EloquentCollection $teachers,
        EloquentCollection $subjects,
        EloquentCollection $levels,
        EloquentCollection $classrooms,
        array $studentsByClassroom,
        array $resourcesByPair,
        EloquentCollection $schedules,
        array $strugglingByClassroom,
    ): array {
        $subjectsById = $subjects->keyBy('id');
        $teachersById = $teachers->keyBy('id');
        $timestamp = now();
        $memos = [];
        $memoIndex = 0;

        foreach ($teachers as $teacher) {
            $subject = $subjectsById->get($teacher->primary_subject_id);

            if (! $subject instanceof Subject) {
                continue;
            }

            $level = $levels->values()->get($memoIndex % $levels->count());

            if (! $level instanceof Level) {
                continue;
            }

            $program = AlgerianCurriculumCatalog::program(
                $subject->name,
                (int) $level->grade_number,
            );
            $resource = $this->catalogResource($program, $memoIndex);
            $activities = [];

            foreach ($resource['phases'] as $phaseIndex => $phase) {
                $activities[] = [
                    'name' => $phase['phase_name'],
                    'duration' => [10, 20, 20, 10][$phaseIndex] ?? 10,
                    'strategy' => ['العمل الجماعي', 'الاستقصاء', 'المناقشة', 'التقويم'][$phaseIndex] ?? 'العمل الفوجي',
                    'content' => $phase['content'],
                ];
            }

            $baseMemo = [
                'user_id' => $teacher->id,
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'competence' => $resource['competence'],
                'starting_situation' => $resource['starting_situation'],
                'activities' => json_encode($activities, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'resources' => 'الكتاب المدرسي، السبورة، الوثائق، جهاز العرض.',
                'evaluation' => 'وضعية إدماجية قصيرة حول '.$resource['title'].'.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $memos[] = $baseMemo + [
                'title' => 'مذكرة درس: '.$resource['title'],
                'phase' => $resource['phases'][0]['phase_name'] ?? null,
                'notes' => 'مذكرة نشطة جاهزة للاستعمال في القسم.',
                'is_template' => false,
                'is_archived' => false,
            ];
            $memos[] = $baseMemo + [
                'title' => 'قالب مذكرة: '.$resource['title'],
                'phase' => $resource['phases'][0]['phase_name'] ?? null,
                'notes' => 'قالب قابل للتعديل حسب وتيرة القسم.',
                'is_template' => true,
                'is_archived' => false,
            ];

            if ($teacher->email === self::DEMO_TEACHER_EMAIL) {
                $memos[] = $baseMemo + [
                    'title' => 'مذكرة مؤرشفة — '.$resource['title'],
                    'phase' => $resource['phases'][0]['phase_name'] ?? null,
                    'notes' => 'نسخة محفوظة من السنة الدراسية السابقة.',
                    'is_template' => false,
                    'is_archived' => true,
                ];
            }

            $memoIndex++;
        }

        $this->insertChunked('pedagogical_memos', $memos, 100);

        $schedulesByClassroom = [];

        foreach ($schedules as $schedule) {
            $schedulesByClassroom[$schedule->classroom_id][] = $schedule;
        }

        $observationRows = [];

        foreach ($classrooms as $classroomIndex => $classroom) {
            $roster = $studentsByClassroom[$classroom->id] ?? [];
            $schedule = collect($schedulesByClassroom[$classroom->id] ?? [])
                ->first(fn (Schedule $item): bool => $item->group_id === null);

            if (! $schedule instanceof Schedule || $roster === []) {
                continue;
            }

            $teacher = $teachersById->get($schedule->user_id);
            $subjectName = $subjectsById->get($schedule->subject_id)?->name ?? 'المادة';
            $observedBy = $teacher?->name;

            foreach (array_slice($roster, 0, 3) as $index => $studentId) {
                $observationRows[] = [
                    'student_id' => $studentId,
                    'recorded_by_id' => $schedule->user_id,
                    'schedule_id' => $schedule->id,
                    'category' => 'positive',
                    'notes' => 'مشاركة مميزة وفعالة في درس '.$subjectName.'.',
                    'date' => $timestamp->copy()->subDays(($classroomIndex + $index) % 10 + 1)->toDateString(),
                    'observed_by' => $observedBy,
                    'is_followed_up' => false,
                    'follow_up_notes' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            foreach (array_slice($roster, 7, 2) as $index => $studentId) {
                $resolved = $index === 0;

                $observationRows[] = [
                    'student_id' => $studentId,
                    'recorded_by_id' => $schedule->user_id,
                    'schedule_id' => $schedule->id,
                    'category' => $index === 0 ? 'negative' : 'disciplinary',
                    'notes' => $index === 0
                        ? 'عدم إنجاز الواجب المنزلي وتشتت أثناء الحصة.'
                        : 'مقاطعة متكررة لنشاط القسم.',
                    'date' => $timestamp->copy()->subDays(($classroomIndex * 2 + $index) % 25 + 1)->toDateString(),
                    'observed_by' => $observedBy,
                    'is_followed_up' => $resolved,
                    'follow_up_notes' => $resolved
                        ? 'تم التواصل مع الولي وتنظيم متابعة فردية.'
                        : null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if (isset($roster[22])) {
                $observationRows[] = [
                    'student_id' => $roster[22],
                    'recorded_by_id' => $schedule->user_id,
                    'schedule_id' => $schedule->id,
                    'category' => 'observation',
                    'notes' => 'ملاحظة صفية: يحتاج إلى تحفيز إضافي والمشاركة في العمل الفوجي.',
                    'date' => $timestamp->copy()->subDays(($classroomIndex * 3) % 20 + 1)->toDateString(),
                    'observed_by' => $observedBy,
                    'is_followed_up' => false,
                    'follow_up_notes' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        $this->insertChunked('behavior_observations', $observationRows, 100);

        $remediationRows = [];
        $extraFollowUps = 0;

        foreach ($classrooms as $classroom) {
            $strugglingStudents = $strugglingByClassroom[$classroom->id] ?? [];

            if ($strugglingStudents === []) {
                continue;
            }

            $schedule = collect($schedulesByClassroom[$classroom->id] ?? [])
                ->first(fn (Schedule $item): bool => $item->group_id === null);
            $levelId = $classroom->level_id;
            $pairKey = $schedule !== null
                ? $schedule->subject_id.':'.$levelId
                : null;
            $resource = $pairKey !== null
                ? ($resourcesByPair[$pairKey][0] ?? null)
                : null;

            foreach ($strugglingStudents as $studentId) {
                $followUpIndex = count($remediationRows) + 1;
                $pattern = $followUpIndex % 10;
                $status = match (true) {
                    $pattern < 7 => 'completed',
                    $pattern < 9 => 'ongoing',
                    default => 'planned',
                };
                $attended = $status !== 'planned';

                $remediationRows[] = [
                    'student_id' => $studentId,
                    'recorded_by_id' => $schedule?->user_id,
                    'schedule_id' => $schedule?->id,
                    'reason' => 'صعوبات في '.($resource['title'] ?? 'المكتسبات الأساسية').'.',
                    'activities' => 'تشخيص الفجوات، أنشطة دعم فردية، تمارين تطبيقية، تقويم قصير.',
                    'session_date' => $timestamp->copy()->subDays(($followUpIndex * 3) % 21 + 1)->toDateString(),
                    'attended' => $attended,
                    'follow_up' => match ($status) {
                        'completed' => 'تحسن ملحوظ بعد جلستي الدعم.',
                        'ongoing' => 'يواصل المتابعة الأسبوعية مع الأستاذ.',
                        default => null,
                    },
                    'status' => $status,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                if ($extraFollowUps < 30 && $status === 'completed' && $resource !== null) {
                    $extraFollowUps++;
                    $remediationRows[] = [
                        'student_id' => $studentId,
                        'recorded_by_id' => $schedule?->user_id,
                        'schedule_id' => $schedule?->id,
                        'reason' => 'متابعة إضافية — '.$resource['title'].'.',
                        'activities' => 'أنشطة تعزيزية، قراءة موجهة، تمارين تفاعلية، تقويم ختامي.',
                        'session_date' => $timestamp->copy()->subDays(($extraFollowUps * 2) % 18 + 1)->toDateString(),
                        'attended' => true,
                        'follow_up' => 'تثبيت المكتسبات مع متابعة أسبوعية.',
                        'status' => 'ongoing',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        $this->insertChunked('pedagogical_remediations', $remediationRows, 100);

        $meetings = [];

        foreach ($teachers as $index => $teacher) {
            $subject = $subjectsById->get($teacher->primary_subject_id);
            $meetings[] = [
                'user_id' => $teacher->id,
                'title' => 'ندوة تكوينية مع مفتش المادة',
                'meeting_type' => 'inspector_seminar',
                'date' => $timestamp->copy()->subDays(($index * 3) % 55 + 1)->toDateString(),
                'location' => 'قاعة الاجتماعات',
                'supervisor_name' => 'مفتش مادة '.($subject?->name ?? 'التعليم المتوسط'),
                'agenda' => 'تحليل التدرج السنوي، توحيد معايير التقويم، وتبادل الممارسات الناجحة.',
                'recommendations' => 'اعتماد الوضعيات الإدماجية وتوثيق المعالجة البيداغوجية بانتظام.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
            $meetings[] = [
                'user_id' => $teacher->id,
                'title' => 'اجتماع تنسيقي فصلي',
                'meeting_type' => 'coordination_meeting',
                'date' => $timestamp->copy()->subDays(($index * 5) % 55 + 2)->toDateString(),
                'location' => 'مكتب المدير',
                'supervisor_name' => self::PRINCIPAL_NAME,
                'agenda' => 'تنسيق برامج الدعم، متابعة الحضور، وتقييم نتائج الفروض المحروسة.',
                'recommendations' => 'تعزيز التواصل مع الأولياء وتفعيل مجالس الأقسام.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $demoTeacher = $teachers->firstWhere('email', self::DEMO_TEACHER_EMAIL);

        if ($demoTeacher instanceof User) {
            $meetings[] = [
                'user_id' => $demoTeacher->id,
                'title' => 'مجلس التعليم',
                'meeting_type' => 'pedagogical_council',
                'date' => $timestamp->copy()->subDays(6)->toDateString(),
                'location' => 'مدرج المؤسسة',
                'supervisor_name' => self::PRINCIPAL_NAME,
                'agenda' => 'مناقشة النتائج الفصلية وخطط الدعم والمرافقة.',
                'recommendations' => 'توسيع أنشطة المعالجة وتنظيم حصص دعم أسبوعية.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
            $meetings[] = [
                'user_id' => $demoTeacher->id,
                'title' => 'مجلس القسم — 1 م 1',
                'meeting_type' => 'class_council',
                'date' => $timestamp->copy()->subDays(3)->toDateString(),
                'location' => 'قاعة الأساتذة',
                'supervisor_name' => self::COUNSELOR_NAME,
                'agenda' => 'قراءة نتائج التلاميذ، متابعة الغيابات، واقتراح التدابير التربوية.',
                'recommendations' => 'متابعة التلاميذ المتعثرين وإشراك الأولياء في خطة الدعم.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->insertChunked('pedagogical_meetings', $meetings, 100);

        return [
            'memos' => count($memos),
            'observations' => count($observationRows),
            'remediations' => count($remediationRows),
            'meetings' => count($meetings),
        ];
    }

    /**
     * Flatten a catalog programme into a list of resources and pick one.
     *
     * @param  list<array<string, mixed>>  $program
     * @return array<string, mixed>
     */
    private function catalogResource(array $program, int $offset): array
    {
        $resources = [];

        foreach ($program as $domain) {
            foreach ($domain['sequences'] as $sequence) {
                foreach ($sequence['resources'] as $resource) {
                    $resources[] = $resource;
                }
            }
        }

        if ($resources === []) {
            throw new \RuntimeException('The curriculum programme contains no resources.');
        }

        return $resources[$offset % count($resources)];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertChunked(string $table, array $rows, int $chunkSize): void
    {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->insert($chunk);
        }
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
