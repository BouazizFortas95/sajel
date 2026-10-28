# Sajel (سجِّل) — Project Status

> Last updated: 2026-10-28
> Verified by `php artisan test` (**373 passed**, 20,469 assertions, **0 failures**),
> `npm run build`, `php artisan sajel:health-check`, and `vendor/bin/pint`.

## 1. Overview

**Sajel** (سجِّل — "record/register") is a teacher-oriented web platform for building and managing
weekly schedules, daily lesson logs (the digital equivalent of the class register / دفتر النصوص),
a reusable pedagogical memo bank (المذكرات البيداغوجية), assessment gradebooks (دفتر العلامات), and
official curriculum tracking (الميادين، المقاطع التعلمية، الموارد المعرفية). Teachers prepare lessons
per schedule slot, confirm them so they become the official register entry, record assessment scores
for the same roster, build or print reusable memos, and map completed lessons to the annual programme
to see each classroom's progress automatically.

The UI and domain language are Arabic (RTL), with English code identifiers. The landing page and
panel support Arabic, English, and French via `mcamara/laravel-localization`.

## 2. Tech Stack

| Layer      | Choice                                   | Notes                                   |
|------------|------------------------------------------|-----------------------------------------|
| Framework  | Laravel `^12.0`                          | PHP `^8.2`                              |
| Admin UI   | Filament `^3.2` (bundles Livewire)       | Single panel, Amber brand color, Cairo font |
| Database   | SQLite                                   | `database/database.sqlite` present      |
| Frontend   | Vite `^7`, Tailwind CSS `^4`, Axios      | Filament theme in `resources/css/filament.css` |
| PDF        | `spatie/browsershot` `^5.4`, Puppeteer  | Headless Chrome; Arabic shaping + Tailwind-preserving PDF |
| i18n       | `mcamara/laravel-localization` `^2.4`    | `ar`, `en`, `fr` locales                |
| Timezone   | `Africa/Algiers`                         | `APP_TIMEZONE`, used for weekly dates   |
| Testing    | PHPUnit `^11.5`                          | **373 tests, all green (20,469 assertions in the latest run)** |
| Tooling    | Laravel Pint, Pail, Sail, Tinker         | Dev dependencies installed              |

## 4. Sajel Pulse — Motivation Engine (نبض التميز والتحفيز التربوي)
- `StudentMotivationService`: zero-click badges (`attendance_streak`, `comeback_champion`, `academic_excellence`, `positive_role_model`), pulse score, classroom synergy meter.
- `DataShowMode` synergy bar + `awardQuickKudos()` (positive `BehaviorObservation`, classroom synergy increment, on-screen toast).
- `MotivationPulseHub` Filament page (`/ar/auth/motivation-pulse`) with spotlight podium cards.
- A4 RTL certificate `print.student-certificate` + authorization (`StudentPolicy::view`).
- Badges injected into `ParentPortalService` and `StudentReportCardService`.
- Translations added to `ar.json`, `en.json`, `fr.json`; `tests/Feature/MotivationPulseTest.php` created.
- Health-check PASS; Pint PASS; full suite 373 pass / 0 failures.
- Section 18 (Phase 8 — Institutional Backup & CI) added.

## 3. Domain Model

Hierarchy: `EducationPhase → Level → Classroom → Group`, with `Subject` and `Schedule` referencing them,
and `DailyLog` attached to each `Schedule`. Pedagogical memos attach to a teacher, subject, and level
independently of a schedule so they can be reused across classes. The official curriculum is a separate
subject/level tree (`CurriculumDomain → CurriculumSequence → CurriculumResource`), and a daily log may
optionally point at one knowledge resource.

```
EducationPhase (name, code, phase_abbreviation, settings[])
  └─ Level (name, grade_number)
       └─ Classroom (name auto-generated, class_number, academic_year)
            └─ Group (name)
                 └─ Student (first_name, last_name, registration_number?,
                            date_of_birth?)

Subject (name, color)
  └─ CurriculumDomain (level, name, code?, description?, sort_order)
       └─ CurriculumSequence (name, code?, description?, sort_order)
            └─ CurriculumResource (name, code?, description?, sort_order)

User (teacher: primary/secondary subject, weekly hours limit)
  ├─ Schedule (user, classroom?, group?, subject, day_of_week, start/end time)
  │    ├─ DailyLog (date, title, content_data[], actual_content, homework,
  │    │             observations, status, curriculum_resource_id?)
  │    │             — unique per (schedule, date)
  │    │    └─ Attendance (student, status, remarks?) — unique per (daily_log, student)
  │    └─ Assessment (title, type, date, max_score)
  │         └─ Grade (student, score?, remarks?) — unique per (assessment, student)
  └─ PedagogicalMemo (subject, level, title, phase, competence,
                      starting_situation, activities[], resources,
                      evaluation, notes, is_template, is_archived)
```

### Models (`app/Models`)

| Model            | Purpose                                                            |
|------------------|--------------------------------------------------------------------|
| `User`           | Teacher/admin; implements `FilamentUser`; primary/secondary subjects, weekly workload limit, schedules & daily logs |
| `EducationPhase` | Educational phase (e.g. Middle School) with abbreviation and JSON `settings` |
| `Level`          | Grade/year within a phase, with a grade number used in classroom names |
| `Classroom`      | Class section within a level; `name` is auto-generated on save |
| `Group`          | Sub-division of a classroom; has many students                    |
| `Student`        | Enrolled student (unique nullable registration number) in a group |
| `Subject`        | Subject with a display color                                       |
| `CurriculumDomain` | Annual-plan domain (ميدان) for a subject and level; has many sequences and resources |
| `CurriculumSequence` | Ordered learning sequence (مقطع تعلمي) inside a curriculum domain |
| `CurriculumResource` | Ordered knowledge resource (مورد معرفي) inside a sequence; may be mapped by many daily logs |
| `Schedule`       | A teacher's weekly recurring lesson slot; `label()` for display    |
| `DailyLog`       | Per-slot daily log; dynamic `content_data` blocks; optional curriculum-resource mapping; register summary generation; has many attendances |
| `Attendance`     | Per-student status (`present`/`absent`/`late`/`excused`) and remarks for one daily log |
| `Assessment`     | Quiz/midterm/exam/continuous assessment attached to a schedule; materializes grade rows |
| `Grade`          | Per-student score and remarks for one assessment; unique per `(assessment, student)` |
| `PedagogicalMemo` | Reusable teacher-owned memo linked to subject, level, and learning sequence; JSON activities, template/archive flags, duplication, A4 print/PDF |

### Enums (`app/Enums`)

| Enum               | Cases                                                                    |
|--------------------|--------------------------------------------------------------------------|
| `DayOfWeek`        | Sunday…Saturday (Arabic labels, per-day colors)                          |
| `DailyLogStatus`   | `Planned` (مخطط), `Completed` (منجز), `Cancelled` (ملغى)                |
| `AttendanceStatus` | `Present` (حاضر), `Absent` (غائب), `Late` (متأخر), `Excused` (معفى)   |
| `AssessmentType`   | `Quiz` (اختبار قصير), `Midterm` (فرض الفصل), `Exam` (امتحان), `ContinuousAssessment` (تقويم مستمر) |
| `LessonPhaseType`  | Warmup, Discovery, Institutionalization, Application, Evaluation (مراحل الدرس) |
| `Role`             | `Admin` (مدير), `Teacher` (أستاذ) with labels/colors/options             |

### Key behaviors

- `DailyLog::content_data` is a JSON array of typed blocks:
  `middle_school_lesson` (lesson title, competence, pedagogical situation, phases),
  `lesson_structure` (competence + repeatable named phases with rich content),
  `general_lesson` (title, objectives, content), and `warmup` (description, duration).
- `DailyLog::generateRegisterSummary()` renders those blocks into a readable Arabic register entry.
- The AI lesson planner turns a lesson title plus its schedule into a complete `lesson_structure`
  block: the "توليد التحضير بالذكاء الاصطناعي" suffix action on the title field resolves the
  subject and level from the selected schedule, calls `AiLessonPlannerService` (Gemini by default,
  OpenAI opt-in via `AI_PROVIDER`), and injects the UUID-keyed block into the `content_data`
  Builder through `$set()` — instantly, without a page reload. It confirms before overwriting
  existing content, warns when the title/schedule are missing, scopes schedules to the current
  teacher, and reports provider errors as danger notifications.
- `DailyLog::markAsCompleted(?string $actualContent = null)` flips status to `Completed` and promotes
  the supplied (or generated) content to `actual_content` (official register entry).
- `DailyLog::isPlanned()` gates the "confirm completion" action.
- `Group::students()` and `Student::group()` model the roster; deleting a group cascades to its
  students. `Student::full_name` combines first and last names for tables and search.
- Attendance is managed from the `DailyLog` edit page through `AttendancesRelationManager`. Opening
  the manager idempotently materializes a `present` row for every roster student: the schedule's
  group when set, otherwise all groups in the schedule's classroom. Existing statuses and remarks are
  never reset, and `insertOrIgnore` plus the unique `(daily_log_id, student_id)` index make the sync
  safe under concurrent opens. Teachers then use an inline status select, inline remarks field, a
  one-click present/absent row toggle, or the "تعيين الجميع حاضراً" header action.
- The gradebook is managed from the `Assessment` edit page through `GradesRelationManager`. Opening
  an assessment idempotently materializes one `grade` row with `score = null` for every roster
  student, using the same schedule resolution as attendance. Existing scores and remarks are never
  overwritten, and `insertOrIgnore` plus the unique `(assessment_id, student_id)` index make the sync
  safe under concurrent opens. Scores are edited inline in a numeric/decimal `TextInputColumn` and
  validated dynamically against the assessment's `max_score`; remarks have their own inline field.
  The schedule is locked after creation so materialized grades cannot be orphaned by moving the
  assessment to a different roster.
- The pedagogical memo bank is managed by `PedagogicalMemoResource`. Memos belong to a teacher and
  link to a subject, level, and optional learning sequence (`phase`); they store the target
  competence, starting situation, repeatable activities, resources, evaluation, and notes. Teachers
  can mark a memo as a reusable template, duplicate it into their own bank (`duplicateFor()` adds a
  `— نسخة` suffix and resets the template/archive flags), archive or restore it without deleting it,
  and open a standalone A4 print sheet or server-side PDF. Admins can see every memo and assign one
  to a teacher on create.
- Official curriculum tracking is managed by three admin-only resources:
  `CurriculumDomainResource` (subject + level), `CurriculumSequenceResource` (domain), and
  `CurriculumKnowledgeResource` (sequence). `DailyLogResource` offers a nullable knowledge-resource
  selector scoped to the schedule's subject and classroom/group level; forged cross-subject or
  cross-level values are rejected. `CurriculumProgressService` derives progress on demand from
  distinct completed daily logs (not mutable counters), so multiple completed lessons for the same
  resource count once. A sequence is `completed` only when every one of its resources has at least
  one completed lesson; otherwise it is `in_progress` or `not_started`. Teachers see only their own
  classroom/subject pairs in the overview, but the percentages aggregate the whole class; admins see
  every pair. The custom `CurriculumProgress` page filters by subject and classroom, exposes search,
  progress-status, sort, and per-page tools bound to the URL, shows scope KPIs and at-risk badges,
  paginates the summary with Filament's no-scroll pagination component, exports the filtered rows to
  UTF-8 CSV, and breaks progress down by domain, sequence, and resource.
- `Student::scopeForSchedule()` is the single roster resolver used by attendance and gradebook syncs
  and their relation managers; a schedule with neither a group nor a classroom targets no students
  rather than the whole school.
- Native Filament CSV import (`StudentImporter`) links each row to a group by ID, exact display
  label, or a unique group name, and keeps blank registration numbers as `null`.
- Native Filament CSV/XLSX export (`DailyLogExporter`) exports completed logs only, with date,
  classroom, subject, lesson title, actual content (HTML flattened to text), and homework. While the
  queued export is running, the daily-log list page shows a live progress bar that polls the current
  user's `exports` row every two seconds and disappears when Filament completes the export. The
  widget keeps the authenticated Livewire hydrate check stable while the export finishes, so its final
  progress poll does not abort with a 403 when the active export disappears. Completion notifications
  (with direct CSV/XLSX download links) are stored in the database and shown in the panel notification
  bell. `ExportPolicy` lets the export owner or an admin download the generated files.
- The official register is available as a direct server-side PDF from the daily-log bulk action
  "تحميل دفتر النصوص (PDF)". `DailyLogRegisterService` re-queries the selected records completed-only
  and scopes teachers to their own schedules, inlines the compiled Vite/Tailwind stylesheet, and
  renders the existing RTL Blade view through `spatie/browsershot` (headless Chrome, A4, backgrounds,
  12 mm margins). Long register rows are allowed to fragment across PDF pages, so lesson content
  continues on the next page instead of being pushed to a blank first page or clipped. The previous
  browser-print route remains available at
  `/print/daily-logs?ids[]=...`; it uses the same query/display service and opens the print dialog.
- Query scopes: `Schedule::forTeacher/onDay`, `DailyLog::forDate/forTeacher`, `EducationPhase::search`.
- Teacher specialization: each teacher has one primary subject and an optional secondary subject. The
  schedule form exposes only those subjects (defaulting to the primary), validates the submitted subject
  server-side, and lets admins choose only teacher accounts before selecting a subject.
- Schedule overlap guard: the `start_time` and `end_time` fields share a closure rule that queries the
  selected teacher's slots for the selected day (excluding the record being edited). Slots are compared
  as half-open intervals (`existingStart < newEnd && existingEnd > newStart`), so identical and
  partially overlapping slots are rejected while back-to-back slots (09:00–10:00 after 08:00–09:00)
  remain valid. The comparison runs in PHP against the teacher's few daily rows, which keeps it correct
  whether a stored time is `H:i` or `H:i:s`. Inverted ranges are still reported by the separate
  ordering rule on `end_time`.
- Weekly workload: `TeacherTimetable` sums the durations of the teacher's Sunday–Thursday lessons and
  passes `$totalHours` and `$weeklyHoursLimit` to the view. The header badge is green while the workload
  is within the limit and turns red with a warning icon only when the total exceeds the limit (equality
  is not treated as an overload).
- Subject-coloured week cards: the week grid maps each subject's `color` (`primary`, `danger`,
  `success`, `warning`, `info`, `gray`) through a `match` in the Blade view to complete Tailwind
  utility strings (card border/hover/ring, title hover, arrow and pin colours), falling back to
  `primary` for a missing or unknown colour. Empty/invalid values never leak into the markup.
- `DayOfWeek::dateInCurrentWeek()` maps a weekday to its date in the current Sunday-first week,
  so weekly cards always act on the correct day's log.
- `Classroom` auto-generates its `name` in the `saving` event as
  `{level.grade_number} {level.educationPhase.phase_abbreviation} {class_number}`
  (example: `1 م 1`). The hook leaves the existing name untouched when the level, phase
  abbreviation, grade number, or class number is missing, so legacy/manual names keep working.

## 4. Database

All 43 migrations have run (SQLite):

- Framework tables: `users` (with sessions), `cache`, `jobs`, `notifications`.
- Domain tables: `education_phases`, `levels`, `classrooms`, `groups`, `students`, `subjects`,
  `schedules`, `daily_logs`, `attendances`, `assessments`, `grades`, `pedagogical_memos`,
  `curriculum_domains`, `curriculum_sequences`, `curriculum_resources`, `school_settings`.
- Filament action tables: `imports`, `exports`, `failed_import_rows` (published from
  `filament-actions` for native CSV import/export).
- `2026_10_20_000001_add_role_to_users_table` adds the `role` column (defaults to `teacher`).
- `2026_10_21_234923_add_subject_specialization_to_users_table` renames a legacy `subject_id` when
  present (otherwise creates it) to `primary_subject_id`, adds nullable `secondary_subject_id` and
  `weekly_hours_limit` (default `22`), and backfills existing teachers from their first two distinct
  scheduled subjects.
- `2026_10_22_004047_add_grade_number_to_levels_table` adds nullable `grade_number` to `levels`.
- `2026_10_22_004051_add_phase_abbreviation_to_education_phases_table` adds nullable
  `phase_abbreviation` to `education_phases`.
- `2026_10_22_004054_add_class_number_to_classrooms_table` adds nullable `class_number` to
  `classrooms`. These columns are nullable so existing rows keep their manual names until edited.
- `2026_10_23_000001_create_pedagogical_memos_table` creates the memo bank with teacher, subject, and
  level foreign keys, title, learning sequence, competence, starting situation, JSON activities,
  resources, evaluation, notes, template/archive flags, and `(user_id, is_archived)` /
  `(subject_id, level_id)` indexes.
- `2026_10_23_000002_create_curriculum_domains_table`,
  `2026_10_23_000003_create_curriculum_sequences_table`, and
  `2026_10_23_000004_create_curriculum_resources_table` create the ordered curriculum catalog:
  domains belong to a subject and level, sequences to a domain, and resources to a sequence, each
  with a name, optional code/description, and a sort order.
- `2026_10_23_000005_add_curriculum_resource_id_to_daily_logs_table` adds the nullable
  `curriculum_resource_id` foreign key with `nullOnDelete`.
- Notable constraints: `daily_logs` has a unique `(schedule_id, date)` pair and a `date` index;
  `schedules` is indexed on `(user_id, day_of_week)`; `students.registration_number` is uniquely
  indexed but nullable; `students.group_id` cascades on group deletion; `attendances` has a unique
  `(daily_log_id, student_id)` pair and both foreign keys cascade on delete; `assessments.schedule_id`
  cascades on schedule deletion; `grades` has a unique `(assessment_id, student_id)` pair, and both
  of its foreign keys cascade on delete; `pedagogical_memos` cascades on teacher/subject/level
  deletion. `curriculum_domains` is unique per `(subject_id, level_id, name)`,
  `curriculum_sequences` per `(curriculum_domain_id, name)`, and `curriculum_resources` per
  `(curriculum_sequence_id, name)`; their parent foreign keys cascade, while
  `daily_logs.curriculum_resource_id` is set to `null` on resource deletion.

Factories exist for all 15 domain models plus `User`.
`Database\Seeders\SajelSeeder` remains a small, non-destructive demo: a teacher
(`teacher@sajel.test` / `password`), an admin (`admin@sajel.test` / `password`), a middle-school
phase, 2 levels, 2 classrooms, 2 groups, 4 subjects, a weekly schedule, and a planned daily log.
The demo teacher is assigned Mathematics as primary and Physics as secondary, with the standard
22-hour weekly limit. It is no longer the default root seeder; run it explicitly when only the
small demo is wanted.

`Database\Seeders\MiddleSchoolDatabaseSeeder` is now the root dataset behind
`php artisan migrate:fresh --seed` (via `DatabaseSeeder`). It is deliberately destructive: every
run wipes grades, attendance, daily logs, curriculum resources, curriculum sequences, curriculum
domains, assessments, schedules, students, groups, classrooms, levels, phases, subjects,
import/export rows, sessions, and users before rebuilding. The dataset contains the school identity
`متوسطة الشهيد العربي بن مهيدي` under `مديرية التربية لولاية الوادي` (`بلدية أم الطيور`, academic
year `2026-2027`), an official holiday calendar, 13 subjects (each with a distinct hex colour), the
`CEM` phase (abbreviation `م`), 4 levels with grade numbers, 20 classrooms (`1 م 1` … `4 م 5`),
40 groups (`الفوج أ` / `الفوج ب`), 600 students (15 per group, ages 11–15 by grade), one default
admin (`admin@sajel.test` / `password`), and 30 teachers. The demo teacher
(`teacher@sajel.test` / `password`) teaches Mathematics as primary and Physics as secondary with a
22-hour weekly limit; every teacher gets a primary subject (all 13 subjects are covered) and exactly
6 teachers (20%) also get a different secondary subject. Students are built through `StudentFactory`
and inserted in six 100-row chunks; teachers use `UserFactory` `teacher()`/`admin()` states.

The same seed builds the official Algerian `التعليم المتوسط — الجيل الثاني` curriculum for every
subject/level pair from `Database\Seeders\Support\AlgerianCurriculumCatalog`: 2 domains, 4 sequences,
and 8 knowledge resources (104 domains, 208 sequences, 416 resources overall). Each resource carries
a competence, starting situation, the four lesson phases (وضعية الانطلاق، بناء التعلمات، الترسيخ
والممارسة، التقويم والاستثمار), register-ready HTML, and homework.

A typical run also seeds two academic weeks of class-register and gradebook history: the previous
Sunday through the current Thursday. Every timetable slot gets two `DailyLog` rows whose
`content_data` is a `lesson_structure` block with subject-specific Arabic content and the four named
phases; a log is `completed` once its date is in the past (or its slot has ended today), otherwise
`planned`. Completed logs receive an HTML `actual_content` summary, and roughly 65% carry an
administrative visa; a handful are taught by a substitute teacher. Every log is attached to a
rotating knowledge resource from its schedule's subject and classroom/group level. Two sequential
assessments (quiz then midterm) are seeded for Arabic, Mathematics, and French in every classroom
(120 assessments / 3,600 grades), using balanced learner profiles: excellence 16–19.5, comeback
9.5 → 13.5, steady 10.5–15.5, and struggling 5.5–9.0. Attendance follows a realistic 90% present /
5% absent / 3% late / 2% excused distribution with Arabic remarks, multi-day present streaks, and
repeat absentees. Three labs with 20 stations each, 60 stations, and two seating plans for `1 م 1`
complete the dataset alongside pedagogical memos, behavior observations, remediations, and meetings;
a typical run writes 1,200 daily logs, 22,725 attendance rows, and stays below its 400-query budget
by preloading core maps and bulk-inserting in chunks.

The deterministic timetable generator produces 20 one-hour lessons per teacher (600 schedules) on
Sunday–Thursday, using the `08:00–12:00` / `14:00–17:00` grid. Each classroom receives 30 lessons
covering all 13 subjects; Physics, Science, and Informatics lab sessions are group-scoped, with each
group attending one session per subject. Teacher/slot and classroom/slot busy maps guarantee no
double-booking, and the seeder validates the resulting grid before inserting.

`StudentFactory` generates given names through Faker's `ar_SA` provider and surnames from a curated
Algerian family-name pool, with `2026-####` registration numbers and 11–15-year-old birth dates.

## 5. Admin Panel (Filament)

- Panel ID/path: `auth` at `/{locale}/auth` (Arabic, English, and French; registered in
  `bootstrap/providers.php` via `App\Providers\Filament\AuthPanelProvider`).
- The panel locale middleware (`App\Http\Middleware\SetFilamentLocale`) validates the `{locale}`
  segment, sets Laravel's locale before rendering, and persists the choice in the session, so
  Filament's native layout direction flips to RTL for Arabic and survives later requests.
- Locale persistence: `App\Http\Middleware\SetLocale` is appended to the `web` group and resolves the
  locale as **URL → session → `config('app.locale')`**, then applies it and calls
  `URL::defaults(['locale' => ...])`. Livewire's `/livewire/update` endpoint (which is in the `web`
  group, not the panel stack) is where Filament computes post-save redirect URLs; without this default
  those routes silently generated `/en/...` from the app default, which is why Arabic reverted to
  English after saving. Fresh visitors still get `APP_LOCALE=en` and `/en/auth/...` links; the chosen
  locale is sticky for that session until the user switches again.
- A locale dropdown (`resources/views/filament/partials/language-switcher.blade.php`) is injected via
  `PanelsRenderHook::TOPBAR_END`, rendering inside Filament's existing top bar. The sidebar is
  collapsible on desktop.
- Brand: **سجِّل**, primary color Amber, Google font **Cairo**. Custom theme CSS is registered through
  `PanelsRenderHook::STYLES_AFTER` (`resources/views/filament/theme.blade.php` →
  `resources/css/filament.css`). Login, registration, and password reset enabled.
- The theme exposes Filament's runtime palette to Tailwind v4: an `@theme inline` block maps
  `primary`, `danger`, `success`, `warning`, and `info` to `rgba(var(--{colour}-{shade}), 1)`, so
  utilities such as `border-s-danger-500` or `dark:bg-success-400/10` compile and follow the panel
  colours (including the Amber primary). Shades `50, 200–800` are defined, which also fixes the
  workload/status badge classes that previously had no generated CSS.
- Access control: users carry a `role` (`admin` | `teacher`, see `App\Enums\Role`); `User::canAccessPanel()`
  grants panel entry to any user with a role. Fine-grained abilities are enforced by model policies,
  each with an admin bypass via `before()`:
  - `SchedulePolicy` and `DailyLogPolicy`: teachers are scoped to their own records.
  - `AssessmentPolicy`: teachers can list/create assessments but only view, edit, or delete assessments
    attached to their own schedules.
  - `PedagogicalMemoPolicy`: teachers can list/create memos and view, edit, delete, duplicate, archive,
    print, or download only their own; admins bypass and can assign memos to teachers.
  - `CurriculumDomainPolicy`, `CurriculumSequencePolicy`, and `CurriculumResourcePolicy`: the
    curriculum catalog is admin-only. Teachers map lessons through daily logs and view progress.
  - `StudentPolicy`: the master list and all mutations are admin-only. A teacher may view an individual
    student only when scheduled for that student's group, or for the whole classroom when the schedule
    has no group filter.
  - `GradePolicy` / `AttendancePolicy`: teachers can view or update rows only when they own the parent
    `Assessment` / `DailyLog`; manual create/delete is disabled (rows are materialized idempotently).
  - `UserPolicy`: admin-only user management.
  - `ExportPolicy`: the owner of an export or an admin may download its generated CSV/XLSX files.
  - `SubjectPolicy`, `ClassroomPolicy`, `LevelPolicy`, `GroupPolicy`, `EducationPhasePolicy`: the
    core academic structure catalogs are strictly admin-only; teachers are denied every ability.

  Teachers see only their own schedules, daily logs, assessments, and pedagogical memos (query scoping
  in `ScheduleResource`, `DailyLogResource`, `AssessmentResource`, and `PedagogicalMemoResource`);
  `StudentResource` is hidden from their navigation entirely. The core academic structure resources
  (`SubjectResource`, `ClassroomResource`, `LevelResource`, `GroupResource`, `EducationPhaseResource`)
  are also hidden from teacher navigation via `shouldRegisterNavigation()` and reject direct access
  with a 403.
  Relation managers additionally verify parent-record ownership, and inline
  grade/attendance columns are disabled unless the policy allows `update` on that row.

### Resources (`app/Filament/Resources`) — each with List / Create / Edit pages

| Resource                | Manages                                |
|-------------------------|----------------------------------------|
| `DailyLogResource`      | Daily logs / lesson preparations (the richest resource: `Builder` content data, `Tabs` form, "تأكيد الإنجاز" action, completion-gated `actual_content`/`homework`, generated register content, CSV/XLSX export, completed-only bulk register PDF download, the AI lesson-plan generator on the title field, and the auto-populated `AttendancesRelationManager`) |
| `AssessmentResource`    | Assessments and the lazy, auto-populated `GradesRelationManager` (inline decimal scores and remarks, dynamic max-score validation, teacher-scoped schedule choices) |
| `PedagogicalMemoResource` | Reusable memo bank (subject/level/sequence links, template and archive filters, duplicate/archive/restore actions, A4 print and server-side PDF) |
| `CurriculumDomainResource` | Annual-plan domains per subject and level (ordered names, optional code/description, sequences/resources counts) |
| `CurriculumSequenceResource` | Learning sequences inside a domain (ordered, optionally coded, with resource counts) |
| `CurriculumKnowledgeResource` | Knowledge resources inside a sequence (custom route slug `curriculum-knowledge-resources`) |
| `ScheduleResource`      | Weekly schedules (cascading classroom → group selects; teacher/admin subject choices restricted to the teacher's primary/secondary assignments) |
| `SubjectResource`       | Subjects and colors                    |
| `ClassroomResource`     | Classrooms / academic years (auto-generated `{grade} {abbr} {number}` name; class number form field) |
| `LevelResource`         | Levels (name + numeric grade number)   |
| `GroupResource`         | Groups                                 |
| `StudentResource`       | Students, group assignment, group filter, CSV import |
| `EducationPhaseResource`| Education phases, abbreviations and settings |
| `UserResource`          | Users, roles, teacher primary/secondary subjects, and weekly workload limit (admin-only via `UserPolicy`) |

The six core academic catalog resources are admin-only (hidden from teacher navigation and fully
denied by policy): `SubjectResource`, `ClassroomResource`, `LevelResource`, `GroupResource`,
`EducationPhaseResource`, and `UserResource`. The three curriculum catalog resources
(`CurriculumDomainResource`, `CurriculumSequenceResource`, and `CurriculumKnowledgeResource`) are
admin-only as well; teachers only map lessons to them and view progress.

`PedagogicalMemoResource` lives in the `التخطيط والمناهج` navigation group. Its list page overrides
`getTitle()` so the sidebar, breadcrumb, and page title stay identical across Arabic, English, and
French. Teachers see their own memos only; admins see every memo and can assign a new memo to a
teacher. Print, PDF, duplicate, archive, and restore actions are available from both the list and the
edit page.

`CurriculumProgress` (`/{locale}/auth/curriculum-progress`) is a custom Filament page for all
authenticated users. Its URL-bound toolbar filters by subject and classroom and adds search by
subject/class/level, progress status (`all`, `completed`, `in_progress`, `not_started`, `at_risk`),
sorting (classroom, subject, percentage ascending/descending, completed resources), and a per-page
selector (10/25/50/100). The page shows scope KPIs (pairs, average progress, completed resources,
at-risk pairs), a class summary table with status badges, at-risk markers, progress bars, and an
explicit details button, and expands into a domain → sequence → resource breakdown with completion
badges. Teachers start on their first scheduled class and only see subject/classroom pairs they
actually teach; admins see every pair. Counts are class-wide even for teachers (so co-taught classes
show the full programme progress), and duplicate logs for the same resource are counted once. The
filtered rows export to UTF-8 CSV, and the summary uses Filament's pagination component, which does
not run Livewire's `scrollIntoView()` on page changes, so the browser keeps its scroll position.

`StudentResource` includes a native Filament CSV import action. Expected headers are
`first_name`, `last_name`, `registration_number`, `date_of_birth`, and `group`. The `group` value
may be a group ID, its exact `display_name()` label, or a group name when that name is unique.
Blank registration numbers and birth dates are stored as `null`. Imports run on the queue
(`QUEUE_CONNECTION=database`), so a queue worker must be running; `composer dev` starts one.

### Pages & Widgets

- Custom `Dashboard` page (`app/Filament/Pages/Dashboard.php`) using a custom Blade view
  (`resources/views/filament/pages/dashboard.blade.php`) with `@livewire('dashboard-stats')`.
  It renders a single full-width column; `getWidgets()` returns `UnloggedSessionsWidget::class`
  followed by `QuickLinks::class` (the `AccountWidget` is deliberately not shown on the dashboard).
- `DashboardStats` Livewire component (`app/Livewire/DashboardStats.php`) + Blade view
  (`resources/views/livewire/dashboard-stats.blade.php`): dark-mode-safe stat cards and
  "Smart log" tabs (Preparation / Completion).
- `MySchedule` page (`app/Filament/Pages/MySchedule.php`) at `/{locale}/auth/my-schedule`
  (teachers only), with two views toggled by `?view=day|week`:
  - **Day view**: `TeacherInteractiveSchedule` — stat cards, day tabs, search, grid/list mode,
    expandable cards, complete/cancel actions, and "فتح السجل"/"إنشاء سجل".
  - **Week view**: `TeacherTimetable` — a fixed Sunday–Thursday grid with seven one-hour slots
    (08:00–12:00 and 14:00–17:00), a lunch-break row, and explicit dashed placeholders for empty
    slots. All slot rows live in a single CSS grid; a lesson lasting several hours is one card with
    an explicit `grid-row / span` (computed from the schedule's start/end time), and the rows it
    covers render no cell at all so the remaining days stay aligned. Spans stop at the lunch break
    and never paint over the period headings. The header includes a workload badge
    ("النصاب الزمني: total / limit ساعة") that turns red with a warning icon when the scheduled total
    exceeds the teacher's weekly limit. Each populated card passes its column's current-week date to
    `openLog()`, which opens the existing log (edit) or a prefilled create page (`schedule_id` +
    `date` query parameters).
  Both views resolve weekday dates in the `Africa/Algiers` timezone and open the existing log
  or a prefilled create page.
- `CurriculumProgress` page (`app/Filament/Pages/CurriculumProgress.php`) at
  `/{locale}/auth/curriculum-progress`: subject/classroom filters, search/status/sort/per-page tools,
  teacher-scoped default, KPI cards, a domain → sequence → resource breakdown, and a paginated
  whole-school or teacher-scoped overview table with CSV export. Its computed properties call
  `CurriculumProgressService`; the Blade view is
  `resources/views/filament/pages/curriculum-progress.blade.php`.
- `QuickLinks` widget (`app/Filament/Widgets/QuickLinks.php`): dashboard navigation cards,
  including the new weekly schedule page (custom Blade view), using Sajel brand colours and
  responsive grid classes.
- `UnloggedSessionsWidget` (`app/Filament/Widgets/UnloggedSessionsWidget.php`): a teacher-only
  dashboard reminder for missed Daily Logs. It scans the teacher's schedules from Sunday through
  today in `Africa/Algiers`, treats a session as overdue once its end time has passed, and lists
  sessions whose log is missing or still `planned`; `completed` and `cancelled` logs are resolved.
  Each item shows the day/date, subject, classroom/group, time, and a missing/planned status badge.
  The "إكمال السجل" button opens the prefilled create page with `schedule_id` and `date`, or the
  existing log's edit page. The list uses Livewire `WithPagination` at five items per page (page
  name `pendingPage`); the card header always shows the total pending count and the pagination
  links sit at the bottom of the card. A subtle "all caught up" success state is rendered when
  nothing is pending. The widget is hidden for admins and guests via `canView()`.

## 6. Routes & Views

- `routes/web.php`:
  - `/` closure returning the `welcome` view (landing page with a language dropdown, login/register
    links for guests, dashboard link for authenticated users).
  - `/{locale}` (where `{locale}` ∈ `ar`, `en`, `fr`) renders the same welcome view behind
    `SetFilamentLocale`, so localized home pages share the switcher state.
  - `/print/daily-logs` (`print.register`, `auth`) accepts `ids[]` query parameters and renders the
    official A4 register. Completed logs only; teachers are scoped to their own schedules, admins may
    print any teacher's completed logs. Guests are redirected to the Filament login page. The same
    register is offered as a server-side PDF from the resource bulk action.
  - `/{locale}/print/assessments/{assessment}` (`print.assessment`, `auth` + locale middleware)
    renders the standalone A4 assessment gradebook; teachers are scoped to their own assessments and
    admins may print any assessment.
  - `/{locale}/print/pedagogical-memos/{memo}` (`print.pedagogical-memo`, `auth` + locale middleware)
    renders the standalone A4 memo sheet. Teachers may print their own memos; admins may print any.
  - `/{locale}/print/pedagogical-memos/{memo}/pdf` (`print.pedagogical-memo.pdf`) streams the same
    memo as a server-side PDF through `PedagogicalMemoPrintService` and Browsershot.
- The Filament panel is available at `/ar/auth`, `/en/auth`, and `/fr/auth`; pedagogical memos live at
  `/{locale}/auth/pedagogical-memos`.
- Curriculum catalog (admin-only): `/{locale}/auth/curriculum-domains`,
  `/{locale}/auth/curriculum-sequences`, and `/{locale}/auth/curriculum-knowledge-resources`.
  Curriculum progress for teachers and admins: `/{locale}/auth/curriculum-progress`.
- Teacher schedule page: `/{locale}/auth/my-schedule` (day/week views; teachers only).
- Language files: `lang/ar.json`, `lang/en.json`, `lang/fr.json`.

## 7. Testing

`php artisan test` — **373 passing, 20,469 assertions in the latest full run, 0 failures**:

| Suite                                  | Tests | Covers                                                        |
|----------------------------------------|-------|---------------------------------------------------------------|
| `Tests\Unit\ExampleTest`               | 1     | Sanity check                                                  |
| `Tests\Feature\ExampleTest`            | 1     | `/` returns a successful response                             |
| `Tests\Feature\ActiveLearningStrategyTest` | 3  | Teacher access, seeded default strategies, strategy availability |
| `Tests\Feature\AuthorizationTest`      | 18    | Role-based panel access; student/assessment/grade/attendance policies; admin-only core academic structure policies, screens, and navigation hiding; relation-manager parent ownership; admin bypass |
| `Tests\Feature\ClassroomNamingTest`    | 3     | Auto-generated classroom name from level grade, phase abbreviation, and class number; regeneration on update; graceful fallback when the convention is incomplete |
| `Tests\Feature\CurriculumTrackingTest` | 17    | Curriculum hierarchy/relationships/ordering, daily-log resource mapping and register summary, admin CRUD page forms, admin-only catalog policies, subject/level-scoped selector options, forged mapping rejection, automatic progress after completion (duplicate logs counted once), classroom/subject/group scoping, teacher class-wide overview vs admin all-pairs, teacher defaults/authorization on the progress page, localized page rendering, search/status/sort tools, at-risk filtering, pagination without scroll-jump, tool reset, CSV export, resource nulling/domain cascade |
| `Tests\Feature\DailyLogPagesTest`      | 6     | List/edit pages, schedule+date prefilling, cross-teacher protection, create-form save, unique `(schedule_id, date)` validation |
| `Tests\Feature\AttendanceTest`         | 7     | Attendance defaults/relationships, unique pair, group and classroom roster resolution, idempotent sync, new-student insertion, inline status update, relation-manager registration |
| `Tests\Feature\GradebookTest`          | 12    | Assessment/grade relationships and casts, unique pair, group/classroom roster sync, new-student insertion, inline decimal scores and max validation, teacher-only schedule creation/scoping, schedule immutability on edit, cascade deletes, relation-manager registration |
| `Tests\Feature\AssessmentPrintTest`    | 10    | Localized assessment labels (navigation/breadcrumb/title), localized page rendering, session-locale fallback for unlocalized URLs, A4 RTL gradebook print, owner/admin scoping, guest redirect, unsupported locale, list print action, header print-action scoping |
| `Tests\Feature\DashboardThemeTest`     | 7     | No missing utility classes, dark mode + theme asset, RTL Arabic panel + topbar switcher, brand colours, widget layout/registration, stat tabs |
| `Tests\Feature\DataShowModeTest`       | 5     | Slide generation and clean narration text, localized voice toolbar and speech locales, Data Show Mode rendering and interactions |
| `Tests\Feature\MySchedulePageTest`     | 4     | Teacher-only access, day/week views, guest redirect           |
| `Tests\Feature\TeacherInteractiveScheduleTest` | 24 | Rendering, colours, dark mode, day/search/view/expand interactions, complete/cancel, stats, date-aware log navigation, teacher scoping |
| `Tests\Feature\TeacherTimetableTest`   | 16    | Fixed five-day/seven-slot grid, matrix mapping, multi-hour row spans with covered cells (2h, 3h, back-to-back, lunch-break clamp), workload totals and overload badge, equality boundary, empty placeholders, subject-colour mapping (all six palette colours), primary fallback for missing/unknown colours, teacher scoping, current-week click dates, stale-date fallback, date-aware log navigation |
| `Tests\Feature\TeacherSubjectAssignmentTest` | 6 | Primary/secondary relationships, default weekly limit, teacher form default/restriction, admin teacher-scoped subject options, user-form specialization assignment/editing |
| `Tests\Feature\StudentManagementTest`  | 8     | Student/group relationships, cascade delete, admin-only resource/navigation, form creation, unique nullable registration, group filter, CSV importer group linking |
| `Tests\Feature\RegisterPrintTest`      | 15    | Authenticated print route, teacher/admin scoping, completed-only filtering, validation, guest redirect, locale-aware Arabic/English/French register output, always-visible PDF bulk action with completed-only server filtering + streamed download, exporter columns/filter, live export progress widget (current-user scoping, percentage, polling, export-start integration, hiding when complete, completion poll does not 403), and owner/admin-only export downloads |
| `Tests\Feature\PedagogicalMemoTest`    | 21    | Memo relationships/casts, teacher/admin resource scoping, create/edit ownership, admin assignment/required teacher, duplicate into current bank, archive/restore, template/archive filters, policy/print/PDF authorization, localized A4 RTL/LTR print views, guest redirect, unsupported locale, localized labels, print/PDF actions, PDF download |
| `Tests\Feature\WelcomeAndDashboardTest`| 7     | Welcome page links, login/register access, guest redirect, dashboard stats/quick links, French and Arabic/RTL localization |
| `Tests\Feature\UnloggedSessionsWidgetTest` | 4 | Teacher-only visibility, Sunday-to-today missing/planned detection, today's end-time boundary, teacher scoping, completion URLs, five-per-page Livewire pagination with total count, success state |
| `Tests\Feature\AiLessonPlannerTest`    | 14    | Service contract (locale-aware prompts, Arabic fallback, UUID-keyed `lesson_structure` block, fenced-JSON tolerance, OpenAI driver, provider errors/retries/fallbacks), form-action injection, missing title/schedule guard, teacher-scoped schedules |
| `Tests\Feature\MotivationPulseTest` | 6 | Zero-click badge computation, comeback-champion detection, quick-kudos authorization, teacher/admin hub access, and the A4 certificate route in Arabic/English/French |
| `Tests\Feature\LocalizationCompletenessTest` | 2 | Scans literal `__()`/`trans()`/`@lang()` keys in app, views, routes, and config; requires English/French entries for user-facing strings and Arabic entries for non-Arabic keys |
| `Tests\Feature\MiddleSchoolSeederTest` | 21    | Dataset and active-learning strategy seed coverage, official subjects, school identity and holiday calendar, school structure, students, teachers, demo-teacher assignments, deterministic 20-hour schedules, two-week daily logs, authentic curriculum mappings, sequential assessments and balanced learner profiles, attendance distribution/streaks, labs/seating plans, pedagogical follow-up records, destructive wipe, idempotent re-runs, bulk query budget (<400 queries), factory identities |
| `Tests\Feature\ScheduleOverlapTest`    | 6     | Overlapping/identical teacher-day slots rejected; back-to-back slots allowed; scoping to teacher and day; edit-safe self-exclusion; admin double-booking guard |
| `Tests\Feature\LocalePersistenceTest`  | 5     | Locale persisted when visiting localized panel routes, updated on language switch, restored on `/livewire/update` (post-save redirect URL generation), applied to non-localized URLs, unsupported locales ignored |
| `Tests\Feature\LabManagementTest`      | 12    | Lab management, relation-manager station creation (header + empty-state actions), and seating-plan behavior |
| `Tests\Feature\BehaviorObservationResourceTest` | 1 | Admin list rendering with follow-up badge formatting |
| `Tests\Feature\OfficialReportsTest`    | 19    | Official report page access, timetable/attendance/transcript output, scoping, localization, and PDF routes |
| `Tests\Feature\QuickAttendanceTest`    | 8     | Quick-attendance access, roster display, attendance saving, and authorization |
| `Tests\Feature\OfflineAttendancePwaTest` | 12  | PWA assets and panel registration, user-scoped offline storage, guest cache/roster purge, localized sync warning, idempotent multi-student sync, schedule/roster authorization, admin bypass, invalid batch validation, offline controls |
| `Tests\Feature\DigitalVisaTest` | 5 | Admin single/bulk stamps, completed-only enforcement, teacher protection, and HTML/PDF register visa output |
| `Tests\Feature\SchoolSupervisionHubTest` | 6 | Locale-aware admin-only access, weekday/time-based compliance, classroom-scoped attendance and distinct-date repeat absences, behavior follow-up resolution with notes |
| `Tests\Feature\StudentReportCardTest` | 4 | Teacher roster authorization and subject/record scoping, admin full reports, localized print/PDF routes, and Official Reports student picker |
| `Tests\Feature\SchoolSettingsTest` | 4 | Admin-only school identity settings view/update (teacher 403), cache refresh, and dynamic school/directorate/headteacher identity on register sheets, class councils, and Official Reports |

## 8. Project Health Snapshot

| Aspect            | Status                                             |
|-------------------|----------------------------------------------------|
| Migrations        | ✅ All 43 migrations ran |
| Test suite        | ✅ 373/373 passing, 0 failures (20,469 assertions in the latest full run) |
| Admin panel       | ✅ 20 Filament resources + custom dashboard, schedule, curriculum-progress, data-show pages + ar/en/fr |
| Seed data         | ✅ Destructive middle-school dataset + optional SajelSeeder |
| Version control   | Git repo on `main`; Phase 2/3/4 complete (`f4e776a`, `f8d...`) |
| README            | ✅ Project-specific bilingual README (English structure + Arabic domain) |
| `APP_NAME`        | ✅ `Sajel`; `APP_URL` = `https://sajel.dev/` |

## 9. Phase 2 Completion Note

All 14 Phase 2 items are completed (`100%`). Previous remaining gaps (official reports, lab management, classroom toolbar, quick attendance, behavior observations, active learning strategies, data show, pedagogical remediation) were implemented, tested, and committed (`main`).

No further Phase 2 work is required. Phase 3 is now complete; see the Phase 3 roadmap below. The project README (`README.md`) documents the complete feature matrix.

## 10. Phase 2 Roadmap (Step-by-Step Tracking Checklist)

> **قاعدة التتبع:** عند إكمال أي خطوة من هذه القائمة، يجب تحديث حالتها إلى `[x]` في هذا الملف بعد التحقق من نجاح جميع الاختبارات عبر `php artisan test` وتنسيق الكود عبر `vendor/bin/pint`.

**Baseline:** All 14 Phase 2 roadmap items complete (100%). This section remains as historical reference only.

| Axis | Done | Remaining | Progress |
|------|------|-----------|----------|
| 1. Planning, Curriculum & Reports | 5 | 0 | 100% |
| 2. Lab & Student Management | 5 | 0 | 100% |
| 3. Classroom Activities & Presentation | 3 | 0 | 100% |
| 4. Assessment & Pedagogical Remediation | 1 | 0 | 100% |
| **Total** | **14** | **0** | **100%** |

### 10.1 المحور الأول: التخطيط والمنهاج والتقارير (Planning, Curriculum & Reports)

- [x] الدفتر اليومي ودفتر النصوص المدمج (`DailyLogResource`).
- [x] التوقيت الأسبوعي التفاعلي (`TeacherTimetable` & `ScheduleResource`).
- [x] **المذكرات البيداغوجية (Pedagogical Memos):** إنشاء مكتبة/بنك مذكرات بيداغوجية نموذجية قابلة لإعادة الاستخدام والطباعة كوثيقة مستقلة.
- [x] **متابعة المنهاج الرسمي (Official Curriculum Tracking):** بناء نموذج وواجهة لتتبع التدرج السنوي (الميادين، المقاطع التعلمية، الموارد المعرفية) وحساب نسبة التقدم في البرنامج لكل قسم تلقائياً عند تأكيد إنجاز الدروس.
- [x] **التقارير والاستمارات الرسمية (Official Reports & Forms):** توسيع نظام الطباعة والتصدير ليشمل طباعة جدول التوقيت الأسبوعي، تقارير الحضور والغيابات المجمعة، وكشوف النقاط الرسمية بصيغة A4 RTL.

> **تم إنجاز المذكرات البيداغوجية:** `PedagogicalMemoResource` + `PedagogicalMemoPrintService` +
> localized `print.pedagogical-memo` / `print.pedagogical-memo.pdf` routes, verified by
> `PedagogicalMemoTest` (21 tests).
>
> **تم إنجاز متابعة المنهاج الرسمي:** `CurriculumDomainResource` + `CurriculumSequenceResource` +
> `CurriculumKnowledgeResource` + `CurriculumProgress` (with `CurriculumProgressService`), verified
> by `CurriculumTrackingTest` (17 tests). The seeder builds 104 domains, 208 sequences, and 416
> resources, and maps every seeded daily log to a matching resource.
> Dark mode contrast fixed: selected table row uses `bg-amber-50/90 dark:bg-amber-500/15 ring-1
> ring-inset ring-amber-500/40 dark:ring-amber-400/40` with `text-amber-950 dark:text-amber-100`
> text colors; all table cells use `text-gray-900 dark:text-gray-100`; rows are clickable via
> `wire:click="showDetail(...)"` to load domain/sequence/resource detail.

> **تم إنجاز التقارير والاستمارات الرسمية:** `OfficialReportService` + `OfficialReportController` +
> `OfficialReports` Filament page + three print views (`print.timetable`, `print.attendance-summary`,
> `print.grade-transcript`) with six routes (print + PDF for each), verified by
> `OfficialReportsTest` (19 tests). No migration required. A4 RTL, teacher scoping enforced,
> translations added to `lang/ar.json`, `lang/en.json`, and `lang/fr.json` (37 new keys).
> Dark mode contrast fixed: teacher box uses `bg-gray-50 border border-gray-200 dark:bg-gray-800/90
> dark:border-white/10` with `text-gray-950 dark:text-white` name value; all select inputs use
> `dark:bg-gray-800 dark:text-white` option colors.

**Definition of done (Axis 1):**

- Memo bank supports create/edit/duplicate/archive, links to subject + level + phase, and renders a standalone A4 RTL PDF.
- Curriculum entities define fields, learning sequences, and knowledge resources per subject/level; completing a daily log advances the mapped sequence and updates classroom progress.
- Weekly timetable, attendance summary, and grade transcript print/export without leaving the Filament panel.

> **تم إنجاز الأقسام والمخابر:** `Lab` + `LabStation` models + `LabStatus` enum + migrations (`labs`, `lab_stations`) +
> factories + policies + `LabResource` with `LabStationsRelationManager` + Filament pages, verified by
> `LabManagementTest` (12 tests), including admin station creation from the relation-manager header and
> empty-state actions. Admin-only navigation, teacher scoping enforced,
> translations added to `lang/ar.json`, `lang/en.json`, and `lang/fr.json` (20 new keys).

> **تم إنجاز مخطط جلوس المخبر:** `LabSeatingPlan` + `LabSeatingPlanStation` models + migrations (`lab_seating_plans`, `lab_seating_plan_stations`) +
> factories + Livewire component `LabSeatingPlan` + Blade view with U-shape layout + Filament page,
> verified by `LabManagementTest` (includes seating plan tests). Admin-only navigation,
> U-shape station layout (top row + left/right columns), student assignment/unassignment,
> translations added to `lang/ar.json`, `lang/en.json`, and `lang/fr.json` (22 new keys).

### 10.2 المحور الثاني: تسيير المخبر والتلاميذ (Lab & Student Management)

- [x] سجل التلاميذ واستيراد القوائم (`StudentResource`).
- [x] **الأقسام والمخابر (Classrooms & Labs):** توسيع البنية لإدارة قاعات المخابر، عدد المناصب/الحواسيب، وحالة كل جهاز (يعمل، معطل، قيد الصيانة).
- [x] **مخطط جلوس المخبر (U-Shape Lab Seating Plan):** بناء واجهة Livewire تفاعلية على شكل حرف (U) لتوزيع تلاميذ الفوج أو القسم على مناصب الحواسيب وحفظ مخطط الجلوس.
- [x] **تسجيل الحضور السريع (Quick Attendance):** إنشاء صفحة/واجهة سريعة (`QuickAttendance` Page + Livewire component + Blade view + 19 translations) في القائمة الجانبية تفتح مباشرة قائمة تلاميذ الحصة الجارية لتسجيل الغيابات والتأخرات بنقرة واحدة دون المرور بعدة صفحات. 8 feature tests pass.
- [x] **الملاحظات والسلوك (Behavior & Observations):** `BehaviorObservation` (model/migration/resource) + مرتبط بالتلميذ عبر `student_id` + تصنيف (إيجابي/انضباطي/ملاحظة) + حالة متابعة.

**Definition of done (Axis 2):**

- Lab rooms and stations have CRUD, capacity, and per-device status.
- Seating plans are stored per group/classroom and survive reloads; the U-shape layout is usable on desktop and tablet.
- Quick attendance opens the current or next session roster and saves attendance in one click.
- Behavior entries are typed, dated, linked to a student, and visible from the student profile.

### 10.3 المحور الثالث: الأنشطة وعرض الحصة (Classroom Activities & Presentation)

- [x] **استراتيجيات التعلم النشط (Active Learning Strategies):** نموذج + Migration + Seeder (8 cards) + Resource + مدمج في `DailyLogResource` phase builder + 19 translations.
- [x] **أدوات الحصة التفاعلية (Interactive Classroom Tools):** `ClassroomToolbar` Livewire + Blade + Page (timer countdown, random student picker, auto group generator) + navigation entry.
- [x] **عرض المسلاط (Data Show Mode):** `DataShowMode` Livewire + Blade + Page — عرض ملء الشاشة (RTL، تنقل لوحة مفاتيح، شرائح) يعرض عنوان الدرس، الكفاءة، المراحل، والأنشطة بخط كبير.

**Definition of done (Axis 3):**

- Strategy cards are seeded/referenceable and can be attached to a lesson phase.
- The classroom toolbar runs a visible countdown timer, picks students without repetition in a round, and creates balanced random groups.
- Presentation mode is full-screen, keyboard navigable, RTL, and hides the Filament shell.

### 10.4 المحور الرابع: التقويم والمعالجة البيداغوجية (Assessment & Pedagogical Remediation)

- [x] التقييمات والمراقبة المستمرة ودفتر العلامات (`AssessmentResource` & `GradesRelationManager`).
- [x] **المعالجة البيداغوجية (Pedagogical Remediation):** `PedagogicalRemediation` (model/migration/resource) + ربط بالتلميذ + سبب/أنشطة/تاريخ/متابعة/حالة.

**Definition of done (Axis 4):**

- A teacher can flag students from an assessment or manually with a reason.
- Remediation sessions record date, objective, activities, attendees, and follow-up status.
- Remediation history is visible on the student profile and printable.

### 10.5 Suggested Execution Order

1. **Official Reports & Forms** — builds on curriculum progress, attendance, and grade data.
2. **Lab & Student Management** — lab rooms/stations, U-shape seating, quick attendance, and behavior log.
3. **Classroom Activities & Presentation** — strategy cards, classroom toolbar, and Data Show mode.
4. **Pedagogical Remediation** — uses assessment results and behavior observations to manage support sessions.

> Pedagogical Memos and Official Curriculum Tracking (Axis 1) are complete. The memo bank established
> the reusable A4 print/PDF pattern, and curriculum tracking now supplies the programme-progress data
> that the official reports can follow.
>
> Update the checkbox for an item and the progress table above after it is implemented, tested, and formatted.

## 11. Dashboard Livewire Remake (dark-mode safe)

### Checklist

- [x] 1. Create `app/Livewire/DashboardStats.php` — Livewire component using Filament theme colors (primary, secondary, success, warning, info, danger) with dark/light-aware Tailwind classes
- [x] 2. Create `resources/views/livewire/dashboard-stats.blade.php` — stat cards with `text-gray-900 dark:text-white` and `bg-white dark:bg-gray-800` etc.
- [x] 3. Update `app/Filament/Pages/Dashboard.php` — import the Livewire component instead of `SajelStats` widget
- [x] 4. Remove/replace `SajelStats` widget (dark-mode text visibility issue)
- [x] 5. Verify dark mode: stat card titles, values, descriptions, and chart colors all readable
- [x] 6. Run `php artisan test` — all tests pass
- [x] 7. Run `vendor/bin/pint` — code formatted
- [x] 8. Update `STATUS_PROJECT.md` with completion notes

## Getting Started

```bash
composer setup          # install deps, create .env, key, migrate, npm install + build
# or manually:
composer install
php artisan key:generate
php artisan migrate:fresh --seed   # wipe + rebuild the full middle-school dataset
# or, on an already-migrated database (also destructive):
php artisan db:seed --class=MiddleSchoolDatabaseSeeder
php artisan db:seed --class=SajelSeeder   # optional small demo, run instead of the large dataset
composer dev            # serve + queue + logs + vite concurrently
```

If you run only `php artisan serve` instead of `composer dev`, also start a queue worker so CSV
imports and daily-log exports are processed (`php artisan queue:work`). Without it, the export
progress bar stays at 0%.

### AI lesson planner

The "توليد التحضير بالذكاء الاصطناعي" button on the daily-log title field needs one API key
(no extra composer package — see `config/ai.php`):

```dotenv
AI_PROVIDER=gemini                     # or "openai"
GEMINI_API_KEY=...                     # https://aistudio.google.com/
GEMINI_MODEL=gemini-3.8-flash
GEMINI_FALLBACK_MODEL=gemini-3.5-flash # used only when the primary is overloaded
AI_RETRY_TIMES=2                       # total attempts for transient provider errors
AI_RETRY_DELAY=500                     # milliseconds between attempts
AI_TIMEOUT=60                          # keep below PHP max_execution_time for web SAPIs
# OPENAI_API_KEY=... / OPENAI_MODEL=gpt-4o-mini when AI_PROVIDER=openai
```

Transient errors (HTTP 429/500/502/503/504 and network failures) are retried automatically.
If Gemini's primary model stays overloaded, the configured fallback model is tried once. Missing
keys, exhausted retries, and other provider errors are surfaced as danger notifications instead
of breaking the form.

### PDF register (Browsershot)

The bulk action "تحميل دفتر النصوص (PDF)" renders `print.register` through headless Chrome.
Select one or more daily logs, open **الإجراءات** in the table toolbar, then choose the PDF action.
The action stays visible so Filament can render it before any row is selected; the service still
re-queries completed records only and shows a danger notification when the selection has none.

```bash
composer require spatie/browsershot
npm install                         # installs Puppeteer (runtime dependency for Browsershot)
npm run build                       # refresh the inlined Tailwind stylesheet
npx puppeteer browsers install chrome   # optional when Chrome is already installed
```

Set these only when Node/npm/Chrome are not discoverable from the PHP process (for example under
Apache on Windows). Use forward slashes or escaped backslashes:

```dotenv
BROWSERSHOT_NODE_BINARY="C:/Program Files/nodejs/node.exe"
BROWSERSHOT_NPM_BINARY="C:/Program Files/nodejs/npm.cmd"
BROWSERSHOT_CHROME_PATH="C:/Program Files/Google/Chrome/Application/chrome.exe"
```

Then open the app URL and visit `/auth` (login/register). Demo accounts:

- Teacher: `teacher@sajel.test` / `password`
- Admin: `admin@sajel.test` / `password`

### Filament component cache

If you add Filament pages/resources after running `php artisan filament:optimize` (or if
`bootstrap/cache/filament/panels/*.php` exists from an earlier run), web requests keep using the
stale component list and may throw `RouteNotFoundException` for new pages — even though
`php artisan route:list` shows them (console commands bypass this cache). Refresh or clear it:

```bash
php artisan filament:cache-components   # re-cache the current components
# or, in development:
php artisan filament:optimize-clear     # remove the cache entirely
```

## 11. Phase 3 Roadmap

Phase 3 is complete (100%). AI-generated lesson plans follow the active Arabic, English, or French
locale; unsupported locales fall back to Arabic. The offline attendance PWA caches the authenticated
Quick Attendance page and compiled Vite assets, stores per-session roster snapshots and a last-write-
wins local change queue, and flushes validated batches through the authenticated Livewire component.
Data Show Mode prepares HTML-free narration for every slide and provides locale-aware browser speech
controls, local/native matching voice preference, persisted playback speed, keyboard toggling, and
cancellation on navigation.

- [x] **Locale-aware AI lesson plans:** Localize generation instructions for Arabic, English, and
  French, and align Gemini structured-output descriptions with the selected language.
- [x] **Offline attendance PWA:** Provide the RTL manifest and service worker; cache snapshots as
  `sajel.attendance.{user_id}.roster.{schedule_id}.{date}`; queue edits at
  `sajel.attendance.{user_id}.queue` keyed by `{schedule_id}:{date}:{student_id}`; synchronize online
  in batches of up to 200. The server validates dates/status/remarks, enforces schedule ownership and
  roster membership (admin bypass), creates planned daily logs, materializes missing attendance rows,
  and upserts status/remarks idempotently. Sync notifications report the number of entries applied.
  Storage is bound to the signed-in user through `sajel.attendance.owner`; legacy unscoped keys are
  cleared. Guest pages remove cached attendance HTML and roster snapshots, while preserving a pending
  queue for the same teacher; another account opening Quick Attendance clears all attendance data.
  Use only on a teacher-controlled, protected device and clear site data when the device is shared or
  retired.
- [x] **Arabic voice narration:** Prepare clean plain-text narration for each slide, select a speech
  locale and preferred matching local voice, and provide play, pause/resume, stop, and keyboard
  controls. Playback speed persists in `sajel.datashow.speech_rate`. Speech is cancelled when slides
  or lessons change and when leaving the page.

## 12. Phase 4 Roadmap

Phase 4 focuses on school supervision and administrative auditing. Completed-register review stamps
capture reviewer identity, review timestamp, and administrative notes; admin-only row and bulk actions
enforce completed status; teachers see the stamp read-only; and browser-printed and PDF registers
include the official RTL administrative visa and notes. The supervision hub is an admin-only, 30-second-refreshing cockpit with localized date, classroom,
and level filters. Daily compliance counts the scheduled sessions for the selected weekday; missing
and planned logs become overdue only after their end time in the configured Algiers timezone, while
past dates are treated as elapsed. Attendance alerts include student, class/group, subject, teacher,
and remarks; repeat absences count distinct absent days in the inclusive 30-day window. Open behavior
cases include reporter and optional schedule context, and administrators can resolve them with
follow-up notes. Phase 4 is complete (100%). `StudentReportCardService` authorizes with `StudentPolicy` and provides
`renderHtml()` and `generatePdf()` (Browsershot with inlined compiled Vite CSS). Its A4 RTL report
includes student identity and academic year, attendance totals and the 10 most recent absences/lates,
subject-grouped assessment details and averages normalized to /20, behavior observations and
follow-up state, and remediation sessions/outcomes. Teachers see attendance and grades on their
schedules and authored behavior/remediation records constrained to their linked schedules;
administrators see all student data. `OfficialReports` provides classroom/group/student filters, and
`StudentResource` exposes print and PDF row actions.

- [x] **Digital administrative visa & register stamp:** Add nullable reviewer, timestamp, and notes
  fields; enforce admin-only stamping of completed logs; provide a review-status table badge/filter and
  read-only review details; include the review stamp in the HTML/PDF register.
- [x] **Educational Counselor (مستشار التربية) daily supervision hub:** Implement
  `SchoolSupervisionHub` and `SchoolSupervisionService` with localized admin-only access, selected-date
  and classroom/level filters, weekday- and end-time-aware daily compliance, weekly metrics, attendance
  and distinct-day repeat-absence radar, and discipline follow-up resolution with optional notes.
- [x] **Student comprehensive parent report card (A4 RTL PDF):** `StudentReportCardService` + controller + routes + `StudentResource` actions + `OfficialReports` picker; authorization scoped (Admin = all subjects; Teacher = own schedules); 4/4 feature tests pass (312 total, 14,800 assertions).
  and Browsershot PDF using `StudentReportCardService`, with attendance summaries and recent alerts,
  normalized per-subject grade averages and assessment details, behavior/follow-up history,
  remediation outcomes, teacher schedule/record scoping, admin-wide access, and Official Reports /
  StudentResource integration.

## 13. Phase 5 Roadmap — Institutional Operations & Professional Portfolio

Phase 5 Milestone 1 is complete (100% for this milestone). Institutional operations now include
class-level deliberation reporting (`ClassCouncilHub` + `ClassCouncilService`) that aggregates
per-subject `/20` averages, attendance tallies, discipline observations, pedagogical recommendations,
and classroom KPIs into an official A4 Landscape RTL PDF (`print.class-council` / `.pdf`).
Authorization mirrors `StudentPolicy`: Admins see all classrooms; Teachers only access classrooms
where they have at least one `Schedule`. Translation keys added for `ar`/`en`/`fr`.

- [x] **Class Council Deliberation Hub (`ClassCouncilHub`):** Filament page with classroom/group
  selector, KPI cards, honor-roll badges, full student deliberation matrix, and direct print/PDF links.
- [x] **Class Council Service (`ClassCouncilService`):** Computes per-subject averages (`/20`),
  general average, attendance counts, behavior tallies (positive/negative), remediation counts,
  automatic recommendation (`امتياز` → `تحذير`), and classroom KPIs (pass rate, honor roll, absences).
- [x] **Official A4 RTL Report (`class-council.blade.php`):** Landscape layout with header,
  KPI strip, honor distribution badges, full deliberation table, and signature boxes.
- [x] **Print Routes (`ClassCouncilController`):** `/{locale}/print/classrooms/{classroom}/class-council`
  + `.pdf`; `ClassroomPolicy` enforces admin/teacher scoping.
- [x] **Localization:** `ar.json`, `en.json`, `fr.json` updated with `class_council` keys.
- [x] **Tests:** `tests/Feature/ClassCouncilTest.php` (3 pass: service computation, admin access,
  teacher scope + 403 guard).
- [x] **Pedagogical Meeting Log (`PedagogicalMeetingResource`):** Migration + model + factory + policy + Filament resource under `التخطيط والمناهج`; CRUD scoped to teacher/admin.
- [x] **Teacher Professional Portfolio (`TeacherPortfolioService`, A4 RTL PDF):** Routes `print.teacher-portfolio` + `.pdf`; identity card, compliance summary, memos catalog, meetings table, signatures; header actions on `PedagogicalMeetingResource` + `MySchedule`.
- [x] **Build & Quality:** `npm run build` passes; `vendor/bin/pint` passes;
  total suite: `312 passed` (14,800 assertions); 0 failures.


## 14. Phase 5 Roadmap — Complete (100%)

Phase 5 (Institutional Operations & Professional Portfolio) is complete.
- Milestone 1: Class Council Deliberation Hub + A4 RTL PDF
- Milestone 2: Substitute Teacher Assignment + Pedagogical Continuity Handover (, , banner)
- Milestone 3: Pedagogical Meeting Log () + Teacher Professional Portfolio (, A4 RTL PDF, routes, header actions, translations, 3 passing feature tests)

Full suite: 312 passing, 14,800 assertions, 0 failures. Build and Pint clean.


## 15. Phase 6 Roadmap — Complete (100%) — Production Readiness & Parent Engagement

Phase 6: Parent Engagement, Academic Calendar & Production Readiness.
- Milestone 1: Signed Parent Portal (`ParentPortalController` / `ParentPortalService`, `parent.student-portal` signed route 90 days, `parent/student-portal.blade.php`, `StudentResource` action, `StudentReportCardService` integration + QR badge, translations, `ParentPortalTest` — 3 assertions pass).
- Milestone 2: School Holiday Calendar (`SchoolHoliday` model/migration/resource/policy/seeder `SchoolHolidaySeeder`, `scopeCoversDate`/`isHoliday`/`holidayDatesBetween`, `UnloggedSessionsWidget` + `SchoolSupervisionService` holiday-aware, `SchoolHolidayComplianceTest` — pass).
- Milestone 3: Pedagogical Meeting Resource (`PedagogicalMeetingResource`, `TeacherPortfolioService`, controller/routes/translations/header actions + `MySchedule`) — complete.
- Milestone 4: Production Readiness (`SajelHealthCheckCommand` `sajel:health-check` + `--json`, DB 25 tables, timezone `Africa/Algiers`, localization `ar/en/fr`, PWA `dir=rtl`, AI provider read from `config/ai.php` with PASS/WARN key detection; `SajelHealthCheckCommandTest`; `pint`/`npm`/`migrate` clean).

Status 2026-10-28: `373 passed`, **0 failures** (`20,469` assertions); `pint` passed; `npm run test:js` passed (8 tests); `npm run build` passed; health command reports PASS on database, schema (25 tables), timezone, localization, PWA, and AI (`Provider: gemini (gemini-3.8-flash) | Key: configured`). All Phase 2–6 milestones complete (100%). The offline attendance PWA now scopes browser storage by user and clears cached attendance HTML and roster snapshots for guests while preserving a pending queue for the same teacher. Localization is complete across `ar`/`en`/`fr` for user-facing UI and print/PDF labels; the official register and student certificate follow the active locale (Arabic remains the default RTL output), and `LocalizationCompletenessTest` guards against missing keys.

## 16. Phase 7 — School Identity (هوية المؤسسة التربوية)

The school principal can now configure the institution's official identity once from the admin panel,
and every printed A4 sheet and Browsershot PDF picks it up automatically.

- **Settings storage:** `school_settings` single-row table (`SchoolSetting`) with `school_name`,
  `directorate_name`, `municipality_name`, `academic_year`, `principal_name`, `counselor_name`, and
  `school_code`. `SchoolSetting::current()` caches the active row forever and safely returns the
  Algerian middle-school defaults when the row is missing; `saved`/`deleted` events bust the cache.
- **Admin page:** `ManageSchoolSettings` (`إعدادات المؤسسة التربوية`) under `التخطيط والمناهج`,
  restricted to admins through `canAccess()`. Saving refreshes the cache and shows a success
  notification. Teachers receive `403 Forbidden`.
- **Dynamic print headers:** a global view composer shares `$schoolSetting` with `print.*` and
  `filament.pages.official-reports`, so the register, student report card, class council, teacher
  portfolio, pedagogical memo, and Official Reports banner render the real directorate, school name,
  academic year, municipality/code, and principal/counselor names, with safe fallbacks.
- **Verification:** `tests/Feature/SchoolSettingsTest.php` (4 tests, 19 assertions) covers admin
  view/update, teacher 403, and identity rendering on register sheets, class councils, and Official
  Reports. Health check now requires 25 tables including `school_settings`.

--- Phase 9 ---
Sajel Pulse: badges (attendance_streak, comeback_champion, academic_excellence, positive_role_model), pulse_score, classroom synergy meter, 3 champions; DataShow synergy bar + awardQuickKudos; MotivationPulseHub (sparkles icon) + A4 RTL certificate; badges in report/portal; translations added; MotivationPulseTest created.
