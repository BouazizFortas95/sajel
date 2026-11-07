# Sajel (سجِّل) — Project Status

> Last updated: 2026-11-07
> Verified by `php artisan test` (**645 passed**, 22,207 assertions, **0 failures**),
> `npm run test:js` (8 passed), `npm run build`, `php artisan sajel:health-check`, and `vendor/bin/pint`.

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

| Layer     | Choice                                 | Notes                                                          |
| --------- | -------------------------------------- | -------------------------------------------------------------- |
| Framework | Laravel `^12.0`                        | PHP `^8.2`                                                     |
| Admin UI  | Filament `^3.2` (bundles Livewire)     | Single panel, Amber brand color, Cairo font                    |
| Database  | SQLite                                 | `database/database.sqlite` present                             |
| Frontend  | Vite `^7`, Tailwind CSS `^4`, Axios    | Filament theme in `resources/css/filament.css`                 |
| PDF       | `spatie/browsershot` `^5.4`, Puppeteer | Headless Chrome; Arabic shaping + Tailwind-preserving PDF      |
| i18n      | `mcamara/laravel-localization` `^2.4`  | `ar`, `en`, `fr` locales                                       |
| Timezone  | `Africa/Algiers`                       | `APP_TIMEZONE`, used for weekly dates                          |
| Testing   | PHPUnit `^11.5`                        | **410 tests, all green (20,753 assertions in the latest run)** |
| Tooling   | Laravel Pint, Pail, Sail, Tinker       | Dev dependencies installed                                     |

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

| Model                | Purpose                                                                                                                                         |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `User`               | Teacher/admin; implements `FilamentUser`; primary/secondary subjects, weekly workload limit, schedules & daily logs                             |
| `EducationPhase`     | Educational phase (e.g. Middle School) with abbreviation and JSON `settings`                                                                    |
| `Level`              | Grade/year within a phase, with a grade number used in classroom names                                                                          |
| `Classroom`          | Class section within a level; `name` is auto-generated on save                                                                                  |
| `Group`              | Sub-division of a classroom; has many students                                                                                                  |
| `Student`            | Enrolled student (unique nullable registration number) in a group                                                                               |
| `Subject`            | Subject with a display color                                                                                                                    |
| `CurriculumDomain`   | Annual-plan domain (ميدان) for a subject and level; has many sequences and resources                                                            |
| `CurriculumSequence` | Ordered learning sequence (مقطع تعلمي) inside a curriculum domain                                                                               |
| `CurriculumResource` | Ordered knowledge resource (مورد معرفي) inside a sequence; may be mapped by many daily logs                                                     |
| `Schedule`           | A teacher's weekly recurring lesson slot; `label()` for display                                                                                 |
| `DailyLog`           | Per-slot daily log; dynamic `content_data` blocks; optional curriculum-resource mapping; register summary generation; has many attendances      |
| `Attendance`         | Per-student status (`present`/`absent`/`late`/`excused`) and remarks for one daily log                                                          |
| `Assessment`         | Quiz/midterm/exam/continuous assessment attached to a schedule; materializes grade rows                                                         |
| `Grade`              | Per-student score and remarks for one assessment; unique per `(assessment, student)`                                                            |
| `PedagogicalMemo`    | Reusable teacher-owned memo linked to subject, level, and learning sequence; JSON activities, template/archive flags, duplication, A4 print/PDF |

### Enums (`app/Enums`)

| Enum               | Cases                                                                                              |
| ------------------ | -------------------------------------------------------------------------------------------------- |
| `DayOfWeek`        | Sunday…Saturday (Arabic labels, per-day colors)                                                    |
| `DailyLogStatus`   | `Planned` (مخطط), `Completed` (منجز), `Cancelled` (ملغى)                                           |
| `AttendanceStatus` | `Present` (حاضر), `Absent` (غائب), `Late` (متأخر), `Excused` (معفى)                                |
| `AssessmentType`   | `Quiz` (اختبار قصير), `Midterm` (فرض الفصل), `Exam` (امتحان), `ContinuousAssessment` (تقويم مستمر) |
| `LessonPhaseType`  | Warmup, Discovery, Institutionalization, Application, Evaluation (مراحل الدرس)                     |
| `Role`             | `Admin` (مدير), `Teacher` (أستاذ) with labels/colors/options                                       |

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

| Resource                      | Manages                                                                                                                                                                                                                                                                                                                                                          |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `DailyLogResource`            | Daily logs / lesson preparations (the richest resource: `Builder` content data, `Tabs` form, "تأكيد الإنجاز" action, completion-gated `actual_content`/`homework`, generated register content, CSV/XLSX export, completed-only bulk register PDF download, the AI lesson-plan generator on the title field, and the auto-populated `AttendancesRelationManager`) |
| `AssessmentResource`          | Assessments and the lazy, auto-populated `GradesRelationManager` (inline decimal scores and remarks, dynamic max-score validation, teacher-scoped schedule choices)                                                                                                                                                                                              |
| `PedagogicalMemoResource`     | Reusable memo bank (subject/level/sequence links, template and archive filters, duplicate/archive/restore actions, A4 print and server-side PDF)                                                                                                                                                                                                                 |
| `CurriculumDomainResource`    | Annual-plan domains per subject and level (ordered names, optional code/description, sequences/resources counts)                                                                                                                                                                                                                                                 |
| `CurriculumSequenceResource`  | Learning sequences inside a domain (ordered, optionally coded, with resource counts)                                                                                                                                                                                                                                                                             |
| `CurriculumKnowledgeResource` | Knowledge resources inside a sequence (custom route slug `curriculum-knowledge-resources`)                                                                                                                                                                                                                                                                       |
| `ScheduleResource`            | Weekly schedules (cascading classroom → group selects; teacher/admin subject choices restricted to the teacher's primary/secondary assignments)                                                                                                                                                                                                                  |
| `SubjectResource`             | Subjects and colors                                                                                                                                                                                                                                                                                                                                              |
| `ClassroomResource`           | Classrooms / academic years (auto-generated `{grade} {abbr} {number}` name; class number form field)                                                                                                                                                                                                                                                             |
| `LevelResource`               | Levels (name + numeric grade number)                                                                                                                                                                                                                                                                                                                             |
| `GroupResource`               | Groups                                                                                                                                                                                                                                                                                                                                                           |
| `StudentResource`             | Students, group assignment, group filter, CSV import                                                                                                                                                                                                                                                                                                             |
| `EducationPhaseResource`      | Education phases, abbreviations and settings                                                                                                                                                                                                                                                                                                                     |
| `UserResource`                | Users, roles, teacher primary/secondary subjects, and weekly workload limit (admin-only via `UserPolicy`)                                                                                                                                                                                                                                                        |

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
  (`resources/views/livewire/dashboard-stats.blade.php`): role-aware, URL-stateful hub with
  Overview, Schedule, Attendance, Lessons & register, Assessments & learning, Curriculum,
  Students & support, and admin-only Institutional tabs. Overview embeds the smart log and quick
  links; Lessons embeds `UnloggedSessionsWidget` for teachers. Periods are today/current
  Sunday–Saturday week/current month in `Africa/Algiers`; `tab`, `period`, `classroom`, and
  `subject` persist in the URL. Forged filters are rejected with `403`, and institutional data is
  guarded in `DashboardMetricsService` as well as hidden from teachers. Exact metric definitions,
  privacy/performance notes, and gaps are documented in README §10.
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

`php artisan test` — **425 passing, 20,836 assertions in the latest full run, 0 failures**:

| Suite                                           | Tests | Covers                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| ----------------------------------------------- | ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `Tests\Unit\ExampleTest`                        | 1     | Sanity check                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `Tests\Feature\ExampleTest`                     | 1     | `/` returns a successful response                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `Tests\Feature\ActiveLearningStrategyTest`      | 3     | Teacher access, seeded default strategies, strategy availability                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| `Tests\Feature\AiLessonPlannerTest`             | 14    | Service contract (locale-aware prompts, Arabic fallback, UUID-keyed `lesson_structure` block, fenced-JSON tolerance, OpenAI driver, provider errors/retries/fallbacks), form-action injection, missing title/schedule guard, teacher-scoped schedules                                                                                                                                                                                                                                                                                                                                      |
| `Tests\Feature\AssessmentPrintTest`             | 11    | Localized assessment labels (navigation/breadcrumb/title), localized page rendering, session-locale fallback for unlocalized URLs, A4 RTL gradebook print, owner/admin scoping, guest redirect, unsupported locale, list print action, header print-action scoping, group-scoped section resolution                                                                                                                                                                                                                                                                                          |
| `Tests\Feature\AttendanceTest`                  | 7     | Attendance defaults/relationships, unique pair, group and classroom roster resolution, idempotent sync, new-student insertion, inline status update, relation-manager registration                                                                                                                                                                                                                                                                                                                                                                                                         |
| `Tests\Feature\AuthorizationTest`               | 18    | Role-based panel access; student/assessment/grade/attendance policies; admin-only core academic structure policies, screens, and navigation hiding; relation-manager parent ownership; admin bypass                                                                                                                                                                                                                                                                                                                                                                                        |
| `Tests\Feature\BehaviorObservationResourceTest` | 1     | Admin list rendering with follow-up badge formatting                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| `Tests\Feature\ClassCouncilTest`                | 5     | Deliberation metrics computation, per-subject and general averages, attendance tallies, recommendations, admin wide access, teacher schedule/roster scoping with 403 guard, A4 landscape print/PDF routes                                                                                                                                                                                                                                                                                                                                                                              |
| `Tests\Feature\ClassroomNamingTest`             | 3     | Auto-generated classroom name from level grade, phase abbreviation, and class number; regeneration on update; graceful fallback when the convention is incomplete                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `Tests\Feature\ClassroomToolbarTest`            | 8     | Livewire classroom toolbar, timer countdown, balanced random student picker, group generator, lesson schedule scoping, admin bypass, empty state, and French localization                                                                                                                                                                                                                                                                                                                                                                                                                |
| `Tests\Feature\CurriculumTrackingTest`          | 17    | Curriculum hierarchy/relationships/ordering, daily-log resource mapping and register summary, admin CRUD page forms, admin-only catalog policies, subject/level-scoped selector options, forged mapping rejection, automatic progress after completion (duplicate logs counted once), classroom/subject/group scoping, teacher class-wide overview vs admin all-pairs, teacher defaults/authorization on the progress page, localized page rendering, search/status/sort tools, at-risk filtering, pagination without scroll-jump, tool reset, CSV export, resource nulling/domain cascade |
| `Tests\Feature\DailyLogPagesTest`               | 6     | List/edit pages, schedule+date prefilling, cross-teacher protection, create-form save, unique `(schedule_id, date)` validation                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `Tests\Feature\DashboardHubTest`                | 22    | Role-aware tab visibility, server-scoped teacher metrics, group-targeted schedules, forged URL/Livewire filters and tabs, institutional 403, Algeria timezone and Sunday-first periods, holiday exclusion, attendance due/recorded/not-recorded boundaries, future-log exclusion, assessment windows and grade-entry counts without averages, curriculum delegation/caps, aggregate support scope, URL state, contextual filters, localization/RTL/LTR, empty states, read-only/no-poll rendering, and constant query count                                                                |
| `Tests\Feature\DashboardThemeTest`              | 7     | No missing utility classes, dark mode + theme asset, RTL Arabic panel + topbar switcher, brand colours, widget layout/registration, stat tabs                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| `Tests\Feature\DataShowModeTest`                | 6     | Auto selects today's log, teacher scoped log access, section name in labels/cover, slide generation and clean narration text, localized voice toolbar and speech locales, Data Show Mode rendering and interactions                                                                                                                                                                                                                                                                                                                                                                       |
| `Tests\Feature\DigitalVisaTest`                 | 14    | Admin single and bulk stamps, completed-only enforcement, teacher protection, review details dialog, review badge in list table, and HTML/PDF register visa output with official administrative signature                                                                                                                                                                                                                                                                                                                                                                                 |
| `Tests\Feature\GradebookTest`                   | 12    | Assessment/grade relationships and casts, unique pair, group/classroom roster sync, new-student insertion, inline decimal scores and max validation, teacher-only schedule creation/scoping, schedule immutability on edit, cascade deletes, relation-manager registration                                                                                                                                                                                                                                                                                                                 |
| `Tests\Feature\LabManagementTest`               | 14    | Lab management, relation-manager station creation (header + empty-state actions), station statuses, and U-shape interactive seating-plan assignment, auto-assign, unassign, and persistence                                                                                                                                                                                                                                                                                                                                                                                                   |
| `Tests\Feature\LocalePersistenceTest`           | 5     | Locale persisted when visiting localized panel routes, updated on language switch, restored on `/livewire/update` (post-save redirect URL generation), applied to non-localized URLs, unsupported locales ignored                                                                                                                                                                                                                                                                                                                                                                          |
| `Tests\Feature\LocalizationCompletenessTest`    | 2     | Scans literal `__()`/`trans()`/`@lang()` keys in app, views, routes, and config; requires English/French entries for user-facing strings and Arabic entries for non-Arabic keys                                                                                                                                                                                                                                                                                                                                                                                                            |
| `Tests\Feature\MiddleSchoolSeederTest`          | 21    | Dataset and active-learning strategy seed coverage, official subjects, school identity and holiday calendar, school structure, students, teachers, demo-teacher assignments, deterministic 20-hour schedules, two-week daily logs, authentic curriculum mappings, sequential assessments and balanced learner profiles, attendance distribution/streaks, labs/seating plans, pedagogical follow-up records, destructive wipe, idempotent re-runs, bulk query budget (<400 queries), factory identities                                                                                     |
| `Tests\Feature\MotivationPulseTest`             | 6     | Zero-click badge computation, comeback-champion detection, quick-kudos authorization, teacher/admin hub access, and the A4 certificate route in Arabic/English/French                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `Tests\Feature\MySchedulePageTest`              | 4     | Teacher-only access, day/week views, guest redirect                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| `Tests\Feature\NetStationLabControlTest`        | 6     | NetStation service master lock/unlock all stations, instant broadcast distribution and clearing, internet access toggle, student station client screen render, heartbeat pings, locked overlay, and Leader PC Livewire controls                                                                                                                                                                                                                                                                                                                                                        |
| `Tests\Feature\OfficialReportsTest`             | 19    | Official report page access, timetable/attendance/transcript output, scoping, localization, and PDF routes                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `Tests\Feature\OfflineAttendancePwaTest`        | 12    | PWA assets and panel registration, user-scoped offline storage, guest cache/roster purge, localized sync warning, idempotent multi-student sync, schedule/roster authorization, admin bypass, invalid batch validation, offline controls                                                                                                                                                                                                                                                                                                                                                   |
| `Tests\Feature\ParentPortalTest`                | 4     | Signed 90-day URL validation and rejection of expired tokens, teacher generation for own roster students, group-scoped privacy guard preventing leakage of other sections                                                                                                                                                                                                                                                                                                                                                                                                                  |
| `Tests\Feature\PedagogicalInspectorTest`        | 7     | Curriculum velocity & parallel section pacing radar, teacher pedagogical scorecard & administrative visas, global KPIs & appreciation distribution, admin-only cockpit access with teacher 403, report authorization & owner teacher access with cross-teacher 403, official A4 RTL inspection print rendering, and PDF streamed download                                                                                                                                                                                                                                             |
| `Tests\Feature\PedagogicalMemoTest`             | 21    | Memo relationships/casts, teacher/admin resource scoping, create/edit ownership, admin assignment/required teacher, duplicate into current bank, archive/restore, template/archive filters, policy/print/PDF authorization, localized A4 RTL/LTR print views, guest redirect, unsupported locale, localized labels, print/PDF actions, PDF download                                                                                                                                                                                                                                        |
| `Tests\Feature\QuickAttendanceTest`             | 9     | Quick-attendance access, roster display, attendance saving, group-scoped schedule resolution, required fields, authorization guards, and offline state                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `Tests\Feature\RegisterPrintTest`               | 17    | Authenticated print route, teacher/admin scoping, completed-only filtering, validation, guest redirect, locale-aware Arabic/English/French register output, always-visible PDF bulk action with completed-only server filtering + streamed download, exporter columns/filter, live export progress widget (current-user scoping, percentage, polling, export-start integration, hiding when complete, completion poll does not 403), group section resolution, and owner/admin-only export downloads                                                                                         |
| `Tests\Feature\SajelHealthCheckCommandTest`     | 4     | Fresh database verification, JSON output flag, missing AI key warning, and configured Gemini key pass                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `Tests\Feature\ScheduleOverlapTest`             | 6     | Overlapping/identical teacher-day slots rejected; back-to-back slots allowed; scoping to teacher and day; edit-safe self-exclusion; admin double-booking guard                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `Tests\Feature\ScheduleRosterTest`              | 4     | Classroom-only schedules target all classroom groups; group-scoped schedules target their specific group; group-only schedules resolve roster; empty schedule targets no students                                                                                                                                                                                                                                                                                                                                                                                                           |
| `Tests\Feature\ScheduleSectionNameTest`         | 3     | Section name fallback and formatting for group-scoped and classroom-scoped schedules across timetable, register, and reports                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `Tests\Feature\ScheduleTargetingTest`           | 3     | Explicit schedule targeting rules, classroom vs group level validations, and conflict prevention                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| `Tests\Feature\SchoolBackupRestoreTest`         | 7     | Full database table export, inspect validation, dry-run simulation, live restore with clean wipe, artisan command options, and admin Filament actions                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `Tests\Feature\SchoolHolidayComplianceTest`     | 5     | Holiday date ranges, calendar exclusion from overdue and unlogged compliance checks in supervision hub and unlogged widget, read-only teacher view                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `Tests\Feature\SchoolSettingsTest`              | 5     | Admin-only school identity settings view/update (teacher 403), cache refresh, dynamic school/directorate/headteacher identity on register sheets, class councils, and Official Reports, and admin-only JSON institutional backup export excluding password hashes and tokens                                                                                                                                                                                                                                                                                                             |
| `Tests\Feature\SchoolSupervisionHubTest`        | 6     | Locale-aware admin-only access, weekday/time-based compliance, classroom-scoped attendance and distinct-date repeat absences, behavior follow-up resolution with notes                                                                                                                                                                                                                                                                                                                                                                                                                     |
| `Tests\Feature\StudentManagementTest`           | 8     | Student/group relationships, cascade delete, admin-only resource/navigation, form creation, unique nullable registration, group filter, CSV importer group linking                                                                                                                                                                                                                                                                                                                                                                                                                         |
| `Tests\Feature\StudentReportCardTest`           | 5     | Teacher roster authorization and subject/record scoping, admin full reports, localized print/PDF routes, and Official Reports student picker                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `Tests\Feature\SubstituteTeacherHandoverTest`   | 4     | Substitute assignment model, schedule association, pedagogical continuity handover banner, and temporary permission delegation                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `Tests\Feature\TeacherInteractiveScheduleTest`  | 24    | Rendering, colours, dark mode, day/search/view/expand interactions, complete/cancel, stats, date-aware log navigation, teacher scoping                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| `Tests\Feature\TeacherPortfolioTest`           | 4     | Teacher can view own portfolio, denied other teacher portfolio, admin view all, section resolution from group-scoped schedules                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `Tests\Feature\TeacherSubjectAssignmentTest`    | 6     | Primary/secondary relationships, default weekly limit, teacher form default/restriction, admin teacher-scoped subject options, user-form specialization assignment/editing                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `Tests\Feature\TeacherTimetableTest`            | 17    | Fixed five-day/seven-slot grid, matrix mapping, multi-hour row spans with covered cells (2h, 3h, back-to-back, lunch-break clamp), workload totals and overload badge, equality boundary, empty placeholders, subject-colour mapping (all six palette colours), primary fallback for missing/unknown colours, teacher scoping, current-week click dates, stale-date fallback, date-aware log navigation                                                                                                                                                                                    |
| `Tests\Feature\UnloggedSessionsWidgetTest`      | 4     | Teacher-only visibility, Sunday-to-today missing/planned detection, today's end-time boundary, teacher scoping, completion URLs, five-per-page Livewire pagination with total count, success state                                                                                                                                                                                                                                                                                                                                                                                         |
| `Tests\Feature\WelcomeAndDashboardTest`         | 7     | Welcome page links, login/register access, guest redirect, dashboard stats/quick links, French and Arabic/RTL localization                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |

## 8. Project Health Snapshot

| Aspect          | Status                                                                                                           |
| --------------- | ---------------------------------------------------------------------------------------------------------------- |
| Migrations      | ✅ All 45 migrations ran                                                                                         |
| Test suite      | ✅ 425/425 passing, 0 failures (20,836 assertions in the latest full run)                                        |
| Admin panel     | ✅ 20 Filament resources + custom dashboard, schedule, curriculum-progress, supervision, pulse, data-show, pedagogical-inspector pages |
| Seed data       | ✅ Destructive middle-school dataset + optional SajelSeeder                                                      |
| Version control | Git repo on `main`; Phases 2–11 complete (100%)                                                                  |
| README          | ✅ Project-specific bilingual README (English structure + Arabic domain)                                         |
| `APP_NAME`      | ✅ `Sajel`; `APP_URL` = `https://sajel.dev/`                                                                     |
| README          | ✅ Project-specific bilingual README (English structure + Arabic domain)                               |
| `APP_NAME`      | ✅ `Sajel`; `APP_URL` = `https://sajel.dev/`                                                           |

## 9. Phase 2 Completion Note

All 14 Phase 2 items are completed (`100%`). Previous remaining gaps (official reports, lab management, classroom toolbar, quick attendance, behavior observations, active learning strategies, data show, pedagogical remediation) were implemented, tested, and committed (`main`).

No further Phase 2 work is required. Phase 3 is now complete; see the Phase 3 roadmap below. The project README (`README.md`) documents the complete feature matrix.

## 10. Phase 2 Roadmap (Step-by-Step Tracking Checklist)

> **قاعدة التتبع:** عند إكمال أي خطوة من هذه القائمة، يجب تحديث حالتها إلى `[x]` في هذا الملف بعد التحقق من نجاح جميع الاختبارات عبر `php artisan test` وتنسيق الكود عبر `vendor/bin/pint`.

**Baseline:** All 14 Phase 2 roadmap items complete (100%). This section remains as historical reference only.

| Axis                                    | Done   | Remaining | Progress |
| --------------------------------------- | ------ | --------- | -------- |
| 1. Planning, Curriculum & Reports       | 5      | 0         | 100%     |
| 2. Lab & Student Management             | 5      | 0         | 100%     |
| 3. Classroom Activities & Presentation  | 3      | 0         | 100%     |
| 4. Assessment & Pedagogical Remediation | 1      | 0         | 100%     |
| **Total**                               | **14** | **0**     | **100%** |

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
ring-inset ring-amber-500/40 dark:ring-amber-400/40` with `text-amber-950 dark:text-amber-100`
> text colors; all table cells use `text-gray-900 dark:text-gray-100`; rows are clickable via
> `wire:click="showDetail(...)"` to load domain/sequence/resource detail.

> **تم إنجاز التقارير والاستمارات الرسمية:** `OfficialReportService` + `OfficialReportController` +
> `OfficialReports` Filament page + three print views (`print.timetable`, `print.attendance-summary`,
> `print.grade-transcript`) with six routes (print + PDF for each), verified by
> `OfficialReportsTest` (19 tests). No migration required. A4 RTL, teacher scoping enforced,
> translations added to `lang/ar.json`, `lang/en.json`, and `lang/fr.json` (37 new keys).
> Dark mode contrast fixed: teacher box uses `bg-gray-50 border border-gray-200 dark:bg-gray-800/90
dark:border-white/10` with `text-gray-950 dark:text-white` name value; all select inputs use
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

- [x]   1. Create `app/Livewire/DashboardStats.php` — Livewire component using Filament theme colors (primary, secondary, success, warning, info, danger) with dark/light-aware Tailwind classes
- [x]   2. Create `resources/views/livewire/dashboard-stats.blade.php` — stat cards with `text-gray-900 dark:text-white` and `bg-white dark:bg-gray-800` etc.
- [x]   3. Update `app/Filament/Pages/Dashboard.php` — import the Livewire component instead of `SajelStats` widget
- [x]   4. Remove/replace `SajelStats` widget (dark-mode text visibility issue)
- [x]   5. Verify dark mode: stat card titles, values, descriptions, and chart colors all readable
- [x]   6. Run `php artisan test` — all tests pass
- [x]   7. Run `vendor/bin/pint` — code formatted
- [x]   8. Update `STATUS_PROJECT.md` with completion notes

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
    - `.pdf`; `ClassroomPolicy` enforces admin/teacher scoping.
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

Status 2026-11-04: `410 passed`, **0 failures** (`20,753` assertions); `pint` passed; `npm run test:js` passed (8 tests); `npm run build` passed; health command reports PASS on database, schema (25 tables), timezone, localization, PWA, and AI (`Provider: gemini (gemini-3.8-flash) | Key: configured`). All Phase 2–10 milestones complete (100%). The offline attendance PWA now scopes browser storage by user and clears cached attendance HTML and roster snapshots for guests while preserving a pending queue for the same teacher. Localization is complete across `ar`/`en`/`fr` for user-facing UI and print/PDF labels; the official register and student certificate follow the active locale (Arabic remains the default RTL output), and `LocalizationCompletenessTest` guards against missing keys. The dashboard is now a role-aware, URL-stateful hub with server-scoped metrics and explicit definitions; see README §10.

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
- **Verification:** `tests/Feature/SchoolSettingsTest.php` (5 tests, 26 assertions) covers admin
  view/update, teacher 403, and identity rendering on register sheets, class councils, Official
  Reports, and JSON backup generation. Health check now requires 25 tables including `school_settings`.

## 17. Phase 8 Roadmap — Institutional Backup & CI (100%)

Phase 8 guarantees institutional disaster recovery, configuration persistence, and automated quality assurance on every commit.

- [x] **School Backup Service (`SchoolBackupService`):**
      Creates a timestamped snapshot (`sajel-backup-YYYY-MM-DD.json`) of the school's active database tables (24+ academic domain tables including students, classrooms, schedules, daily logs, attendances, assessments, grades, memos, and curriculum maps) along with table row counts and institutional metadata.
- [x] **Sensitive Data Protection:**
      The backup engine explicitly unsets all authentication and security secrets (`password`, `remember_token`, `api_token`, sessions) prior to JSON streaming.
- [x] **Filament Header Action:**
      Added an Admin-only header download action `"تحميل نسخة احتياطية للمؤسسة (JSON)"` on the `ManageSchoolSettings` page, with streamed JSON attachment delivery.
- [x] **Automated Continuous Integration (`.github/workflows/ci.yml`):**
      GitHub Actions workflow on `main` branch push and pull requests with PHP 8.3 (with `pdo_sqlite`, `gd`, `intl`, `mbstring`, `dom`, `xml`, `zip`), Node 20, composer/npm caching, in-memory SQLite migrations & seed, frontend Vite compilation, JavaScript test suite, Pint formatting verification, Sajel system health check, and full PHP test suite execution.
- [x] **Verification:**
      Covered by `SchoolSettingsTest::test_admin_backup_download_excludes_password_hashes`, CI workflow, and full test suite passing with 0 errors.

## 18. Phase 9 Roadmap — Sajel Pulse Motivation System (نبض التميز والتحفيز التربوي) (100%)

Phase 9 implements an integrated pedagogical gamification and motivation engine (نبض التميز والتحفيز التربوي) designed to reward effort, attendance consistency, academic growth, and positive classroom behavior.

- [x] **Student Motivation Service (`StudentMotivationService`):**
      Computes real-time student motivation profiles without manual data entry using existing academic domain data:
      - **Zero-Click Badges:**
        - `attendance_streak` (سلسلة الحضور الذهبية): Earned for 5 or more distinct present days in the recent window with 0 absences and 0 lates.
        - `comeback_champion` (بطل التحسن / العودة القوية): Awarded for notable academic leaps between consecutive assessments (e.g. +3.0 points or crossing the pass threshold).
        - `academic_excellence` (فرسان التميز): Awarded for achieving an overall or subject average of 16.0/20 or higher.
        - `positive_role_model` (القدوة الحسنة): Awarded for logged positive behavior observations with 0 negative conduct flags.
      - **Student Pulse Score:** A dynamic 0–100 engagement index aggregating attendance reliability, grade trends, and behavioral accolades.
      - **Classroom Synergy Meter (`⚡ طاقة تفاعل القسم`):** A collective classroom metric (0–100%) factoring in weekly attendance rates, positive kudos awarded, and confirmed daily logs.
      - **Podium Spotlight Champions:** Identifies the top 3 weekly classroom standouts across Excellence, Comeback, and Attendance.
- [x] **Data Show Mode Synergy & Quick Kudos (`DataShowMode`):**
      - Displays the live interactive Classroom Synergy bar (`⚡ طاقة تفاعل القسم`) on the full-screen projector view.
      - Provides an instant, one-click `awardQuickKudos(studentId, reason)` action for teachers during live presentations, immediately logging a positive `BehaviorObservation`, incrementing classroom synergy, dispatching a Livewire event, and showing on-screen visual confirmation.
- [x] **Motivation Pulse Hub (`MotivationPulseHub`):**
      - Custom Filament dashboard page at `/{locale}/auth/motivation-pulse` (icon: `heroicon-o-sparkles`, group: `التخطيط والمناهج`).
      - Accessible by both teachers and administrators.
      - Includes classroom selector, live synergy meter widget, weekly champions podium, and searchable student motivation table with badges, pulse score, and direct certificate print links.
- [x] **Official A4 RTL Certificate of Educational Excellence (`print.student-certificate`):**
      - High-fidelity printable A4 certificate and downloadable PDF route (`/{locale}/print/students/{student}/certificate` + `.pdf`).
      - Fully localized in Arabic, English, and French (`Certificate of Excellence`, `Certificat d'excellence`).
      - Injects official school identity headers from `SchoolSetting`, student details, dynamic badges with descriptions, and signature boxes for the subject teacher and school principal.
      - Authorized via `StudentPolicy::view` (teachers scoped to their scheduled classrooms/groups; administrators school-wide).
- [x] **Cross-Platform Integration:**
      - Motivation badges surfaced directly on the official Student Comprehensive Report Card (`StudentReportCardService`).
      - Motivation badges displayed on the 90-day Signed Parent Portal (`ParentPortalService`).
- [x] **Localization & Verification:**
      - All keys registered across `ar.json`, `en.json`, and `fr.json`.
      - Comprehensive automated test suite in `tests/Feature/MotivationPulseTest.php` (6 tests, 21 assertions, all green, 0 deprecation warnings).

## 19. Phase 10 Roadmap — Disaster Recovery & Backup Restoration (100%)

Phase 10 completes the institutional resilience suite with a robust, bidirectional backup restoration and disaster recovery engine.

- [x] **Full Table Snapshot (`SchoolBackupService::fullSnapshot`):**
      Expanded `SchoolBackupService` to capture all 26 core academic domain tables in foreign-key dependency order (school settings, structure, users, curriculum maps, students, schedules, daily logs, attendances, assessments, grades, memos, meetings, remediations, observations, labs, and seating plans), with sensitive credentials (`password`, `remember_token`, `api_token`) strictly redacted.
- [x] **School Restore Service (`SchoolRestoreService`):**
      Engine supporting payload validation, simulation, and transactional database restoration:
      - `inspect()`: JSON structural validation, schema presence verification, academic year comparison warnings, and table row tallies.
      - `restore()`: Database transaction with foreign-key safety (`PRAGMA foreign_keys = OFF / ON`), clean wipe capability (`wipeExisting`), preserved current admin account, non-destructive user account matching, and automatic cache invalidation (`SchoolSetting::flushCache`).
      - `--dry-run`: Comprehensive simulation mode reporting exact affected table row counts without database writes.
- [x] **Artisan CLI Command (`php artisan sajel:restore`):**
      Full CLI restoration command (`sajel:restore {file} {--dry-run} {--wipe} {--force}`) with colored summary tables, inspection output, confirmation prompts, and timing metrics.
- [x] **Filament Admin UI Integration (`ManageSchoolSettings`):**
      Added `"استرجاع نسخة احتياطية (JSON)"` header action with a modal file uploader, dry-run toggle, clean wipe toggle, detailed notification feedback, and form state refresh.
- [x] **Multilingual Support & Verification:**
      All modal, notification, and CLI messages localized across Arabic, English, and French. Fully covered by `tests/Feature/SchoolBackupRestoreTest.php` (7 tests, 64 assertions, 0 failures).

## 20. Phase 11 Roadmap — Pedagogical Inspector Cockpit (فضاء المفتش التربوي ومتابعة المنهاج) (100%)

Phase 11 equips educational inspectors and school principals with a specialized, institutional supervision cockpit for curriculum velocity tracking, parallel section pacing radar, teacher compliance scorecards, and official A4 RTL inspection visit reports.

- [x] **Database Schema & Eloquent Domain Models (`inspection_reports`):**
      - Migration `2026_10_26_010001_create_inspection_reports_table.php` adding the 26th core domain table.
      - Eloquent model `InspectionReport` with casts for evaluation criteria (`criteria_scores`), visit date, scores, and appreciation enums (`excellent`, `very_good`, `good`, `fair`, `needs_followup`).
      - Factory `InspectionReportFactory` with realistic Arabic educational visit observations and Algerian pedagogical criteria.
      - Integrated into `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` (26 verified tables).
- [x] **Domain Service (`PedagogicalInspectionService`):**
      - **Curriculum Velocity & Section Pacing Radar (`getCurriculumVelocity`):** Real-time comparative analysis between parallel sections of the same subject and grade level (e.g. `1 م 1` vs `1 م 2`), flagging sections as `ahead`, `on_track`, or `lagging` ($\ge 2$ resources gap).
      - **Teacher Pedagogical Scorecard (`getTeacherPedagogicalScorecard`):** Aggregates confirmed daily logs, administrative visa coverage rates, total inspection visits, and average pedagogical scores.
      - **Global Inspection KPIs (`getInspectionMetrics`):** Macro-level metrics summarizing visit counts, teacher coverage %, average pedagogical mark, and appreciation distribution.
      - **A4 RTL Document & PDF Generation:** High-fidelity HTML view compilation and headless PDF generation via Browsershot.
- [x] **Inspector Cockpit Filament Page (`PedagogicalInspectorHub`):**
      - Route `/{locale}/auth/pedagogical-inspector` (icon: `heroicon-o-academic-cap`, group: `التخطيط والمناهج`).
      - Admin-only access guard (strictly forbidden to teachers with 403).
      - 3 interactive tabs:
        1. **Curriculum Velocity Radar (رادار توازي الأقسام والتدرج):** Subject and level filters, completion progress bars, pacing tags, resource gaps, and leading section callouts.
        2. **Teacher Compliance & Scorecards (سجل امتثال وتأشيرات الأساتذة):** Textbooks logged, administrative visa %, inspection history, and latest pedagogical notes.
        3. **Official Inspection Visits Register (سجل الزيارات والتقارير الرسمية):** Archive of official visit reports with direct A4 print and PDF download buttons.
      - Modal Action `"تسجيل زيارة تفتيشية جديدة"` with auto-suggested lesson topics, criteria sliders, scores, qualitative appreciations, strengths, and pedagogical recommendations.
- [x] **Official A4 RTL Inspection Report (`print.inspection-report`):**
      - Route `/{locale}/print/inspection-reports/{inspectionReport}` and downloadable stream `.../pdf`.
      - Strict policy authorization (`InspectionReportPolicy`): accessible to administrators and the specific evaluated teacher; cross-teacher access denied (403).
      - Official Algerian ministerial header, school identity from `SchoolSetting`, inspector credentials, 4-criteria pedagogical evaluation grid, qualitative appreciation badge, and formal signature/visa boxes (Teacher, Principal, National Education Inspector).
- [x] **Multilingual Support & Full Test Coverage:**
      - Complete Arabic, English, and French translations across `ar.json`, `en.json`, and `fr.json`.
      - Comprehensive automated test suite in `tests/Feature/PedagogicalInspectorTest.php` (7 tests, 39 assertions, 100% green). All 417 application tests pass with 0 regressions.

## 21. NetStation Suite — Computer Lab Central Control & Student Kiosk System (منظومة التحكم بمخابر الإعلام الآلي) (100%)

Transforms the computer lab management experience into an integrated classroom management suite (NetStation / LanSchool / NetSupport model) allowing teachers to command all student stations directly from their Leader PC:

- [x] **Database Schema & Model Extensions (`lab_stations`):**
      - Migration `2026_10_26_020001_add_netstation_controls_to_lab_stations_table.php` adding `ip_address`, `is_locked`, `lock_message`, `active_broadcast`, `internet_enabled`, and `last_ping_at`.
      - Model methods on `LabStation`: `isOnline()`, `lock()`, `unlock()`, `broadcast()`, `isComputer()`.
- [x] **Domain Service (`NetStationService`):**
      - `lockAll()` and `unlockAll()` for whole-lab instant pedagogical control.
      - `lockStation()` and `unlockStation()` for single-station control.
      - `broadcastMessage()` and `clearBroadcast()` for real-time guidance alerts.
      - `toggleInternet()` to centrally restrict or allow web browsing per lab.
      - `pingStation()` for live client telemetry and status reporting.
- [x] **Leader PC Live Cockpit (`LabSeatingPlanPage` & `LabSeatingPlan`):**
      - Dual-mode switcher: **وضع التحكم المباشر (NetStation)** vs **وضع تخطيط الجلوس**.
      - High-tech Leader PC desk ("الحاسوب الرئيسي للأستاذ") with master command bar: Lock All, Unlock All, Broadcast Message Modal, and Internet Toggle.
      - Realistic PC monitor cards displaying station number, IP address, online pulse LED, lock state, and assigned student.
      - Individual quick lock/unlock buttons and instant client kiosk preview links.
- [x] **Student Station Kiosk Client (`/{locale}/lab/station/{id}`):**
      - Interactive fullscreen client view for student computers.
      - Real-time heartbeat polling (`ping`) synchronizing lock state and broadcasts every 3 seconds.
      - Fullscreen lock overlay with glowing lock icon, warning banners, and custom teacher messages ("انتبه للأستاذ!").
      - Instant broadcast alert banner with acknowledgment button.
      - Live connection indicator ("متصل بالحاسوب الرئيسي 🟢") and educational launchpad.
- [x] **Testing & Localization:**
      - Test suite in `tests/Feature/NetStationLabControlTest.php` (6 tests, 31 assertions, 100% green).
      - Complete translations in Arabic, English, and French.

## 22. Science Laboratory Benches & Experiment Safety System (طاولات ومحطات التجارب المخبرية وضوابط السلامة) (100%)

Adapts the laboratory seating and management workflow dynamically based on the Algerian Middle School curriculum discipline:

- [x] **Domain Models & Discipline Classification (`Lab`, `LabStation`):**
      - Added helpers: `isInformatics()`, `isPhysics()`, `isNaturalScience()`, `labType()`, `labTypeLabel()`, `stationLabel()`, and `LabStation::isComputer()`.
      - Correctly differentiates computer labs (PC network, NetStation controls) from science laboratories (Physics & Chemistry, Biology / Natural Sciences & Life / SVT).
- [x] **6 Long-Tables Laboratory Floor Plan (6 طاولات كبرى × 4 مقاعد = 24 مقعد):**
      - Modeled directly after Algerian Middle School physical science laboratories (as photographed): 6 long double workbenches arranged into two aisles (Right Aisle: Tables 1–3, Left Aisle: Tables 4–6).
      - Each Long Table (`science-long-table.blade.php`) seats 4 students: 2 on Side A (front) and 2 on Side B (opposite side facing each other).
      - Central Service Bridge (*Dosseret technique*) down the center of each table with 220V power sockets (⚡), yellow gas taps (🟡), water sink & faucets (🚰), and microscope/reagents rack (🔬).
      - Individual seat cards (`science-table-seat.blade.php`) with seat numbers `#01`–`#24`, assigned student avatar/name, quick unassign, and kiosk link.
      - Student roster cards display precise seating location: `طاولة :table • مقعد :seat`.
- [x] **Teacher Demonstration & Preparation Desk (منصة تحضير وعرض التجارب للأستاذ):**
      - Prominently placed at the front facing both aisles with whiteboard/direction indicator (`جهة السبورة ومنصة العرض للأستاذ ⬆️`).
      - Safety and experiment readiness status chips (cotton lab coats mandatory, emergency breakers checked, reagents prepared).
      - Actions: `توزيع تلقائي للأفواج` (fills students in groups of 4 per table), `تفريغ الطاولات`, `ميثاق السلامة`, and `حفظ المخطط`.
- [x] **Laboratory Safety Charter Modal (`ميثاق السلامة والأمن المخبري`):**
      - Discipline-specific safety protocol based on Algerian Middle School regulations (المنشور الوزاري للسلامة في المخابر التعليمية):
        - Physics & Chemistry: Electrical circuit breaker protocol, handling reagents and glassware, emergency shutoffs, and post-experiment hand hygiene.
        - Biology & Natural Sciences (SVT): Careful microscope transport and lens care, bubble-free 45° coverslip placement, anatomical model conservation, and workbench disinfection.
- [x] **Digital Experiment Bench Kiosk Screen (`resources/views/lab/student-station.blade.php`):**
      - For science benches, renders a digital experiment bench card and safety guide instead of PC screen locks.
      - Displays bench number, assigned student details, safety protocol cards, and an interactive checklist before leaving the lab.
- [x] **Testing & Localization:**
      - Added tests in `tests/Feature/LabManagementTest.php` verifying science lab 6-table workbench switching, lack of PC controls, and experiment bench kiosk rendering.
      - Translations added to `lang/en.json` and `lang/fr.json` (100% covered by `LocalizationCompletenessTest`).
      - Full suite: **427 tests, 20,856 assertions, 0 failures, 100% green**.

## 23. Daily Logs Pedagogical Ownership & Administrative Visa Policy (حصرية تدوين دفتر النصوص للأساتذة والتأشيرة الإدارية) (100%)

Enforces strict Algerian institutional pedagogical boundaries regarding the digital class register / daily lesson logs (دفتر النصوص الرقمي):

- [x] **Exclusive Creation Rights for Teachers (`DailyLogPolicy`, `DailyLogResource`):**
      - `DailyLogPolicy::before()` explicitly denies the `create` ability to administrators/moderators.
      - `DailyLogPolicy::create()` verifies `$user->isTeacher()`.
      - `DailyLogResource::canCreate()` returns `false` for admins, automatically hiding the "New Daily Log" action from administrators and returning HTTP 403 Forbidden on `/daily-logs/create`.
- [x] **Administrative Review & Digital Visa (`administrativeVisaTableAction`, `digitalVisa`):**
      - Administrators and superadmins retain read/view permissions across all teachers' daily logs.
      - Administrators review completed lesson logs and stamp official administrative visas with pedagogical notes via `administrativeVisaTableAction()` and bulk visa action.
      - Added `Tables\Actions\ViewAction::make()` to table row actions for non-destructive log inspection.
- [x] **Full Test Coverage:**
      - Verified in `tests/Feature/AuthorizationTest.php` (`test_admin_and_moderator_cannot_create_daily_logs_which_is_reserved_for_teachers`).
      - All 14 tests in `tests/Feature/DigitalVisaTest.php` passing green.

## 24. Navigation & Link Visibility Hardening Across Roles (حجب الروابط والأزرار غير المصرح بها) (100%)

Strictly hides all inaccessible navigation items, sidebar groups, quick links, table actions, and dashboard buttons to prevent any HTTP 403 Forbidden encounters for users lacking permissions:

- [x] **Page Navigation Visibility Synchronization (`shouldRegisterNavigation()`):**
      - Synchronized `shouldRegisterNavigation()` across all Filament Pages (`MySchedule`, `QuickAttendance`, `LabSeatingPlanPage`, `ManageSchoolSettings`, `PedagogicalInspectorHub`, `SchoolSupervisionHub`, `ClassCouncilHub`, `ClassroomToolbar`, `CurriculumProgress`, `DataShowMode`, `MotivationPulseHub`, `OfficialReports`).
      - Ensured teacher-only pages (`MySchedule`, `QuickAttendance`) are strictly hidden from admin navigation (`shouldRegisterNavigation()` and `canAccess()` return `false` for admins).
      - Ensured admin-only pages (`ManageSchoolSettings`, `PedagogicalInspectorHub`, `SchoolSupervisionHub`, `LabSeatingPlanPage`) are strictly hidden from teacher navigation (`shouldRegisterNavigation()` and `canAccess()` return `false` for teachers).
      - Grouped `ClassCouncilHub` neatly under `__('التخطيط والمناهج')` with explicit access control (`isAdmin() || isTeacher()`).
- [x] **Resource Navigation Visibility Hardening:**
      - Added explicit `shouldRegisterNavigation(): bool { return auth()->user()?->isAdmin() ?? false; }` to `UserResource` to ensure consistent sidebar omission for non-administrators.
      - Core academic structure resources (`ClassroomResource`, `LevelResource`, `SubjectResource`, `EducationPhaseResource`, `GroupResource`, `LabResource`, `StudentResource`, `CurriculumDomainResource`, `CurriculumSequenceResource`, `CurriculumKnowledgeResource`) are strictly hidden from teacher navigation.
- [x] **Daily Log Action Visibility Scoping (`DailyLogResource`):**
      - Restricted `confirmCompletionTableAction` and header action exclusively to the lesson's teacher: `Auth::user()?->isTeacher() && (Auth::id() === $record->schedule?->user_id || Auth::id() === $record->substitute_teacher_id)`. Administrators only view or apply administrative visas; they never see "Confirm Completion".
- [x] **Dashboard Links & Pending Sessions Guarding:**
      - In `lessons.blade.php`: "إنشاء سجل يومي" (New Daily Log) button guarded with `@can('create', \App\Models\DailyLog::class)`.
      - In `smart-log.blade.php`: Create button guarded with `@can('create', \App\Models\DailyLog::class)`, gracefully falling back to "عرض دفتر النصوص" (View class register) for administrators.
      - In `PendingSessionService::forUser`: For missing daily logs, URL is only set to `DailyLogResource::getUrl('create', ...)` when the user can create daily logs (`$user->can('create', DailyLog::class)`), otherwise `null`.
      - In `overview.blade.php` and `lessons.blade.php`: Pending items for administrators with missing logs show a clean informative badge `بانتظار تحضير الأستاذ` (Awaiting teacher entry) rather than an invalid link pointing to HTTP 403.
- [x] **Localization & Test Suite:**
      - Added translations for all newly introduced strings (`بانتظار تحضير الأستاذ`, `معاينة السجل`, `عرض دفتر النصوص`) to `lang/en.json` and `lang/fr.json`.
      - Verified full suite: **431 passed tests, 20,893 assertions, 0 failures, 100% green**.

## 25. Teacher Schedule Binding, Live Attendance Tracking & Laboratory Pedagogical Protocols (ربط مخطط المخبر بالحصص والحضور التفاعلي وبروتوكول التجارب) (100%)

Enables middle school science and informatics teachers to seamlessly link laboratory sessions with their weekly timetable, take live 1-click attendance from seating benches, and broadcast active experiment protocols:

- [x] **Role-Based Navigation & Page Access (`LabSeatingPlanPage`):**
      - Updated `canAccess()` to allow access for both Teachers and Admins: `$user?->isAdmin() || $user?->isTeacher()`.
      - Updated navigation group to `__('التشغيل اليومي')` (`Daily Operations`) for intuitive discovery during teaching periods.
      - Tested in `AuthorizationTest` to ensure visibility and access for teachers and admins alike.
- [x] **Schedule Slot Binding & Auto-Resolution (`LabSeatingPlan.php`):**
      - Bound query parameters `#[Url(as: 'schedule')] public ?int $scheduleId` and `#[Url(as: 'date')] public string $attendanceDate`.
      - Implemented automatic resolution of matching laboratory (Physics/Chemistry, Biology/Natural Sciences, Informatics) based on schedule subject.
      - Automatically sets classroom/group from the selected schedule.
      - Resolves or safely creates the session's active `DailyLog` record for today's lesson.
- [x] **Live Interactive Attendance from Bench Seats & PC Monitors:**
      - Added 1-click attendance toggle on bench seats (`science-table-seat.blade.php`) and PC monitor cards (`station-monitor-card.blade.php`): cycling through Present (حاضر ✔️), Absent (غائب ❌), Late (متأخر ⏳), and Excused (معفى 📝).
      - Immediate real-time persistence to `attendances` table linked to the active `daily_log_id`.
      - Real-time visual feedback: color-coded borders and backgrounds for absent (rose), late (amber), excused (sky), and present (teal).
      - Instant KPI summary counters strip for Live Attendance: present count, absent count, late count, and excused count.
      - "تعيين الجميع حاضراً" (Mark All Present) quick action button.
- [x] **Pedagogical Experiment Protocol Modal & Student Station Kiosk Cards:**
      - Added "بروتوكول التجربة" modal in the teacher's cockpit displaying experiment title, lesson steps, safety instructions, and a direct link to the class register (دفتر النصوص).
      - Updated `StudentStationController` to resolve today's scheduled lesson daily log (`$todayLog`).
      - Rendered "بطاقة التجربة البيداغوجية للحصة" on student kiosks (`student-station.blade.php`) for both science benches and informatics PC stations, displaying experiment objective, supervisor teacher, and required tasks.
- [x] **Comprehensive Testing & Verification:**
      - Added 4 new feature tests in `LabManagementTest`:
        - `test_lab_seating_plan_loads_schedule_and_prefills_group_and_lab`
        - `test_interactive_attendance_toggle_from_seating_plan_syncs_to_daily_log`
        - `test_mark_all_present_action_in_lab_seating_plan`
        - `test_student_station_kiosk_displays_active_experiment_protocol_for_today`
      - Verified `LocalizationCompletenessTest` is 100% green with complete Arabic, English, and French translations.
      - Verified full test suite: **435 passed tests, 20,920 assertions, 0 failures, 100% green**.
      - Verified clean Pint formatting across all code files.

## 26. Exam Timetable Hub, Conflict Guard & Official Algerian Middle School Schedule Print (رزنامة الفروض والامتحانات ومنع تضارب الاختبارات) (100%)

Enforces Algerian Ministry of National Education scheduling regulations for midterms and exams, guards against student test overload and teacher double-booking, and provides a central interactive timetable hub and official printable A4/PDF schedules:

- [x] **Algerian Ministerial Conflict Guard Service (`ExamConflictService`):**
      - **Maximum 2 Major Tests Per Day Rule (`max_daily_assessments`):** Enforces Algerian middle school ministerial directive (`لا يجوز برمجة أكثر من فرضين محروسين في نفس اليوم للقسم الواحد`), strictly preventing any classroom from having more than two quizzes/midterms/exams on any calendar date.
      - **Time-Slot Overlap Collision Guard (`time_overlap`):** Detects overlapping start and end times for scheduled assessments targeting the same classroom section or student group.
      - **Teacher Double-Booking Collision Guard (`teacher_double_booking`):** Flags cross-classroom collisions when a teacher is assigned an assessment overlapping another of their scheduled assessments at the same time.
      - **Trimester Date Range & Group Scoping:** Automatically determines the Algerian trimester calendar bounds (1st: Sep–Dec, 2nd: Jan–Mar, 3rd: Apr–Jun) and filters or aggregates assessments across class groups.
- [x] **Reactive Assessment Form Validation & Conflict Callout (`AssessmentResource`):**
      - Reactive scheduling fields (`schedule_id`, `type`, `date`) trigger live conflict analysis via `afterStateUpdated`.
      - Dynamic visual callout banner (`Placeholder('conflict_status')`): displays a reassuring green badge when all conflict checks pass, or an amber/rose alert summary specifying collision reasons when conflicts occur.
      - Form validation rule rejecting creation or modification if a classroom exceeds 2 major assessments on the target date.
- [x] **Exam Timetable Hub (`ExamTimetableHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-calendar-days` and URL binding for `classroom_id`, `trimester`, and `assessment_type`.
      - Role-scoped classroom selector: teachers only view their assigned classes, while administrators and directors view all school classes.
      - Responsive summary KPI cards: Total Scheduled Assessments, Midterms & Exams count, Distinct Subjects, and Active Conflicts.
      - Active Conflict Alert banner with quick rescheduling shortcuts and conflict explanations.
      - Chronological date-grouped calendar view featuring daily assessment load badges, subject color pills, time slots, teacher signatures, and direct gradebook links.
- [x] **Official Algerian A4 Printable Timetable & PDF (`ExamTimetableController`):**
      - Formatted strictly according to Algerian Ministry of National Education administrative layout: Republic header, Algerian coat of arms / school logo, academic year, classroom title, structured table columns (Date, Day, Time Slot, Subject, Assessment Type, Teacher, Notes).
      - Prominent pedagogical regulation warning note citing the official limit of 2 assessments per day.
      - Official tripartite approval block: Classroom Delegate (مندوب القسم), Director of Studies / Principal (مدير المؤسسة / ناظر الدروس), and Official Administrative Stamp (الختم الرسمي).
      - Localized printable web view (`print.exam-timetable`) and streamed PDF download (`print.exam-timetable.pdf`) using Spatie Browsershot.
- [x] **Comprehensive Testing & Localization:**
      - Added 9 feature tests in `tests/Feature/ExamTimetableTest.php` covering access permissions, rule violation detection, form validation, page rendering, and printable/PDF routes.
      - 100% complete Arabic, English, and French translation keys (61 keys added and verified green in `LocalizationCompletenessTest`).
      - Full test suite: **444 passed tests, 20,954 assertions, 0 failures, 100% green**.

## 27. Student Awards & Merits Hub, Official Algerian Certificates & Bulletin Board Honor Roll (نظام التكريم والشهادات المدرسية ولوحة الشرف) (100%)

Automatically evaluates student assessment averages, conduct observations, and attendance streaks at the end of each trimester or the whole academic year, allocating official honors and rendering ornate A4 landscape certificates and bulletin-board honor roll sheets:

- [x] **Pedagogical Award Categorization (`StudentAwardType`):**
      - **Excellence (شهادة امتياز):** Awarded for general average $\ge 16.0/20$ and 0 negative conduct observations.
      - **Encouragement (شهادة تشجيع):** Awarded for general average $\ge 14.0/20$ and $< 16.0$ (or $\ge 16.0$ with minor behavioral remarks).
      - **Honor Roll (لوحة شرف):** Awarded for general average $\ge 12.0/20$ and $< 14.0$.
      - **Good Conduct (شهادة حسن سيرة وسلوك):** Awarded for 0 unexcused absences, 0 negative conduct observations, and high discipline/positive participation.
- [x] **Automated Deliberation & Merit Engine (`StudentAwardService`):**
      - Evaluates all students in the classroom or group by trimester (1st: Sep–Dec, 2nd: Jan–Mar, 3rd: Apr–Jun, or full academic year).
      - Computes weighted subject averages (/20 scale) and overall classroom general average.
      - Ranks students in descending average order with tie-breakers (positive behavior count, attendance rate, alphabetical).
      - Computes classroom KPIs: total students, graded count, total honored students, excellence count, encouragement count, honor roll count, good conduct count, and highest classroom average.
      - Generates official Algerian pedagogical commendation citations customized to student rank, trimester, and award type.
- [x] **Interactive Filament Cockpit (`StudentAwardsHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-academic-cap`.
      - Live URL bindings for classroom, trimester, group, and award type filter.
      - 6 summary KPI cards with distinction counts and class averages.
      - Ranked roster display featuring gold (1st), silver (2nd), and bronze (3rd) rank badges, student demographics, averages, honor pills, and 1-click print and PDF export actions.
      - Header actions for bulk printing all certificates, bulk PDF export, and printing the notice board honor roll sheet.
- [x] **Official Algerian A4 Landscape Certificates & Notice Board Sheet:**
      - **`print.student-award.single` & `print.student-awards.batch`:** Landscape A4 ornate Islamic geometric double-bordered certificates featuring the Algerian Republic header, Directorate of Education, school name, academic year, bold student dedication, tailored pedagogical citation, academic average and rank badges, and tripartite approval block (Head Teacher, Education Advisor / Director of Studies, Principal & Official Seal).
      - **`print.classroom-honor-roll`:** Official Algerian school notice-board honor roll sheet for classroom bulletin display, summarizing class performance and listing honored students in order of merit.
      - Localized streaming PDF downloads (`.pdf` routes) powered by Spatie Browsershot.
- [x] **Comprehensive Testing & Complete Tri-Lingual Localization:**
      - Added 12 feature tests in `tests/Feature/StudentAwardsTest.php` covering access permissions, average calculations, award eligibility rules, trimester scoping, layout rendering, and PDF streams.
      - Added 89 translation keys covering Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **456 passed tests, 21,009 assertions, 0 failures, 100% green**.

## 28. Digital School Archive & Bound Annual Register Book (أرشيف المؤسسة الرقمي وتصدير دفاتر النصوص السنوية المجلدة) (100%)

Assembles, audits, and exports the complete class register (دفتر النصوص) as an official bound annual digital book for any classroom, subject, or academic trimester in compliance with Algerian Ministry of National Education pedagogical archival directives:

- [x] **Annual Register Book Assembly & Pedagogical Engine (`SchoolArchiveService`):**
      - **Official Commemorative Hardcover:** Automatically generates an authentic Algerian administrative cover page with the Republic header, Directorate of Education, school identity, academic year, classroom, subject, supervising teachers, and unique digital archive registration code (`ARCH-{year}-{classId}-{subjectId}`).
      - **Curriculum Domain & Progress Index:** Formats an exhaustive table of all taught knowledge resources mapped to their parent learning sequences and domains, calculating the exact percentage of syllabus completion against the official ministerial curriculum.
      - **Chronological Register Ledger:** Structures completed daily logs chronologically across trimesters and months with detailed pedagogical lesson phases, actual content delivered, homework assignments, and attendance tallies.
      - **Administrative & Inspection Visa Ledger:** Compiles an official audit ledger of all administrative stamps and pedagogical inspection visas with inspection dates, reviewer credentials, and instructional directives.
      - **Annual Statistical Balance Sheet:** Calculates total instructional sessions, delivered teaching hours, homework frequency, distinct subjects/teachers, and overall student attendance rate.
      - **Official Archival Attestation & Closing Signatures:** Formats the legal Algerian closing minutes and tripartite/quadripartite sign-off block (Subject Teacher, Director of Studies, Middle School Inspector, Principal & Official Seal).
- [x] **School Archive Hub Cockpit (`SchoolArchiveHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-archive-box`.
      - Live URL bindings for classroom, subject, trimester, and teacher selectors.
      - 6 summary KPI cards: Total Completed Sessions, Delivered Teaching Hours, Covered Knowledge Resources, Syllabus Completion %, Administrative Visas Count, and General Attendance Rate.
      - Comprehensive preview tabs for curriculum domain distribution, chronological lesson logs, and administrative inspection visas.
      - 1-click header actions for viewing the bound A4 book, exporting the full PDF, and printing archival records.
- [x] **Official Algerian A4 Book Print Layout & Streaming PDF (`SchoolArchiveController`):**
      - Web print view (`print.school-archive.book`) and streamed PDF download (`print.school-archive.book.pdf`) utilizing Spatie Browsershot.
      - Strict page-break rules separating the Hardcover, Curriculum Index, Chronological Register Logs, Visas Ledger, and Annual Balance & Closing Attestation.
- [x] **Comprehensive Testing & Complete Tri-Lingual Localization:**
      - Added 7 feature tests in `tests/Feature/SchoolArchiveTest.php` covering access permissions, curriculum completion calculations, trimester/teacher filtering, layout rendering, and PDF streams.
      - Added 80 translation keys covering Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **464 passed tests, 21,048 assertions, 0 failures, 100% green**.

## 30. School Textbook Inventory, Solidarity Scheme & Student Clearance Certificate (منظومة إدارة الكتاب المدرسي وحصص التضامن وبراءة الذمة) (100%)

Comprehensive institutional management system compliant with Algerian Ministry of National Education and ONPS (الديوان الوطني للمطبوعات المدرسية) / CRDDP regulations, empowering middle school administrations, bursars (المقتصد), and textbook store managers to oversee book stocks, solidarity allowances (منحة 5000 دج), loan packages, and official end-of-year clearance certificates (Quitus):

- [x] **Database Schema & Eloquent Domain Models (`textbooks`, `student_textbook_loans`):**
      - Migrations `2026_10_28_010001_create_textbooks_table.php` and `2026_10_28_010002_create_student_textbook_loans_table.php` introducing the 28th and 29th core domain tables.
      - Enums `TextbookDistributionType` (Solidarity, Purchased, Loan) and `TextbookReturnCondition` (Good, Damaged, Lost).
      - Model `Textbook` with computed stock helpers (`distributedCount`, `availableStock`, `returnedCount`), active scopes, and relationships to `Level` and `Subject`.
      - Model `StudentTextbookLoan` with casts, scopes (`forAcademicYear`, `pendingReturn`), condition tracking, and relationships to `Student`, `Textbook`, and `User` (distributed by).
      - `Student` model updated with `loans(): HasMany`.
      - Integrated into `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` (29 verified tables).
- [x] **Domain Service (`TextbookInventoryService`):**
      - **National ONPS Curriculum Catalog Generator (`seedStandardAlgerianCatalog`):** Automatically generates and matches official prescribed textbook titles and prices for levels 1AM through 4AM with standard Algerian National Education guidelines.
      - **Classroom Pack Batch Distribution (`batchDistributeToClassroom` & `distributeToStudent`):** Distributes book packs across entire classrooms or individual students with automatic stock deduction and category tagging (Free Solidarity vs Loan vs Purchased).
      - **Condition Evaluation & Returns Processing (`processReturn`):** Consigns returned books, evaluates condition (Good, Damaged, Lost), records inspection notes, and sets individual clearance status.
      - **Clearance Engine (`checkStudentClearance` & `grantStudentClearance`):** Automatically computes student clearance eligibility based on return status and allows official clearance certification.
      - **KPI Aggregator & Data Assembly:** Computes warehouse stock, circulation rate, solidarity consumption %, and compiles data for printable documents.
- [x] **Interactive Filament Cockpit (`TextbookInventoryHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-book-open`.
      - 4 dedicated tabs:
        1. **دليل ورصيد الكتب (Catalog & Stock):** Displays all textbooks with level filters, stock availability bars, received quantities, and 1-click batch stock adjustment.
        2. **توزيع الكتب والتضامن (Distribution & Solidarity):** Classroom-by-classroom distribution tracker, filtering by beneficiary category (5000 DZD solidarity, free quotas, loan).
        3. **استرجاع الكتب وبراءة الذمة (Returns & Clearance):** Student clearance table, inspection return modal with Good/Damaged/Lost radios and compensation notes, and 1-click clearance granting.
        4. **الجرد السنوي (ONPS Annual Inventory):** Comprehensive balance ledger with received, distributed, remaining stock, unit prices, and financial value in DZD.
      - Header actions for seeding the national catalog, batch-distributing classroom packs, printing sheets, and exporting PDFs.
- [x] **Official Algerian A4 Printable Documents & PDFs (`TextbookInventoryController`):**
      - **Student Clearance Certificate (`print.textbook.clearance` + `.pdf`):** Formal A4 portrait certificate (Quitus) with official Algerian header, student bio & classroom, itemized loaned and returned book table with condition badges, legal clearance attestation, and signature boxes for the Store Manager and School Administration.
      - **Classroom Distribution Sheet (`print.textbook.classroom` + `.pdf`):** A4 tracking sheet for tracking distribution and return signatures per student for a given class section.
      - **Annual ONPS Inventory & Movement Ledger (`print.textbook.annual-inventory` + `.pdf`):** Formal A4 landscape financial and physical inventory statement for official transmission to ONPS / CRDDP and the Directorate of Education.
- [x] **Comprehensive Testing & Complete Tri-Lingual Localization:**
      - Added 8 feature tests in `tests/Feature/TextbookInventoryTest.php` covering catalog seeding, classroom pack distribution, condition evaluation, clearance calculation, access permissions, and PDF streams.
      - Added 150 translation keys across Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **478 passed tests, 21,141 assertions, 0 failures, 100% green**.

## 31. Student Transfers, Mobility & General Matriculation Register (منظومة شهادات التحويل وحركية التلاميذ وسجل القيد العام) (100%)

Complete statutory student mobility and registration tracking system compliant with Algerian National Education decrees, managing incoming/outgoing transfers, ministerial transfer authorizations, paper dossier forwarding, and the official institution matriculation index (سجل القيد العام - Registre Matricule):

- [x] **Database Schema & Eloquent Domain Models (`student_transfers` & `students` enhancement):**
      - Migration `2026_10_29_010001_add_matricule_and_enrollment_to_students_table.php` adding permanent institutional matricule number (`matricule_number`), national ID number (`national_id_number`), birth place (`place_of_birth`), entry date (`enrollment_date`), exit date (`departure_date`), and active flag (`is_active`).
      - Migration `2026_10_29_010002_create_student_transfers_table.php` introducing the 30th core domain table.
      - Enums `TransferType` (Inward, Outward), `TransferReason` (Change of residence, Parent employment, Orientation, Disciplinary, Health, Other), and `TransferStatus` (Pending, Approved, Cancelled).
      - Model `StudentTransfer` with casts, scopes (`inward`, `outward`, `approved`, `pending`, `forAcademicYear`), and relationships to `Student` and approving `User`.
      - Model `Student` extended with `transfers(): HasMany` and `latestTransfer(): HasOne`.
      - Integrated into `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` (30 verified tables).
- [x] **Domain Service (`StudentMobilityService`):**
      - **Transfer Registration & State Synchronization (`registerTransfer`):** Records movements with automatic student lifecycle transitions (sets `is_active = false` and `departure_date` on approved outward transfers; sets `is_active = true` and `enrollment_date` on approved inward transfers).
      - **Status Transitions (`updateTransferStatus`):** Reversible approval and cancellation workflows maintaining student registry consistency.
      - **Dossier Transmission Tracker (`markSchoolFileForwarded`):** Records official dispatch/receipt of physical student files.
      - **Mobility Flow KPIs Engine (`getMobilityKpis`):** Aggregates incoming, outgoing, net student enrollment delta, and pending transfers.
      - **Data Assemblers & Browsershot PDF Generators:** Assembles data and streams official A4 PDFs for transfer certificates, annual movement ledgers, and file forwarding slips.
- [x] **Interactive Filament Cockpit (`StudentMobilityHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-arrows-right-left`.
      - 3 primary tabs:
        1. **سجل الحركية والتحويلات (Transfers Ledger):** Dynamic search and filterable table of student movements, file forwarding toggles, and direct links to certificates and slips.
        2. **سجل القيد العام (Matricule Register):** Complete legal institutional roster ordered by matricule, featuring class filters, enrollment dates, and departure status badges.
        3. **مؤشرات التدفق والحركية (Mobility KPIs):** Net student balance analytics and dossier tracking guidance.
      - Header actions: "تسجيل تحويل تلميذ جديد" modal and "طباعة سجل الحركة السنوي (A4)".
- [x] **Official Algerian A4 Printable Documents & PDFs (`StudentMobilityController`):**
      - **Official Student Transfer Certificate (`print.student-transfer.certificate` + `.pdf`):** Formal A4 portrait certificate with Republic header, bilingual student identity, matriculation numbers, destination school and directorate, legal attestation, and tripartite approval block (Studies Counselor, Studies Director, Principal & Official Seal).
      - **Annual Student Mobility Ledger (`print.student-transfer.ledger` + `.pdf`):** Formal A4 landscape ministerial register listing all arrivals and departures with KPI summary cards.
      - **School File Forwarding Slip (`print.student-transfer.forwarding-slip` + `.pdf`):** Official forwarding letter enclosing the student's primary physical dossier (health book, grade records, clearance, transfer certificate) with a perforated tear-off receipt slip.
- [x] **Comprehensive Testing & Complete Tri-Lingual Localization:**
      - Added 9 feature tests in `tests/Feature/StudentMobilityTest.php` covering inward/outward transfers, state synchronization, file forwarding, KPI calculations, authorization policies, and PDF streams.
      - Added 180+ translation keys across Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **487 passed tests, 21,192 assertions, 0 failures, 100% green**.

## 32. School Disciplinary Council & Student Behavioral Sanctions Management (منظومة مجلس التأديب المدرسي ومتابعة العقوبات والتقويم السلوكي) (100%)

Complete statutory disciplinary council proceeding engine compliant with Algerian Ministry of National Education Executive Decree 90-130 and Ministerial Order 778 regarding middle school disciplinary councils and internal regulations:

- [x] **Database Schema & Eloquent Domain Models (`disciplinary_records`):**
      - Migration `2026_10_30_010001_create_disciplinary_records_table.php` introducing the 31st core system table.
      - Comprehensive fields: `student_id`, `academic_year`, `session_number`, `incident_date`, `session_date`, `infraction_degree`, `incident_description`, `reported_by_id`, `status`, `summons_sent_at`, `guardian_attended`, `student_statement`, `decision`, `exclusion_days`, `exclusion_start_date`, `exclusion_end_date`, `compensation_amount`, `deliberated_at`, `guardian_notified_at`, `attendees`, `notes`, `president_id`.
      - Enums `InfractionDegree` (FirstDegree, SecondDegree, ThirdDegree), `DisciplinarySanction` (Warning, Reprimand, TemporaryExclusion, DefinitiveExclusion, CommunityService, FinancialCompensation, Acquittal), and `DisciplinaryStatus` (Scheduled, Deliberated, Executed, Appealed), all implementing Filament's `HasLabel` and `HasColor`.
      - Model `DisciplinaryRecord` with casts, scopes (`scheduled`, `deliberated`, `forAcademicYear`), relationships (`Student`, `reportedBy`, `president`), and dual-property accessors ensuring smooth backward/forward compatibility across domain layers.
      - Integrated into `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` (31 verified tables).
- [x] **Domain Service (`DisciplinaryCouncilService`):**
      - **Hearing Scheduling & Referral (`scheduleSession`):** Schedules hearings with automatic numbering (`CD-YYYY-###`), statutory default council attendance rolls, and summons tracking.
      - **In-Camera Deliberation & Decision Recording (`recordDeliberation`):** Consigns hearing findings, student defense statements, sanction severity (1-8 days temporary exclusion limits per Algerian middle school law), financial compensation liability for property damage, and pedagogical notes.
      - **Guardian Summons & Notification Toggles:** Tracks dispatched summons (`markGuardianSummoned`) and formal decision delivery (`markGuardianNotified`).
      - **Behavioral KPIs & Analytics Engine (`getDisciplinaryKpis`):** Aggregates total referrals, deliberated sessions, exclusion decisions, warnings/reprimands, damage compensation amounts, guardian notification rate, and statistical breakdowns by degree and sanction.
      - **Data Assemblers & Browsershot PDF Generators:** Generates official A4 portrait/landscape PDFs for summons, deliberation minutes, formal notifications, and annual registers.
- [x] **Interactive Filament Cockpit (`DisciplinaryCouncilHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-scale` and sort order 88.
      - 3 primary tabs:
        1. **الجلسات التأديبية والمحاضر (Hearings & Sessions):** Searchable and filterable case management table with infraction degree badges, guardian summons/notification toggles, and direct links to print summons, minutes, and notifications.
        2. **سجل العقوبات والقرارات المعتمدة (Sanctions Ledger):** Dedicated categorization cards for exclusion orders (temporary/definitive), warnings/reprimands, and material restitution compensations in DZD.
        3. **المؤشرات والتقويم السلوكي (Behavioral Analytics & KPIs):** Metric cards and statistical distribution progress bars by regulatory degree and approved sanction types.
      - Header actions: "جدولة جلسة تأديب جديدة" modal and "سجل مجالس التأديب السنوي" ledger viewer.
- [x] **Official Algerian A4 Printable Documents & PDFs (`DisciplinaryCouncilController`):**
      - **Official Hearing Summons (`print.disciplinary.summons` + `.pdf`):** Formal A4 portrait summons for student and legal guardian with national republic header, session details, legal defense guarantees, and perforated return acknowledgment slip.
      - **Official Council Deliberation Minutes (`print.disciplinary.minutes` + `.pdf`):** Formal A4 portrait PV with statutory council composition roll, statement of facts, student defense and apology, secret deliberation, voting, and authenticating signatures block.
      - **Formal Sanction Notification (`print.disciplinary.notification` + `.pdf`):** Formal decision notice sent to the legal guardian with statutory notice of the 8-day legal right of appeal before the Wilaya Appeal Committee (لجنة الطعن الولائية) and tear-off return receipt slip.
      - **Annual Disciplinary Register Ledger (`print.disciplinary.ledger` + `.pdf`):** Formal A4 landscape ministerial register logging all disciplinary proceedings, sanctions, and restitution across the academic year with administrative audit visas.
- [x] **Comprehensive Testing & Complete Tri-Lingual Localization:**
      - Added 11 feature tests in `tests/Feature/DisciplinaryCouncilTest.php` covering session scheduling, in-camera deliberation, guardian summons/notification, KPI calculation, permissions, and PDF streams.
      - Added 210+ translation keys across Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **498 passed tests, 21,247 assertions, 0 failures, 100% green**.

## 34. Official Algerian School Year Opening Comprehensive Statistical Statement & October 15th Survey for the Directorate of Education (الكشف الإحصائي الشامل للدخول المدرسي إلى غاية 15 أكتوبر لمديرية التربية) (100%)

Statutory annual census return and comprehensive school opening survey submitted to the Directorate of Education (مديرية الدراسات والإعلام الآلي ونظم المعلومات) as of October 15th:

- [x] **Database Schema & Eloquent Domain Models (`school_census_records`):**
      - Migration `2026_11_07_010001_create_school_census_records_table.php` bringing Sajel core database tables from 31 to 32.
      - Attributes: `academic_year`, `census_date`, `reference_number`, `status` (`draft`, `certified`), `certified_at`, `certified_by_id`, `ordinary_classrooms_count`, `specialized_rooms_count`, `science_labs_count`, `computer_labs_count`, `library_capacity`, `canteen_capacity`, `administrative_staff_count`, `education_supervisors_count`, `service_workers_count`, `snapshot_data` (frozen JSON snapshot), `observations`.
      - Model `SchoolCensusRecord` with casts, scopes (`forAcademicYear`), relationships (`certifiedBy`), and `isCertified()` helper.
      - Registered in `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` (32 verified tables).
- [x] **Domain Service (`SchoolCensusService`):**
      - **Demographic & Divisions Matrix (`computeDemographics`):** Computes level-by-level pedagogical divisions, total students, boys, girls, gender percentages, schooling regimes (خارجي, نصف داخلي, داخلي), and average students per division.
      - **Age Pyramid Cross-Tabulation (`computeAgePyramid`):** Computes Algerian national demographic age pyramid matching student birth year and exact age against educational levels with boys/girls breakdown.
      - **Pedagogical Supervision & Teacher Staffing (`computeStaffingAndSupervision`):** Analyzes subject assignments, assigned teacher headcount, weekly allocated hours, and calculates institutional student-to-teacher staffing ratios.
      - **Official Certification & Historical Snapshot Freezing (`certifyCensus`):** Freezes an immutable historical snapshot (`snapshot_data`) upon certification by the Principal, guaranteeing that subsequent mid-year student transfers or departures never distort the legal October 15th return.
      - **Report Assembler & Browsershot PDF Generator:** Supports live dynamic draft returns and archived frozen reports with A4 landscape PDF generation.
- [x] **Interactive Filament Cockpit (`SchoolCensusHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-chart-bar` and sort order 90.
      - 3 interactive tabs:
        1. **الكشف الإحصائي للتلاميذ والأفواج التربوية (Demographics & Divisions):** Real-time summary KPI cards and level-by-level demographic table with gender and regime distribution.
        2. **الهرم العمري والتأطير البيداغوجي (Age Pyramid & Teacher Staffing):** Cross-tabulated demographic age pyramid by birth year and subject-by-subject teacher supervision breakdown with staffing ratios.
        3. **المرافق والهياكل القاعدية والمصادقة (Facilities & Official Certification):** Physical facilities management (ordinary rooms, specialized rooms, physics/science labs, computer lab, canteen, library capacity) and official Principal certification modal to freeze October 15 census data.
      - Header actions: "معاينة وطباعة الكشف (A4)" and "تحميل PDF".
- [x] **Official Algerian A4 Landscape Printable Documents & PDFs (`SchoolCensusController`):**
      - **Official School Census Statement (`print.school-census` + `.pdf`):** Standard A4 landscape format strictly adhering to Algerian Ministry of National Education guidelines with Democratic and Popular Republic header, institutional details, demographic table, age pyramid, teacher staffing, physical facilities inventory, and tripartite audit signatures (School Principal, Studies Counselor, District Inspector & Directorate Planning Visa).
- [x] **Comprehensive Testing & Complete Tri-Lingual Localization:**
      - Added 10 feature tests in `tests/Feature/SchoolCensusTest.php` covering demographic calculation, age pyramid cross-tabulation, teacher staffing ratios, official certification freezing, print/PDF routes, Livewire cockpit actions, and backup/restore table integration.
      - Added 121 translation keys across Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **517 passed tests, 21,349 assertions, 0 failures, 100% green**.

## 35. 4AM School Guidance, Wishes Card & Lycée Transition Orientation Hub (منظومة التوجيه والقبول إلى السنة الأولى ثانوي لتلاميذ 4AM) (100%)

Statutory 4th Year Middle School (4AM) guidance, wishes card management, pedagogical profile calculation, admissions council deliberations, and Wilaya appeals management for transition to 1st Year Secondary (Lycée):

- [x] **Database Schema & Eloquent Domain Models (`student_orientations`):**
      - Migration `2026_11_08_010001_create_student_orientations_table.php` bringing Sajel core database tables from 32 to 33.
      - Attributes: `student_id`, `academic_year`, `first_wish`, `second_wish`, `third_wish`, `guardian_approval` (boolean), `wishes_signed_at`, `scientific_score` (decimal 4,2), `literary_score` (decimal 4,2), `general_average` (decimal 4,2), `pre_orientation_stream`, `pre_orientation_notes`, `council_decision`, `council_notes`, `bem_score`, `rescue_average`, `appeal_status`, `appeal_stream`, `appeal_date`, `appeal_result_notes`, `appeal_decided_at`.
      - Model `StudentOrientation` with scopes (`forAcademicYear`, `admitted`, `pendingDeliberation`, `appealed`), relationships (`student`), and helper methods (`recommendedStream()`, `profileGap()`, `effectiveDecision()`, `hasSatisfiedWish()`).
      - Updated `Student` model with `orientations()` and `currentOrientation()` relationships.
      - Registered in `SajelHealthCheckCommand` (verifying 33 core tables), `SchoolBackupService`, and `SchoolRestoreService`.
- [x] **Domain Enums:**
      - `OrientationStream`: `ScienceTech` (جذع مشترك علوم وتكنولوجيا), `ArtsHumanities` (جذع مشترك آداب), `VocationalTraining` (تكوين مهني), `RepeatYear` (إعادة السنة), with localized labels, theme colors, icons, and short codes.
      - `OrientationAppealStatus`: `None` (بدون طعن), `Submitted` (مقدم للدراسة), `Accepted` (مقبول), `Rejected` (مرفوض) with labels and colors.
- [x] **Domain Service (`StudentOrientationService`):**
      - **Pedagogical Profile Computation (`computeStudentProfile`):** Computes scientific profile score (معدل المواد العلمية: رياضيات + فيزياء + علوم طبيعية), literary profile score (معدل المواد الأدبية: لغة عربية + لغة فرنسية + لغة إنجليزية + تاريخ وجغرافيا), annual general average, profile gap, and recommended stream.
      - **Wishes Management (`updateWishes`):** Records student stream choices (رغبة 1، رغبة 2، رغبة 3), legal guardian signature timestamp, and guardian approval status.
      - **Council Deliberations & Admissions Rules (`deliberateCouncil`):** Implements statutory Algerian admission criteria (`BEM >= 10.00` OR rescue average `(Annual Average + BEM) / 2 >= 10.00`), assigning final streams based on student wishes, pedagogical profile compatibility, and council consensus.
      - **Wilaya Appeal Management (`submitAppeal`, `decideAppeal`):** Manages appeals submitted to the Wilaya Orientation Appeals Commission (لجنة الطعن الولائية للتوجيه والقبول) with decision logging and audit trail.
      - **Browsershot PDF Generators:** Generates official A4 portrait Wishes Card, Parent Notification Letter with perforated return slip, and A4 landscape Official Council Minutes (محضر مداولات مجلس القبول والتوجيه).
- [x] **Interactive Filament Cockpit (`OrientationHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-arrow-trending-up` and sort order 92.
      - High-contrast, strictly styled for light and dark modes with dedicated tables safety net.
      - 3 interactive tabs:
        1. **بطاقات الرغبات والملمح البيداغوجي (Wishes & Profiles):** Candidate list with scientific vs literary score meters, recommended stream, wish compliance status, and wish editing modal.
        2. **مداولات مجلس القبول والتوجيه (Council Deliberations):** Deliberation ledger with annual averages, BEM scores, rescue averages, admission criteria badges, stream assignments, and deliberation modal.
        3. **الطعون الولائية وتوزيع الشعب (Appeals & Stream Distribution):** Appeals management workflow, decision resolutions, and visual stream allocation distribution progress bars.
      - Header actions: "معاينة وطباعة محضر المجلس (A4)" and "تحميل المحضر (PDF)".
- [x] **Official Algerian A4 Printable Documents & PDFs (`OrientationController`):**
      - **Official Wishes Card (`print.orientation.wishes` + `.pdf`):** A4 portrait card with student identity, subject breakdown, student choices, and parent commitment undertaking.
      - **Individual Guardian Notification Letter (`print.orientation.notification` + `.pdf`):** A4 portrait formal notification of 1st year secondary admission stream with perforated return acknowledgment slip.
      - **Official Council Deliberations Minutes (`print.orientation.pv` + `.pdf`):** Formal A4 landscape ministerial PV with full council composition, class candidates ledger, BEM scores, admissions decisions, and authentication signatures block.
- [x] **Comprehensive Testing & Complete Tri-Lingual Localization:**
      - Added 10 feature tests in `tests/Feature/StudentOrientationTest.php` covering profile score computation, wishes workflow, admission and rescue deliberation rules, appeals cycle, KPIs computation, print/PDF routes, Livewire cockpit actions, and backup/restore table integration.
      - Added 100+ translation keys across Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **527 passed tests, 21,413 assertions, 0 failures, 100% green**.

## 36. Student Psycho-Pedagogical Record & Guidance Counseling Hub (بطاقة المتابعة النفسية والتربوية للتلميذ وجلسات الاستماع والإرشاد) (100%)

Statutory Middle School Guidance and Orientation Counselor (مستشار التوجيه والإرشاد المدرسي والمهني) tracking hub, student psycho-pedagogical record (Le Dossier Psycho-Pédagogique de l'Élève), individual interview logs, and career counseling sessions:

- [x] **Database Schema & Eloquent Domain Models (`student_counseling_profiles`, `counseling_sessions`):**
      - Migrations `2026_11_09_010001_create_student_counseling_profiles_table.php` and `2026_11_09_010002_create_counseling_sessions_table.php` bringing Sajel core database tables from 33 to 35.
      - Profile Attributes: `student_id`, `family_status`, `parents_divorced`, `father_occupation`, `mother_occupation`, `siblings_count`, `birth_rank`, `chronic_illnesses`, `physical_disabilities`, `psychological_followup`, `pe_exempt`, `blood_group`, `repeated_grades`, `learning_difficulties`, `special_talents`, `career_aspirations`, `general_behavior`, `notes`.
      - Counseling Session Attributes: `student_id`, `counselor_id`, `session_date`, `session_number`, `reason`, `initiator`, `status`, `interview_notes`, `guidance_recommendations`, `action_plan`, `parent_notified`, `follow_up_date`.
      - Models `StudentCounselingProfile` and `CounselingSession` with scopes, relationships, and helper methods.
      - Updated `Student` model with `counselingProfile()`, `counselingSessions()`, and `latestCounselingSession()`.
      - Registered in `SajelHealthCheckCommand` (verifying 35 core tables), `SchoolBackupService`, and `SchoolRestoreService`.
- [x] **Domain Enums:**
      - `FamilyStatus`: `BothParents` (يعيش مع الوالدين معاً), `OrphanFather` (يتيم الأب), `OrphanMother` (يتيم الأم), `OrphanBoth` (يتيم الوالدين معاً), `DivorcedSeparated` (الوالدان منفصلان), `ParentsAbroad` (الوالدان بالخارج) with localized labels and colors.
      - `CounselingSessionReason`: `AcademicDifficulty` (صعوبات وبطء التعلم), `BehavioralIssue` (سلوك وانضباط مدرسي), `FamilyCrisis` (ظروف ومشاكل عائلية), `CareerGuidance` (توجيه دراسي ومهني), `Absenteeism` (الغياب المتكرر والتسرب), `PsychologicalSupport` (دعم نفسي وانفعالي), `Other` (أخرى) with labels and colors.
      - `CounselingSessionStatus`: `Open` (مفتوحة للمتابعة), `InProgress` (قيد التكفل والمرافقة), `Resolved` (تمت المعالجة والإغلاق).
- [x] **Domain Service (`StudentCounselingService`):**
      - Profile CRUD (`getOrCreateProfile`, `updateProfile`).
      - Counseling session logging and status transitions (`recordSession`, `updateSessionStatus`).
      - KPI computations (`computeKpis`).
      - Dossier assembler (`assembleStudentDossierData`).
      - Browsershot PDF generators for individual counseling reports, annual register ledgers, and comprehensive psycho-pedagogical dossiers.
- [x] **Interactive Filament Cockpit (`StudentCounselingHub`):**
      - Registered under `__('التشغيل اليومي')` (`Daily Operations`) with icon `heroicon-o-academic-cap` and sort order 93.
      - High-contrast, strictly styled for light and dark modes with dedicated tables safety net.
      - 3 interactive tabs:
        1. **سجلات المتابعة والملفات النفسية-التربوية (Profiles Ledger):** Dossier management with family status, health indicators, academic repetitions, learning difficulty badges, and profile edit modal.
        2. **جلسات الاستماع والمقابلات الإرشادية (Counseling Sessions):** Session ledger with motives, interviewers, action plans, status badges, and new session creation modal.
        3. **مؤشرات المتابعة والتحليل الوقائي (KPIs & Prevention):** Metric summary cards, difficulty prevalence charts, and counseling workload analytics.
- [x] **Official Algerian A4 Printable Documents & PDFs (`CounselingController`):**
      - **Official Student Psycho-Pedagogical Dossier (`print.counseling.dossier` + `.pdf`):** A4 portrait card strictly complying with ministerial middle school counseling dossier template.
      - **Individual Interview & Counseling Report (`print.counseling.session` + `.pdf`):** Formal A4 portrait individual session minutes with recommendations and parent notification slip.
      - **Annual Guidance Counseling Ledger (`print.counseling.ledger` + `.pdf`):** Formal A4 landscape ministerial ledger of all guidance sessions conducted during the school year.
- [x] **Comprehensive Testing, Dark Mode Contrast & Full Tri-Lingual Localization:**
      - Added 10 feature tests in `tests/Feature/StudentCounselingTest.php`.
      - Enhanced global dark mode CSS across `resources/css/filament.css` and `resources/views/filament/theme.blade.php`, enforcing native `color-scheme: dark`, high contrast table headers, footer cells, and dark backgrounds for custom cards.
      - Added 172+ translation keys across Arabic, English, and French, verified 100% green by `LocalizationCompletenessTest`.
      - Full test suite: **537 passed tests, 21,496 assertions, 0 failures, 100% green**.

## 37. Executive Principal Analytics Cockpit & Official Quarterly Institutional Report (الحصيلة البيداغوجية والتربوية الفصلية الشاملة - Bilan Trimestriel Global) (100%)

Comprehensive executive intelligence cockpit for Algerian Middle School Principals (مدراء المتوسطات) and Directors of Studies (نظار ومستشاري التربية) synthesizing institutional performance at the end of each academic trimester:

- [x] **Database Schema & Eloquent Domain Models (`quarterly_institutional_reports`):**
      - Migration `2026_11_10_010001_create_quarterly_institutional_reports_table.php` bringing Sajel core database tables from 35 to 36.
      - Attributes: `academic_year`, `trimester`, `status` (`draft`, `validated`, `archived`), `general_success_rate`, `evaluated_students_count`, `passed_students_count`, `excellence_count`, `encouragement_count`, `honor_count`, `passing_count`, `failing_count`, `total_due_sessions`, `completed_sessions`, `teacher_absence_hours`, `teacher_substitution_hours`, `student_absence_records`, `disciplinary_councils_count`, `counseling_sessions_count`, `level_breakdown`, `subject_breakdown`, `executive_summary`, `pedagogical_recommendations`, `validated_by`, `validated_at`.
      - Model `QuarterlyInstitutionalReport` with scopes, relationships (`validator`), and helper calculations.
      - Registered in `SajelHealthCheckCommand` (verifying 36 core tables), `SchoolBackupService`, and `SchoolRestoreService`.
- [x] **Domain Enums:**
      - `InstitutionalReportStatus`: `Draft` (مسودة ديناميكية), `Validated` (مصادق عليها ومجمدة), `Archived` (مؤرشفة).
- [x] **Domain Service (`InstitutionalBilanService`):**
      - Computes live trimester performance and attendance analytics across all 35 existing tables.
      - Supports live computation, draft snapshot saving, frozen report validation, and unfreezing.
      - High-fidelity A4 landscape PDF generation via `BrowsershotFactory`.
- [x] **Interactive Filament Cockpit (`InstitutionalBilanHub`):**
      - Registered under `__('الإشراف والمراقبة الإدارية')` with icon `heroicon-o-presentation-chart-line` and sort order 5.
      - High contrast and dark-mode safe.
      - 4 tabs: Academic Performance, Attendance & Regularity, School Climate & Guidance, and Executive Summary & Validation.
- [x] **Official Algerian A4 Landscape Printable Documents & PDFs (`InstitutionalBilanController`):**
      - Official ministerial report template (`print.institutional-bilan` + `.pdf`).
- [x] **Comprehensive Testing & Full Tri-Lingual Localization:**
      - 9 feature tests in `tests/Feature/InstitutionalBilanTest.php`.
      - 110+ translation keys across Arabic, English, and French verified by `LocalizationCompletenessTest`.
      - System health check passing with 36 core tables verified.

## 38. Ministry Rakmana Integration Hub (منظومة التكامل والمزامنة مع الرقمنة الوزارية) (100%)

Official ministerial synchronization engine for Algerian national education platforms (فضاء الرقمنة لوزارة التربية الوطنية):
- [x] **Student Rosters Import & Export (قوائم التلاميذ):**
      - Robust multi-format roster parser (`parseMinistryRosterCsv`) auto-detecting delimiters (comma, semicolon, tab), BOM stripping, and header normalization for official ministerial formats.
      - Official certified nominal roster CSV export (`generateRakmanaStudentRosterCsv`) featuring UTF-8 BOM (`\xEF\xBB\xBF`) and literal text formula wrapping (`="NIN"`) to protect 18-digit Algerian National ID Numbers from Excel scientific truncation.
      - Dedicated streaming export route (`/{locale}/rakmana/rosters/export-csv`) restricted to administrative and education leadership roles.
- [x] **Ministerial Grade Booking Files (ملفات حجز النقاط):**
      - Grade booking CSV export (`generateRakmanaGradeCsv`) with continuous assessment, midterm, exam scores, calculated Algerian quarterly averages, and pedagogical remarks.
      - Blank grade booking template export (`generateBlankGradeTemplateCsv`) pre-populating enrolled students, NINs, and empty score slots for teachers with dedicated route (`/{locale}/rakmana/grades/{classroom}/{subject}/template-csv`).
      - Intelligent Grade Import Engine (`parseGradeBookingFile`, `previewGradeImport`, `executeGradeImport`) supporting teacher-filled and Rakmana-exported files, fuzzy matching by NIN, matricule or Arabic full name, decimal comma normalization (`14,50` -> `14.50`), absence handling (`غ`/`غائب`), and atomic database upserts creating missing schedules and assessments.
- [x] **Filament Interactive Management Cockpit (`RakmanaSyncHub`):**
      - 4 reactive tabs: `import` (قوائم التلاميذ: استيراد وتصدير مع مرشحات الأقسام والمستويات), `grades` (تصدير كشوف حجز النقاط الوزارية وقالب الحجز الفارغ), `grade_import` (معالج تفريغ ومطابقة النقاط المرصودة مع معاينة تفاعلية وسجل الفروقات), and `audit` (مدقق المطابقة والأرقام التعريفية).
- [x] **Tri-Lingual Localization & Automated Verification:**
      - 47 translation keys verified across Arabic, English, and French passing `LocalizationCompletenessTest`.
      - Expanded test suite to 16 feature tests (94 assertions) in `tests/Feature/RakmanaSyncTest.php` passing with 100% green status.

## 39. Administrative Resumption of Work Protocols & Teacher Replacement Mandates (محاضر استئناف العمل والتسخيرات الإدارية لغيابات الأساتذة وإشعارات الخصم من الراتب) (100%)

Complete statutory Algerian administrative and legal workflow for managing teacher absences, returns, and requisitions under Algerian Education Law 08-04, Executive Decree 08-315, and General Civil Service Ordinance 06-03:
- [x] **Database Schema & Eloquent Attributes (`teacher_absences`):**
      - Migration `2026_11_06_134144_add_administrative_protocols_to_teacher_absences_table.php` adding `resumed_at`, `resumption_pv_number`, `resumption_notes`, `is_deduction_notified`, `deduction_days`, `deduction_ref_number`, `deduction_notified_at`, `is_requisition`, `requisition_ref_number`, and `requisition_justification`.
      - Scopes: `resumed()`, `pendingResumption()`, `deductionNotified()`, `requisitions()`.
      - Model helpers: `hasResumed()`, `hasSalaryDeduction()`, `hasRequisition()`, `calculateDeductionDays()`, `resumptionPvFormatted()`, `deductionNoticeFormatted()`, `requisitionFormatted()`.
- [x] **Service Layer Expansion (`TeacherAbsenceService`):**
      - `recordWorkResumption(TeacherAbsence, array)`: records effective return to work, date/time, and registers official PV reference.
      - `issueSalaryDeductionNotice(TeacherAbsence, array)`: records 1/30th per-day statutory financial deductions under Law 90-21 with 48h appeal window.
      - `issueRequisitionMandate(TeacherAbsence, array)`: official administrative requisition to safeguard classroom teaching continuity.
      - High-fidelity PDF generation methods: `generateResumptionPvPdf()`, `generateDeductionNoticePdf()`, and `generateRequisitionOrderPdf()`.
- [x] **Controller Actions & Secure Routing (`TeacherAbsenceController`, `routes/web.php`):**
      - Web routes with tri-party role authorization (Admin, Teacher, Substitute):
        - `print.teacher-absence.resumption-pv` (+ `.pdf`)
        - `print.teacher-absence.deduction-notice` (+ `.pdf`)
        - `print.teacher-absence.requisition-order` (+ `.pdf`)
- [x] **Self-Contained Official Algerian Ministerial Printable Templates:**
      - `resources/views/print/teacher-resumption-pv.blade.php`: Official Minutes of Resumption of Work (محضر استئناف العمل الرسمي).
      - `resources/views/print/salary-deduction-notice.blade.php`: Official Salary Deduction Notice (إشعار رسمي بالخصم من الراتب لغياب غير مبرر).
      - `resources/views/print/administrative-requisition-order.blade.php`: Official Requisition Order (قرار تسخير إداري رسمي لتغطية حصة تعليمية).
- [x] **Interactive Filament Livewire Cockpit Integration (`TeacherAbsenceHub`):**
      - Visual status badges and quick action buttons on absence radar cards.
      - Work Resumption modal (`$isResumptionModalOpen`) for date, PV number, and notes.
      - Salary Deduction modal (`$isDeductionModalOpen`) with auto-calculated deduction days and statutory appeal alerts.
      - Requisition button and PDF downloads on affected session rows.
- [x] **Tri-Lingual Localization & Automated Testing:**
      - 114 translation keys across Arabic, English, and French verified by `LocalizationCompletenessTest`.
      - 7 feature tests passing in `tests/Feature/TeacherAbsenceProtocolsTest.php` with 56 assertions.

## 40. School Life & Extra-curricular Clubs System (منظومة الحياة المدرسية والنوادي والأنشطة اللاصفية) (100%)

Comprehensive management of student extracurricular life, school clubs, activities, and merit recognitions under Algerian educational directives for school projects (مشروع المؤسسة):
- [x] **Database Schema & Eloquent Models (`school_clubs`, `club_memberships`, `club_activities`):**
      - Migration `2026_11_11_010001_create_school_clubs_and_activities_tables.php` bringing Sajel core database tables from 36 to 39.
      - Models: `SchoolClub`, `ClubMembership`, `ClubActivity` with relationships, scopes, and helper methods.
      - Registered in `SajelHealthCheckCommand` (verifying 39 core tables), `SchoolBackupService`, and `SchoolRestoreService`.
- [x] **Domain Enums (`app/Enums`):**
      - `ClubType`: `Scientific`, `Environmental`, `Cultural`, `Reading`, `Sports`, `Citizenship`, `Other`.
      - `ClubStatus`: `Active`, `Frozen`, `Archived`.
      - `ClubMemberRole`: `President`, `VicePresident`, `Secretary`, `ActiveMember`, `HonoraryMember`.
      - `ClubMemberStatus`: `Active`, `Withdrawn`, `Graduated`.
      - `ClubActivityType`: `Workshop`, `Exhibition`, `Competition`, `FieldTrip`, `Commemoration`, `AwarenessCampaign`.
      - `ClubActivityStatus`: `Planned`, `Completed`, `Postponed`, `Cancelled`.
- [x] **Domain Service (`SchoolLifeService`):**
      - `getClubsOverview()`: computes institutional clubs overview, membership numbers, bureau counts, activity completion, and grade-level distribution.
      - `createClub()`, `updateClub()`, `enrollStudent()`, `recordActivity()`.
      - High-fidelity PDF generation methods: `generateClubTechnicalSheetPdf()`, `generateMembershipCertificatePdf()`, `generateAnnualLifeBilanPdf()`.
- [x] **Controller Actions & Secure Routing (`SchoolLifeController`, `routes/web.php`):**
      - Web routes with role authorization (Admin and Supervising Teachers):
        - `print.school-life.club-sheet` (+ `.pdf`)
        - `print.school-life.member-certificate` (+ `.pdf`)
        - `print.school-life.annual-bilan` (+ `.pdf`)
- [x] **Self-Contained Official Algerian Printable Templates:**
      - `resources/views/print/club-technical-sheet.blade.php`: Club Technical Sheet & Executive Board (البطاقة الفنية والتنظيمية للنادي المدرسي - A4 Portrait).
      - `resources/views/print/club-member-certificate.blade.php`: Official Certificate of Merit & Engagement (شهادة تقدير ومشاركة في النادي المدرسي - A4 Landscape with ornate national borders).
      - `resources/views/print/annual-school-life-bilan.blade.php`: Official Annual School Life & Clubs Bilan (الحصيلة السنوية الرسمية للحياة المدرسية - A4 Landscape).
- [x] **Interactive Filament Livewire Cockpit (`SchoolLifeHub`):**
      - Registered under `__('الحياة المدرسية وشؤون التلاميذ')` with icon `heroicon-o-sparkles` and sort order 4.
      - 4 tabs: `clubs` (cards with capacity bars and quick actions), `members` (board roles, student filters, certificate print/PDF), `activities` (agenda of completed and planned events), `bilan` (domain distribution and official report download).
      - 3 Livewire modals: Create/Edit Club, Enroll Student, Record Activity.
- [x] **Tri-Lingual Localization & Automated Testing:**
      - 188 translation keys across Arabic, English, and French verified by `LocalizationCompletenessTest`.
      - 7 feature tests passing in `tests/Feature/SchoolLifeTest.php` with 58 assertions.

## 41. School Health & Medical Screening Unit (منظومة الصحة المدرسية ووحدة الكشف والمتابعة الصحية - UDS) (Completed - 100%)

Comprehensive school healthcare management under Algerian public health and national education inter-ministerial decrees:
- [x] **Database Schema & Data Model:**
      - Migration `2026_11_12_010001_create_student_medical_records_table.php` creating table `student_medical_records` with composite uniqueness on `(student_id, academic_year)`.
      - Captures biometrics (height, weight, BMI), blood types, vision acuity, hearing conditions, dental health (caries count & hygiene), chronic conditions (asthma, diabetes, allergies, epilepsy, cardiopathy, etc.), individual emergency reception protocols (PAI - Projet d'Accueil Individualisé), on-site emergency medications, vaccinations, and physical education dispensations.
      - Eloquent model `StudentMedicalRecord` with calculated properties (`bmi`, `bmi_classification`), scoping scopes, and relationship hooks on `Student` (`medicalRecords`, `currentMedicalRecord`, `isEpsDispensed`).
      - Full registration in `SajelHealthCheckCommand` (now tracking 40 core domain tables), `SchoolBackupService`, and `SchoolRestoreService`.
- [x] **Enums Architecture:**
      - `App\Enums\BloodType`: Complete Rhesus blood groups (A+, A-, B+, B-, AB+, AB-, O+, O-, Unknown) with badge labels.
      - `App\Enums\ChronicConditionType`: Standard public health disease taxonomy (None, Asthma, Diabetes, Severe Allergy, Epilepsy, Cardiopathy, Hematological, Renal, Physical Handicap, Other).
      - `App\Enums\EpsDispensationStatus`: Official physical education statuses (Fit, Temporary, Trimester, Annual, Exempt) with active exemption checks.
      - `App\Enums\HearingCondition`: Audiological screening taxonomy (Normal, Mild Impairment, Severe Impairment, Uses Hearing Aid).
      - `App\Enums\DentalHygieneStatus`: Oral healthcare status (Good, Moderate, Poor, Needs Orthodontics).
- [x] **Domain Service & Livewire Cockpit:**
      - `SchoolHealthService`: Encapsulates screening updates, biometric BMI calculations, chronic condition and PAI protocols, EPS dispensation validation, summary KPIs computation, and PDF generation.
      - `SchoolHealthHub` (`app/Filament/Pages/SchoolHealthHub.php` & `resources/views/filament/pages/school-health-hub.blade.php`): Full-featured Livewire cockpit with reactive tabs:
        1. **Systematic Screenings (فحوصات الكشف الدوري):** Student health roster, biometric indicators, search, level filtering, and screening entry modal.
        2. **Chronic Conditions & PAI (الأمراض المزمنة وبروتوكول PAI):** PAI emergency protocol tracking, medication stock at infirmary, and rapid crisis management instructions.
        3. **Physical Education Exemptions (إعفاءات التربية البدنية والرياضية EPS):** Official dispensation tracking with validity dates and certificate numbers.
        4. **Annual UDS Statistical Bilan (الحصيلة الإحصائية السنوية):** Macro institutional indicators, level breakdown coverage rates, chronic condition classification, and EPS dispensation statistics.
- [x] **Self-Contained Printable Views & PDF Generation:**
      - `resources/views/print/student-medical-dossier.blade.php`: Official A4 portrait student medical card (بطاقة الفحص والمتابعة الطبية المدرسية).
      - `resources/views/print/eps-medical-dispensation.blade.php`: Formal A4 portrait EPS medical exemption certificate (شهادة الإعفاء الطبي من ممارسة التربية البدنية والرياضية).
      - `resources/views/print/annual-uds-bilan.blade.php`: High-density single-page A4 landscape institutional health report with bulletproof CSS table layout.
      - `SchoolHealthController`: 6 web routes for in-browser print preview and PDF downloads (`BrowsershotFactory`).
- [x] **Tri-Lingual Localization & Automated Verification:**
      - 232 translation keys verified across Arabic, English, and French by `LocalizationCompletenessTest`.
      - Comprehensive automated feature tests in `tests/Feature/SchoolHealthTest.php` (8 tests, 46 assertions, 100% green).
      - All 64 related feature tests pass cleanly with 511 assertions.

## 42. Subject Councils, Pedagogical Coordination & District Seminars System (منظومة مجالس التعليم والتنسيق البيداغوجي للمواد والندوات التربوية للمقاطعة) (100%)

Statutory pedagogical management under Algerian Executive Decree 08-315 and Ministerial Decree 174 governing Subject Councils, Coordination Meetings, and District Inspection Seminars:
- [x] **Database Schema & Data Model:**
      - Migration `2026_11_13_010001_create_pedagogical_councils_and_seminars_tables.php` bringing Sajel core database tables from 40 to 44.
      - Tables: `pedagogical_councils`, `pedagogical_council_attendances`, `pedagogical_seminars`, `pedagogical_seminar_attendances`.
      - Eloquent models: `PedagogicalCouncil`, `PedagogicalCouncilAttendance`, `PedagogicalSeminar`, `PedagogicalSeminarAttendance`.
      - Full registration in `SajelHealthCheckCommand` (now verifying 44 core domain tables), `SchoolBackupService`, and `SchoolRestoreService`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `PedagogicalCouncilType`: `SubjectCouncil` (مجلس التعليم للمادة), `TermCoordination` (جلسة التنسيق البيداغوجي الفصلي), `AdministrativeCoordination` (مجلس التنسيق الإداري والتربوي), `InternalSeminar` (ندوة تربوية داخلية بالمؤسسة).
      - `PedagogicalCouncilStatus`: `Scheduled`, `InProgress`, `Completed`, `Postponed`.
      - `SeminarType`: `InspectoralSeminar` (ندوة تربوية مقاطعية), `ModelLesson` (درس نموذجي توضيحي), `StudyDay` (يوم دراسي تكويني), `ExchangeVisit` (زيارة بيداغوجية متبادلة).
      - `SeminarStatus`: `Planned`, `Completed`, `Postponed`, `Cancelled`.
- [x] **Domain Service Layer (`PedagogicalCouncilService`):**
      - `getPedagogicalOverview()`: aggregate executive telemetry, council counts, teacher participation rates, upcoming seminars, and pacing lag analytics.
      - `computeCurriculumPacingStats()`: automated comparison of completed lessons in daily logs against ministerial school-calendar benchmarks.
      - `createCouncil()` and `updateCouncil()`: atomic transactions managing council minutes and synchronizing teacher attendance rosters.
      - `recordSeminar()`: registers inspectoral training days, target teachers, and model lesson animators.
      - High-fidelity PDF generation methods: `generateCouncilPvPdf()`, `generateCurriculumPacingPdf()`, `generateSeminarCalendarPdf()`.
- [x] **Controller Actions & Secure Routing (`PedagogicalCouncilController`, `routes/web.php`):**
      - 6 web routes for in-browser print preview and PDF downloads (`BrowsershotFactory`) with role authorization (Admin, General Supervisor, and Subject Teachers):
        - `print.pedagogical-council.pv` (+ `.pdf`)
        - `print.pedagogical-council.curriculum-pacing` (+ `.pdf`)
        - `print.pedagogical-council.seminar-calendar` (+ `.pdf`)
- [x] **Self-Contained Official Algerian Printable Templates:**
      - `resources/views/print/pedagogical-council-pv.blade.php`: Official Minutes of Subject Council (محضر اجتماع مجلس التعليم والتنسيق البيداغوجي - A4 Portrait with attendee signatures and tripartite visas).
      - `resources/views/print/curriculum-pacing-ledger.blade.php`: Curriculum Pacing & Pedagogical Progression Ledger (كشف متابعة وتيرة تنفيذ المنهاج الدراسي وتدرج التعلمات - A4 Landscape single-page).
      - `resources/views/print/pedagogical-seminars-calendar.blade.php`: Official Calendar of Pedagogical Seminars & Inspection Training Days (رزنامة الندوات التربوية وأيام التكوين والتفتيش - A4 Landscape single-page).
- [x] **Interactive Filament Livewire Cockpit (`PedagogicalCouncilHub`):**
      - Grouped under `__('التخطيط والمناهج والتفتيش')` with icon `heroicon-o-user-group`.
      - 3 reactive tabs: `councils` (council cards, status badges, coordinator, attendance metrics, print/PDF), `pacing` (automated completion rate vs ministerial expectation, lag badges, status), `seminars` (district seminars agenda, animators, inspector, official call numbers).
      - Modals: Document Council Minutes (`openCouncilModal`), Schedule District Seminar (`openSeminarModal`).
      - Role-based visibility: scoped for Teachers (filtered to their subject councils/seminars) and leadership/inspection staff via `User::canManagePedagogicalCouncils()`.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - 164 translation keys verified across Arabic, English, and French by `LocalizationCompletenessTest`.
      - 8 comprehensive feature tests in `tests/Feature/PedagogicalCouncilTest.php` passing with 100% green status.
      - Navigation role assertions passing in `tests/Feature/RoleNavigationTailoringTest.php`.

## 43. School Canteen, Half-Board Regime & Daily Catering Consumption System (منظومة الإطعام المدرسي وتسيير نصف الداخلي وورقة الاستهلاك اليومي للمطعم للمقتصد - FJC) (100%)

Comprehensive statutory catering and half-board (نصف داخلي) management under Algerian National Education Law 08-04, Executive Decrees governing school catering, food accounting, and hygiene regulations:
- [x] **Database Schema & Data Model:**
      - Migration `2026_11_14_010001_create_school_canteen_and_meal_tables.php` bringing Sajel core database tables from 44 to 48.
      - Tables: `canteen_food_items`, `canteen_daily_sheets`, `canteen_consumption_entries`, `canteen_weekly_menus`.
      - Eloquent models: `CanteenFoodItem`, `CanteenDailySheet`, `CanteenConsumptionEntry`, `CanteenWeeklyMenu`.
      - Full registration in `SajelHealthCheckCommand` (now verifying 48 core domain tables), `SchoolBackupService`, and `SchoolRestoreService`.
- [x] **Domain Enums & Role Abilities:**
      - `FoodItemCategory`: MeatFish (لحوم وأسماك ودواجن), Produce (خضر وفواكه طازجة), DryGoods (بقوليات وبقالة وحبوب), Dairy (ألبان وأجبان وبيض), Bakery (مخبوزات وعجائن), OilsCondiments (زيوت وتوابل ومعلبات).
      - `CanteenSheetStatus`: Draft (مسودة يومية), Certified (مؤشرة ومصادق عليها), Archived (مؤرشفة نهائياً).
      - Enhanced `Student`: `isHalfBoard(): bool`, `isBoarder(): bool`, and `regimeLabel(): string` supporting national educational terminology.
      - Enhanced `User`: `canManageCanteen(): bool` granting authorized access strictly to Developer, Principal, General Supervisor, and Finance Officer (المقتصد / مسير المصالح المالية).
- [x] **Domain Service Layer (`SchoolCanteenService`):**
      - `seedStandardAlgerianPantryItems()`: automated idempotent seeder with 12 standard Algerian food staples (عدس، حمص، لوبيا، أرز، زيت نباتي، حليب، بيض، دجاج، لحم بقري، طماطم مصبرة، توابل، خبز نصف مدعم).
      - `getCanteenOverview()`: calculates half-board beneficiaries, detects dietary allergy & chronic illness alerts (severe allergies, celiac, diabetes, PAI emergency protocols), compiles monthly meals served, total expenditure, and average cost per meal.
      - `getOrCreateDailySheet()`, `addConsumptionEntry()`, `removeConsumptionEntry()`, `certifyDailySheet()`: atomic transactions handling daily sheets, headcount tracking, real-time stock deduction and restoration, and recalculation of daily expenditure and unit cost per meal.
      - `saveWeeklyMenu()`: validates and records weekly nutritional menus with caloric estimates and UDS doctor approval.
      - High-fidelity PDF generation methods: `generateDailyConsumptionSheetPdf()`, `generateWeeklyMenuPdf()`, `generatePeriodicBilanPdf()`.
- [x] **Controller Actions & Secure Routing (`SchoolCanteenController`, `routes/web.php`):**
      - 6 web routes for in-browser print preview and PDF downloads (`BrowsershotFactory`) with strict role-based authorization:
        - `print.canteen.daily-sheet` (+ `.pdf`)
        - `print.canteen.weekly-menu` (+ `.pdf`)
        - `print.canteen.periodic-bilan` (+ `.pdf`)
- [x] **Self-Contained Official Algerian Printable Templates:**
      - `resources/views/print/canteen-daily-sheet.blade.php`: Official Feuille Journalière de Consommation (FJC) - A4 Landscape, tripartite signatures (Storekeeper, Bursar, Principal), itemized category breakdown, unit costs, and daily meal metrics.
      - `resources/views/print/canteen-weekly-menu.blade.php`: Official Balanced Weekly Menu Poster - A4 Portrait, Sunday through Thursday meals, calories estimate, UDS medical validation visa, and ministerial header.
      - `resources/views/print/canteen-periodic-bilan.blade.php`: Monthly Catering Movement & Meal Distribution Report (كشف التغذية والإطعام الدوري لمديرية التربية) - A4 Landscape, macro telemetry, daily sheet journal, and Directorate of Education financial visa.
- [x] **Interactive Filament Livewire Cockpit (`SchoolCanteenHub`):**
      - Grouped under `__('المصالح المالية والكتاب المدرسي')` with icon `heroicon-o-cake`.
      - 4 reactive tabs:
        1. **Daily Sheet FJC (ورقة الاستهلاك اليومي):** Date selector, live headcount editing (half-boarders, teachers, workers), itemized disbursed pantry items, modal for stock consumption, real-time total cost and cost-per-meal computation, and official certification action.
        2. **Pantry Inventory (مخزون المواد الغذائية):** Complete pantry ledger, stock balance, unit prices, warning threshold indicators, low-stock alerts, one-click national pantry initialization, and restock modal.
        3. **Weekly Menu Planner (البرنامج الأسبوعي للوجبات):** Nutritional schedule cards from Sunday to Thursday, calories, medical approval status, and print shortcut.
        4. **Periodic Bilan (كشف التغذية لمديرية التربية):** Monthly financial summary, days operated, meals distributed, total expenditure, average cost per meal, and ministerial export shortcut.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - 193 translation keys verified across Arabic, English, and French by `LocalizationCompletenessTest`.
      - 9 comprehensive feature tests in `tests/Feature/SchoolCanteenTest.php` passing with 100% green status (66 assertions).
      - Tailored navigation verified for all roles in `tests/Feature/RoleNavigationTailoringTest.php`.

## 44. Comprehensive Ministerial Digitation & File Integration Ecosystem (منظومة التكامل مع الرقمنة الوزارية: استيراد وتصدير قوائم التلاميذ وملفات حجز النقاط ومحاضر مجالس الأقسام والمداولات الوزارية - amatti.education.gov.dz) (100%)

Full statutory alignment and bidirectional file interoperability with the official digital platform of the Algerian Ministry of National Education (`amatti.education.gov.dz`):
- [x] **Database Schema & Extended Student Model:**
      - Migration `2026_11_15_010001_add_ministerial_rakmana_fields_to_students_table.php` adding `first_name_latin`, `last_name_latin`, `is_repeater` (معيد / مكرر السنة), and `address` (عنوان الإقامة).
      - Eloquent model enhancements on `Student`: `isRepeater(): bool`, `fullNameLatin` accessor, and inclusion in fillables/casts and factories.
      - User role capabilities: `User::canManageRakmana(): bool` granting authorized management to Developer, Principal, Censor, General Supervisor, and Admins.
- [x] **Domain Service Layer (`RakmanaSyncService`):**
      - Ministerial Header Mapping (`mapHeaderColumns`): auto-detects Arabic, French, and Latin header labels (`nom_latin`, `prenom_latin`, `redoublant`, `adresse`).
      - Normalization & Transactional Roster Ingestion (`sanitizeStudentRow`, `executeImport`): handles NIN normalization, repeater flags, Latin names, and address persistence.
      - 19-Column Official Ministerial Student Roster Export (`generateRakmanaStudentRosterCsv`): emits full official Algerian Ministry specification CSV with UTF-8 BOM.
      - Intelligent Subject Coefficient Calculator (`getSubjectCoefficient`): automatically calculates statutory coefficients (معاملات المواد) across levels according to Algerian middle school curricula.
      - Comprehensive Class Council Deliberation Engine (`generateClassCouncilDeliberationMatrix`): compiles student x subject grades, total weighted points, total coefficients, general averages, rankings, and statutory council decisions (تهنئة، تشجيع، لوحة شرف، تنبيه، توبيخ).
      - Deliberation CSV Export (`generateMinisterialDeliberationCsv`): outputs the full deliberation matrix compatible with ministerial archiving spreadsheets.
      - High-Fidelity PDF Generation via `BrowsershotFactory`: `generateGradeSheetPdf()` and `generateClassCouncilDeliberationPdf()`.
- [x] **Controller Actions & Secure Routing (`RakmanaSyncController`, `routes/web.php`):**
      - 5 new routes supporting official previews and PDF/CSV downloads:
        - `print.rakmana.grade-sheet` (+ `.pdf`)
        - `rakmana.deliberations.export-csv`
        - `print.rakmana.deliberation-sheet` (+ `.pdf`)
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/rakmana-grade-sheet.blade.php`: Official Subject Grade Booking Sheet (بطاقة حجز النقاط الرسمية الموحدة للمادة - A4 Portrait) with ministerial header, KPI strip, marks breakdown, and dual teacher-principal visas.
      - `resources/views/print/rakmana-class-deliberation.blade.php`: Official Class Council Deliberation Minutes (محضر مداولات مجلس القسم الفصلي الرسمي - A4 Landscape) with comprehensive subject matrix, honor brackets, and quadruple council signatures.
- [x] **Interactive Filament Livewire Cockpit (`RakmanaSyncHub`):**
      - Grouped under `__('الإدارة المدرسية والرقمنة والأرشيف')` with icon `heroicon-o-arrow-path-rounded-square`.
      - 5 reactive tabs:
        1. **Rosters (قوائم التلاميذ):** Import wizard with group auto-resolution, live preview, NIN match indicator, and 19-column ministerial CSV export.
        2. **Grades Export (تصدير كشوف حجز النقاط):** Class/subject selector, live score matrix, CSV export, blank booking template, and official printable grade sheet shortcuts.
        3. **Grades Import (استيراد ملفات حجز النقاط):** Score parser (0-20), absent tracking, projected average preview, and atomic grade persistence.
        4. **Deliberations & Class Councils (محاضر مجالس الأقسام والمداولات):** Comprehensive deliberation matrix, automatic rankings, council honor awards, deliberation CSV export, and official printable A4 minutes.
        5. **Audit & Reconciliation (مدقق المطابقة والفروقات):** Institutional completeness checks for NIN, Latin names, birth records, and residential addresses.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - 49 new translation keys populated in `lang/en.json` and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 22 comprehensive feature tests in `tests/Feature/RakmanaSyncTest.php` passing with 100% green status (135 assertions).
      - Code style verified with `vendor/bin/pint`.

## 45. Staff & Teacher Career Management, Annual Administrative & Pedagogical Evaluation, and Step Advancement Grids (منظومة تسيير المسار المهني وحركات الموظفين والأساتذة، التنقيط الإداري والتربوي السنوي، وجداول الترقية في الدرجات - Notation Administrative & Avancements d'Échelons) (100%)

Full statutory alignment with Algerian Public Civil Service Law (Ordinance 06-03) and Special Status of National Education Personnel (Executive Decree 08-315, amended by Decree 12-240):
- [x] **Database Schema & Extended Staff Profile:**
      - Migration `2026_11_16_010001_add_career_profile_fields_to_users_table.php` adding civil service identity to `users`: `registration_number` (رقم التعريف المالي/الوظيفي Matricule), `national_id_number` (NIN), `birth_date`, `birth_place`, `gender`, `phone`, `recruitment_date` (تاريخ أول تعيين), `grade_appointment_date` (تاريخ التعيين في الرتبة), `current_step` (الدرجة الحالية 0-12), `current_step_date` (تاريخ مفعول الدرجة), `statutory_grade` (الرتبة القانونية), and `diploma` (المؤهل العلمي).
      - Migration `2026_11_16_010002_create_staff_career_and_evaluation_tables.php` creating 3 core civil service tables:
        - `staff_evaluations`: annual administrative evaluation (/20) with 4 statutory sub-criteria (punctuality /5, performance /5, conduct /5, initiative /5), pedagogical inspection score (/20), composite promotion score, appreciation, and staff signature/contestation status.
        - `career_advancements`: step advancements (steps 0 to 12) with computed statutory eligibility dates across Algerian rhythms (Minimum 2.5 yrs, Medium 3 yrs, Maximum 3.5 yrs), CAP approval status, and financial effective dates.
        - `staff_movements`: tracking transfers (حركات نقل), secondments (انتداب), availability leaves (استيداع), retirements (تقاعد), and reinstatements (إعادة إدماج).
      - Database health check expanded to 51 required tables: `[PASS] schema: All required tables present (51)`.
- [x] **Domain Enums & Models:**
      - Enums: `AdvancementRhythm` (Minimum, Medium, Maximum), `StaffMovementType` (Transfer, Secondment, Availability, Retirement, Integration, NewHire), `StaffEvaluationAppreciation` (Excellent, VeryGood, Good, Acceptable, Poor).
      - Models: `StaffEvaluation`, `CareerAdvancement`, `StaffMovement`.
      - User model enhancements: `evaluations()`, `latestEvaluation()`, `careerAdvancements()`, `latestAdvancement()`, `movements()`, and seniority calculators (`getGeneralSeniority`, `getGradeSeniority`, `getStepSeniority`), and permission gate `canManageStaffCareer(): bool`.
- [x] **Domain Service Layer (`StaffCareerService`):**
      - Seniority breakdowns: `calculateSeniority(User $user, ?Carbon $targetDate)`.
      - Step projection engine: `projectAdvancementEligibility(User $user, ?Carbon $asOfDate)` calculating statutory eligibility for Minimum (30 months), Medium (36 months), and Maximum (42 months).
      - Annual sync engine: `syncAdvancementProjections(int $targetYear)` automatically compiling advancement proposals for the Joint Administrative Committee (CAP).
      - Evaluation manager: `saveEvaluation(array $data)` computing sub-scores, composite mark, and appreciation.
      - Promotion executor: `promoteStaff(CareerAdvancement $advancement, Carbon $effectiveDate)` updating user step and recording financial effect.
      - Official CAP CSV export: `generateAdvancementLedgerCsv(int $year)` with UTF-8 BOM.
      - High-fidelity PDF generation via `BrowsershotFactory`: `generateEvaluationSheetPdf()` and `generateAdvancementLedgerPdf()`.
- [x] **Controller Actions & Secure Routing (`StaffCareerController`, `routes/web.php`):**
      - 5 secure routes for official previews, CSV exports, and streamed PDFs:
        - `print.staff-career.evaluation-sheet` (`/{locale}/print/staff-career/evaluation/{evaluation}`)
        - `print.staff-career.evaluation-sheet.pdf` (`/{locale}/print/staff-career/evaluation/{evaluation}/pdf`)
        - `print.staff-career.advancement-ledger` (`/{locale}/print/staff-career/advancements/ledger`)
        - `print.staff-career.advancement-ledger.pdf` (`/{locale}/print/staff-career/advancements/ledger/pdf`)
        - `staff-career.advancements.export-csv` (`/{locale}/staff-career/advancements/export-csv`)
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/staff-evaluation-sheet.blade.php`: Official Annual Administrative Evaluation Bulletin (استمارة وبطاقة التنقيط والتقييم الإداري السنوي - A4 Portrait) with official ministerial header, civil identity strip, 4-tier score breakdown, inspection marks, and dual employee-principal signature visas.
      - `resources/views/print/career-advancement-ledger.blade.php`: Official Step Advancement Ledger for Joint Administrative Committee (جدول الترقية في الدرجات السنوي - اللجنة الإدارية المتساوية الأعضاء - A4 Landscape) with full staff identification, matricule, current step & effect date, seniority metrics, administrative & inspection scores, dates of eligibility for Minimum (سنتان ونصف), Medium (3 سنوات), Maximum (3.5 سنة), and Joint Committee visa block.
- [x] **Interactive Filament Livewire Cockpit (`StaffCareerHub`):**
      - Grouped under `__('الإدارة المدرسية والرقمنة والأرشيف')` with icon `heroicon-o-identification`.
      - 4 reactive tabs:
        1. **Evaluations (التنقيط والتقييم السنوي):** Academic session filter, live KPI cards (evaluated count, average score, high performers), interactive scoring modal (4 subscores, auto-sum, pedagogical score, appreciation, notes), and official printable sheet shortcuts.
        2. **Advancements (جدول الترقية في الدرجات - CAP):** Year filter, one-click eligibility sync action, comprehensive candidate table, CAP approval action, CSV export, and official landscape print/PDF shortcuts.
        3. **Movements (حركات الموظفين والاستيداع):** Ledger of transfers, secondments, availability leaves, retirements, with decision numbers and dates.
        4. **Profiles (السجلات والبطاقات المهنية):** Complete civil service directory with registration numbers, NIN, recruitment dates, step dates, and general seniority metrics.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - 224 translation keys populated in `lang/en.json` and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 10 comprehensive feature tests in `tests/Feature/StaffCareerTest.php` passing with 100% green status (58 assertions).
      - Strict role navigation tailoring verified in `tests/Feature/RoleNavigationTailoringTest.php`.
      - Code style verified with `vendor/bin/pint`.

## 46. Digital Administrative & Pedagogical Archive, Handover/Takeover Registers, and Official Mail Ledger (منظومة تسيير الأرشيف الإداري والتربوي الرقمي، محاضر تنصيب الموظفين الجدد، محاضر تسليم المهام الرسمية، وسجل البريد الصادر والوارد - Passation de Consignes, Procès-verbaux d'Installation & Courrier Officiel) (100%)

Full statutory compliance with Algerian Executive Decrees and National Education circulars governing institutional records, personnel installation, administrative succession, and official correspondence registers:
- [x] **Database Schema & Archival Tables (54 core tables):**
      - Migration `2026_11_17_010001_create_administrative_archive_tables.php` creating 3 dedicated administrative tables:
        - `staff_installations`: tracking new personnel installations (`user_id`, `installation_date`, `recruitment_order_ref`, `role_label`, `statutory_grade`, `previous_institution`, `ministry_decision_ref`, `notes`, `recorded_by`).
        - `administrative_handovers`: official handover/takeover minutes (`role`, `handover_date`, `outgoing_user_id`, `incoming_user_id`, `outgoing_name`, `incoming_name`, `inventory_summary`, `inventory_ref`, `pending_dossiers`, `remarks`, `outgoing_signed`, `incoming_signed`, `director_signed`, `recorded_by`).
        - `official_mails`: ministerial and local mail ledgers (`mail_number`, `mail_type`, `mail_date`, `sender_name`, `recipient_name`, `subject`, `reference_number`, `confidentiality`, `attachment_path`, `response_deadline`, `status`, `notes`, `assigned_to`, `recorded_by`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding verification to 54 tables: `[PASS] schema: All required tables present (54)`.
- [x] **Domain Enums & Models:**
      - Enums: `OfficialMailType` (Incoming, Outgoing) with statutory number prefixes (`ARR/` / `DEP/`), `MailConfidentiality` (Normal, Confidential, Urgent), `MailStatus` (Pending, Processed, Archived), and `HandoverRole` (Principal, Censor, FinanceOfficer).
      - Models: `StaffInstallation`, `AdministrativeHandover`, `OfficialMail` with full casts, relationship bindings, overdue tracking (`isOverdue()`), and search scopes.
      - User model relationships: `installations()`, `outgoingHandovers()`, `incomingHandovers()`, `assignedMails()`, and permission gate `canManageAdministrativeArchive(): bool`.
- [x] **Domain Service Layer (`AdministrativeArchiveService`):**
      - Personnel installation workflow: `recordInstallation()`, `assembleInstallationSheetData()`.
      - Handover/Takeover workflow: `recordHandover()`, `signHandover()`, `assembleHandoverSheetData()`.
      - Official Mail workflow: sequential reference formatting (`generateMailNumber`), mail registration (`registerMail`), status management (`updateMailStatus`), overdue deadline detection, and ledger aggregation (`assembleMailLedgerData`).
      - Comprehensive KPI aggregations: `getInstallationStats()`, `getHandoverStats()`, and `getMailStats()`.
- [x] **Controller Actions & Secure Routing (`AdministrativeArchiveController`, `routes/web.php`):**
      - 6 dedicated routes for official sheet previews and high-fidelity PDF stream downloads via `BrowsershotFactory` with resilient HTML fallback:
        - `print.admin-archive.installation-sheet` (`/{locale}/print/admin-archive/installation/{installation}`)
        - `print.admin-archive.installation-sheet.pdf` (`/{locale}/print/admin-archive/installation/{installation}/pdf`)
        - `print.admin-archive.handover-sheet` (`/{locale}/print/admin-archive/handover/{handover}`)
        - `print.admin-archive.handover-sheet.pdf` (`/{locale}/print/admin-archive/handover/{handover}/pdf`)
        - `print.admin-archive.mail-ledger` (`/{locale}/print/admin-archive/mail-ledger/{type}/{year}`)
        - `print.admin-archive.mail-ledger.pdf` (`/{locale}/print/admin-archive/mail-ledger/{type}/{year}/pdf`)
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/staff-installation-sheet.blade.php`: Procès-verbal d'installation (محضر تنصيب رسمي - A4 Portrait) compliant with Algerian Ministry of National Education statutory template, featuring Algerian institutional header, employee civil and professional identifiers, recruitment order and ministry decision references, and legal notice of taking office.
      - `resources/views/print/administrative-handover-sheet.blade.php`: Procès-verbal de passation de consignes (محضر تسليم واستلام المهام - A4 Portrait) for administrative leadership (Principal, Censor, Finance Officer), featuring physical inventory handover, pending dossiers, institutional stamps, and tri-partite signature visas (Outgoing, Incoming, Director/Inspector).
      - `resources/views/print/official-mail-ledger.blade.php`: Official Mail Register (سجل البريد الصادر والوارد - A4 Landscape) formatted with ministerial sequential number, receipt/dispatch dates, source/destination authority, subject summary, confidentiality, response deadline, status, and administrative visa box.
- [x] **Interactive Filament Livewire Cockpit (`AdministrativeArchiveHub`):**
      - Integrated under `__('الإدارة المدرسية والرقمنة والأرشيف')` with icon `heroicon-o-archive-box-arrow-down` (sort order 7).
      - 3 reactive tabs:
        1. **Staff Installations (محاضر التنصيب):** Live KPI metrics (total installations, latest record, current session count), search bar, new installation modal, and single-click access to official printable sheets and PDF downloads.
        2. **Administrative Handovers (محاضر تسليم واستلام المهام):** Role selector (مدير، ناظر، مقتصد), handover log, digital signing actions (تأشيرة المسلِّم، المستلِم، والمدير), inventory references, and official print shortcuts.
        3. **Official Mail (سجل البريد الصادر والوارد):** Toggle between Incoming (الوارد) and Outgoing (الصادر), year selector, live stats (pending, processed, urgent, overdue), search, register mail modal, status update actions, and full-year official ledger print/PDF generator.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Full tri-lingual localization across Arabic, English, and French in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 22 comprehensive feature tests in `tests/Feature/AdministrativeArchiveTest.php` passing with 100% green status (49 assertions).
      - Health check verified: `[PASS] schema: All required tables present (54)`.
      - Code standards enforced via `vendor/bin/pint`.

## 47. Official Class Councils Deliberation Ledger, Subject Coordination Protocols & Curriculum Pacing Audit System (منظومة محاضر مداولات مجالس الأقسام الرسمية، بروتوكولات التنسيق البيداغوجي، وتدقيق نسب إنجاز المناهج والتدرج السنوي - Procès-Verbaux des Conseils de Classe, Coordination Pédagogique & Suivi d'Avancement des Programmes) (100%)

Full statutory alignment with Algerian Ministerial Decrees 157 and 158 governing Teaching Councils, Class Councils, pedagogical coordination protocols, and curriculum progression audits:
- [x] **Database Schema & Deliberation Tables (58 core tables):**
      - Migration `2026_11_18_010001_create_class_council_and_pedagogical_coordination_tables.php` adding 4 dedicated domain tables:
        - `class_council_sessions`: official quarterly council sessions (`classroom_id`, `trimester`, `academic_year`, `session_date`, `president_id`, `main_teacher_id`, `counselor_id`, `supervisor_id`, `student_delegates`, `general_appreciation`, `class_average`, `highest_average`, `lowest_average`, `success_rate`, `total_students`, `status`, `council_decisions_summary`, `recorded_by`).
        - `class_council_decisions`: student-by-student official council decisions (`class_council_session_id`, `student_id`, `trimester_average`, `annual_average`, `rank`, `honors`, `sanctions`, `end_of_year_decision`, `orientation_stream`, `remarks`).
        - `subject_coordination_protocols`: periodic subject coordination meeting minutes (`academic_year`, `subject_id`, `trimester`, `meeting_date`, `coordinator_id`, `attendees`, `topic`, `syllabus_pacing_notes`, `unified_exams_notes`, `difficulties_and_remediations`, `recommendations`, `recorded_by`).
        - `curriculum_pacing_audits`: quarterly syllabus execution audits per classroom and subject (`academic_year`, `trimester`, `audit_date`, `classroom_id`, `subject_id`, `teacher_id`, `expected_resources_count`, `completed_resources_count`, `execution_percentage`, `pacing_status`, `delay_reasons`, `compensatory_plan`, `audited_by`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding core schema from 54 to 58 tables: `[PASS] schema: All required tables present (58)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `ClassCouncilSessionStatus`: `Draft`, `Deliberated`, `Certified` with bilingual labels and tone badges.
      - `CouncilHonors`: `None`, `TableauHonneur` (>=12.0), `Encouragement` (>=14.0), `Felicitations` (>=15.0).
      - `CouncilSanctions`: `None`, `AvertissementTravail` (<8.0), `BlameTravail` (<6.0), `AvertissementConduite`, `BlameConduite`.
      - `EndOfYearDecision`: `None`, `Admis`, `Redouble`, `Reoriente`, `Exclu` with statutory Algerian terminology.
      - `PacingStatus`: `Ahead`, `OnTrack`, `MinorDelay`, `CriticalDelay` with execution rate classifier.
- [x] **Domain Service Layer (`ClassCouncilDeliberationService`):**
      - `getOrCreateSession()`: creates or resolves the session for classroom, trimester, and academic year.
      - `syncDecisionsFromGrades()`: automatically calculates student averages from assessments, assigns ranks, determines statutory honors and academic reprimands, evaluates class statistics (class average, highest, lowest, pass rate), and updates session status.
      - `certifySession()`: officially locks and certifies the class council deliberation minutes.
      - `recordSubjectCoordination()`: records subject team meetings, unified testing criteria, and remedial strategies.
      - `auditCurriculumPacing()`: scans daily logs and compares actual completed knowledge resources against expected ministerial progression to detect syllabus pacing delays.
      - `assembleClassCouncilPvData()`, `assembleCoordinationPvData()`, `assemblePacingAuditData()`.
- [x] **Controller Actions & Secure Routing (`ClassCouncilDeliberationController`, `routes/web.php`):**
      - 6 dedicated routes for official sheet previews and high-fidelity PDF stream downloads via `BrowsershotFactory` with resilient HTML fallback:
        - `print.class-council-deliberation.pv` (`/{locale}/print/class-council-deliberation/{session}`)
        - `print.class-council-deliberation.pv.pdf` (`/{locale}/print/class-council-deliberation/{session}/pdf`)
        - `print.subject-coordination.pv` (`/{locale}/print/subject-coordination/{protocol}`)
        - `print.subject-coordination.pv.pdf` (`/{locale}/print/subject-coordination/{protocol}/pdf`)
        - `print.curriculum-pacing-audit` (`/{locale}/print/curriculum-pacing-audit/{year}/{trimester}`)
        - `print.curriculum-pacing-audit.pdf` (`/{locale}/print/curriculum-pacing-audit/{year}/{trimester}/pdf`)
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/class-council-pv.blade.php`: Procès-verbal de délibération du conseil de classe (محضر مداولات مجلس القسم الرسمي - القرار الوزاري 158 - A4 Landscape) featuring ministerial header, council leadership composition, statistical distribution cards, full student roster with ranks, averages, honors, sanctions, and end-of-year decisions, general council appreciation, and multi-member signature visas.
      - `resources/views/print/subject-coordination-pv.blade.php`: Procès-verbal de coordination pédagogique (محضر جلسة التنسيق البيداغوجي لمدرسي المادة - القرار 157 - A4 Portrait) featuring subject coordinator, attendee teachers, syllabus pacing review, unified exam agreements, learning difficulties and remedial plan, and teacher signatures.
      - `resources/views/print/curriculum-pacing-audit-sheet.blade.php`: Bilan de progression des programmes (كشف تدقيق ومتابعة نسب إنجاز المناهج والتدرج السنوي - A4 Landscape) detailing classroom, subject, teacher, expected lessons, completed logbook lessons, execution %, status badge, and compensatory remedial calendar.
- [x] **Interactive Filament Livewire Cockpit (`ClassCouncilHub`):**
      - Enhanced existing `ClassCouncilHub` under `__('التقويمات والامتحانات والمجالس')` with 3 dynamic tabs:
        1. **Class Council Sessions (مجالس الأقسام ومحاضر المداولات):** Classroom and trimester selectors, live KPI cards, one-click deliberation calculation and grade sync action, student decision matrix, official certification action, and instant print/PDF shortcuts.
        2. **Subject Coordination (التنسيق البيداغوجي لمدرسي المادة):** Subject and trimester filters, modal for recording new coordination sessions, protocols ledger, and printable minutes.
        3. **Curriculum Pacing Audit (تدقيق وتدرج المناهج السنوي):** Trimester and level filters, automated audit executor, execution progress table, status badges, and official audit ledger print.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - 74 translation keys added to `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 15 comprehensive feature tests in `tests/Feature/ClassCouncilDeliberationTest.php` passing 100% green (51 assertions).
      - Backward compatibility verified with 5 tests in `tests/Feature/ClassCouncilTest.php` passing 100% green (42 assertions).
      - Strict role navigation tailoring verified in `tests/Feature/RoleNavigationTailoringTest.php`.
      - Code style verified with `vendor/bin/pint`.

## 48. Permanent & Special School Inventory, Material Allocations & Asset Decommissioning System (منظومة سجل الجرد الدائم العام والخاص، بطاقات تخصيص العتاد، ومحاضر الإسقاط والإتلاف - Inventaire Permanent du Mobilier et Matériel Scolaire) (100%)

Full statutory alignment with Algerian National Education Circular 143/97 and Ministry of Finance regulations governing public property inventory, room allocations, and asset reform in educational institutions:
- [x] **Database Schema & Inventory Tables (61 core tables):**
      - Migration `2026_11_19_010001_create_school_inventory_tables.php` creating 3 dedicated tables:
        - `inventory_items`: permanent general and special inventory registry (`inventory_number`, `category`, `designation`, `specifications`, `acquisition_date`, `acquisition_source`, `acquisition_cost`, `quantity`, `location_type`, `location_name`, `custodian_id`, `condition`, `is_reformed`, `reformed_at`, `reform_reason`, `notes`, `recorded_by`).
        - `inventory_allocations`: room and science lab allocation cards (`allocation_ref`, `room_name`, `room_type`, `responsible_user_id`, `academic_year`, `items_count`, `condition_verified`, `notes`, `signed_at`, `recorded_by`).
        - `inventory_reforms`: reform and asset decommissioning commission minutes (`reform_number`, `reform_date`, `academic_year`, `committee_president_id`, `total_items_count`, `total_estimated_value`, `commission_observations`, `domains_notification_date`, `status`, `recorded_by`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding core schema from 58 to 61 tables: `[PASS] schema: All required tables present (61)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `InventoryCategory`: `Furniture` (01), `ScientificLab` (02), `AudiovisualIt` (03), `Sports` (04), `LibraryDocs` (05), `KitchenRefectory` (06) with statutory category codes and colors.
      - `InventoryCondition`: `Good`, `Fair`, `Damaged`, `Reformed`, `Lost` with condition labels and badge tones.
      - `InventoryReformStatus`: `Proposed`, `ApprovedByBoard`, `Discharged` tracking the official disposal lifecycle.
- [x] **Domain Service Layer (`SchoolInventoryService`):**
      - Statutory inventory numbering generator conforming to Circular 143/97: `category_code/sequential_number/year` (e.g. `02/0001/2026`).
      - Statutory recolement numbering generator: `REC/{year}/{seq}` (e.g. `REC/2026/001`).
      - Asset registration (`registerItem`), condition updating (`updateCondition`), room allocations with custody nature (`recordAllocation`), periodic verification and discrepancies resolution (`recordRecolement`), and reform execution (`recordReform`).
      - KPI metrics engine (`getInventoryKpis`): active items count, total estimated valuation in DZD, scientific lab assets, furniture & office assets, IT/audiovisual items, verification recolements, and reform counts.
      - Printable data assemblers: `assembleGeneralLedgerData`, `assembleAllocationSheetData`, `assembleCustodyVoucherData`, `assembleRecolementData`, `assembleReformPvData`.
- [x] **Controller Actions & Secure Routing (`SchoolInventoryController`, `routes/web.php`):**
      - 10 dedicated routes for official previews and high-fidelity PDF stream downloads via `BrowsershotFactory`:
        - `print.inventory.general-ledger` (`/{locale}/print/inventory/general-ledger/{category?}`)
        - `print.inventory.general-ledger.pdf` (`/{locale}/print/inventory/general-ledger/{category?}/pdf`)
        - `print.inventory.allocation-sheet` (`/{locale}/print/inventory/allocation/{allocation}`)
        - `print.inventory.allocation-sheet.pdf` (`/{locale}/print/inventory/allocation/{allocation}/pdf`)
        - `print.inventory.custody-voucher` (`/{locale}/print/inventory/allocation/{allocation}/custody`)
        - `print.inventory.custody-voucher.pdf` (`/{locale}/print/inventory/allocation/{allocation}/custody/pdf`)
        - `print.inventory.recolement` (`/{locale}/print/inventory/recolement/{recolement}`)
        - `print.inventory.recolement.pdf` (`/{locale}/print/inventory/recolement/{recolement}/pdf`)
        - `print.inventory.reform-pv` (`/{locale}/print/inventory/reform/{reform}`)
        - `print.inventory.reform-pv.pdf` (`/{locale}/print/inventory/reform/{reform}/pdf`)
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/inventory-general-ledger.blade.php`: Grand livre d'inventaire permanent général et spécial (سجل الجرد الدائم العام والخاص المعتمد - A4 Landscape) formatted per Circular 143/97 with statutory registration numbers, acquisition origins, costs, and multi-partite visa blocks (Bursar, Principal, Custodian).
      - `resources/views/print/inventory-allocation-sheet.blade.php`: Fiche d'affectation de local (بطاقة تخصيص عتاد الحجرة / المخبر - A4 Portrait) featuring room designation, custodian commitment, itemized inventory, and signature visas.
      - `resources/views/print/inventory-custody-voucher.blade.php`: Fiche et procès-verbal de prise en charge et de décharge de matériel scolaire (سند ومحضر تسليم واستلام عهدة التجهيزات المدرسية والمكتبية والمخبرية - A4 Portrait) with legal custody undertaking, itemized inventory, and tripartite signatures (Custodian, Bursar, Principal).
      - `resources/views/print/inventory-recolement-sheet.blade.php`: Fiche de récolement et procès-verbal de constatation périodique de conformité (بطاقة ومحضر المطابقة والمعاينة السنوية للتجهيزات المدرسية - A4 Landscape) featuring theoretical vs physical counts, conform/missing/surplus/damaged audits, and inspection committee visas.
      - `resources/views/print/inventory-reform-pv.blade.php`: Procès-verbal de commission de réforme (محضر معاينة وإسقاط التجهيزات غير الصالحة للاستعمال - A4 Portrait) with inspection committee members, technical depreciation causes, total estimated value, and State Property recommendation.
- [x] **Interactive Filament Livewire Cockpit (`SchoolInventoryHub`):**
      - Registered under `__('المصالح المالية والكتاب المدرسي')` with icon `heroicon-o-cube` (sort order 3).
      - 4 interactive tabs:
        1. **Inventory Ledger (سجل الجرد الدائم العام والخاص):** Real-time financial valuation cards, special inventory quick filter pills (Scientific/Lab, Office, IT/Audiovisual, Sports, Library, Refectory), Add Item modal, and instant print/PDF ledger actions.
        2. **Location Allocations & Custody (بطاقات التخصيص وسجل جرد العهدة للمواقع):** Grid of room and lab custody cards, custodian assignment, custody nature indicators, item counts, print allocation sheet, and print custody voucher.
        3. **Periodic Verification & Conformity (بطاقات ومحاضر المطابقة والمعاينة السنوية):** Recolement ledger, New Recolement modal, discrepancy tracking, and print/PDF verification minutes.
        4. **Asset Reforms (محاضر الإسقاط والإتلاف):** Multi-item decommissioning workflow, committee president assignment, observations, and official reform PV generation.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Full tri-lingual dictionary populated in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 23 comprehensive feature tests in `tests/Feature/SchoolInventoryTest.php` passing 100% green (71 assertions).
      - Health check verified: `[PASS] schema: All required tables present (69)`.
      - Strict role navigation tailoring verified in `tests/Feature/RoleNavigationTailoringTest.php`.
      - Code style verified with `vendor/bin/pint`.

## 49. School Budget & Financial Public Accounting System (منظومة تسيير الميزانية والمحاسبة المدرسية وسجل الالتزامات وحوالات الدفع - Budget Scolaire et Comptabilité Publique) (100%)

Full statutory alignment with Algerian Public Accounting Law 90-21 and Executive Decree 91-311 governing financial accounting and budgetary management in public educational institutions:
- [x] **Database Schema & Financial Tables (64 core tables):**
      - Migration `2026_11_20_010001_create_school_budget_and_accounting_tables.php` creating 3 core tables:
        - `school_budget_lines`: standard chapters and articles for expenditures (Dépenses) and revenues (Recettes), allocated credits, commitments, and liquidations.
        - `budget_commitments`: official purchase orders (Bons de Commande) and commitments ledger (ENG) with supplier data, tax registration (NIF), order subject, estimated amounts, and status.
        - `payment_mandates`: payment mandates (MAND) and liquidation orders with invoice details, reception slip numbers, net liquidated amounts, payment methods (Treasury, Cheque, Cash), and authorization visas.
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding verified schema to 64 domain tables: `[PASS] schema: All required tables present (64)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `BudgetType`: `Expenditure` (نفقات التسيير والتجهيز), `Revenue` (الإيرادات والمداخيل) with statutory classification.
      - `CommitmentStatus`: `Committed` (ملتزم به قيد الإنجاز), `Liquidated` (مصفى ومسدد), `Cancelled` (ملغى).
      - `MandateStatus`: `Pending`, `Approved` (مؤشر ومقبول للدفع), `Paid` (تم التحويل والدفع), `Rejected`.
      - `PaymentMethod`: `TreasuryTransfer` (تحويل عبر الخزينة العمومية), `Cheque` (شيك بريدي / بنكي), `Cash` (نقداً من صندوق المؤسسة).
- [x] **Domain Service Layer (`SchoolBudgetService`):**
      - `seedStandardBudgetGrid`: automatic generation of statutory chapters and articles conforming to Executive Decree 91-311 (staff charges, educational supplies, food & canteen, maintenance, state subsidies, self-financing).
      - Sequential numbering generators: `BC/{year}/{seq}` for purchase orders, `ENG/{year}/{seq}` for commitments, `MAND/{year}/{seq}` for payment mandates.
      - Transaction-safe operations: `recordCommitment` with automated budget line commitment incrementation; `recordPaymentMandate` with commitment status liquidation and liquidated amount incrementation.
      - Financial KPIs engine: total allocated credits, committed expenditures, liquidated amounts, remaining uncommitted balance, and consumption percentage rate.
      - High-fidelity printable data assemblers: `assembleBudgetLedgerData`, `assemblePurchaseOrderData`, `assemblePaymentMandateData`.
- [x] **Controller Actions & Secure Routing (`SchoolBudgetController`, `routes/web.php`):**
      - 6 dedicated routes for official sheet previews and PDF streaming via `BrowsershotFactory` with HTML fallback:
        - `print.budget.ledger` (`/{locale}/print/budget/ledger/{fiscalYear?}`)
        - `print.budget.ledger.pdf` (`/{locale}/print/budget/ledger/{fiscalYear?}/pdf`)
        - `print.budget.purchase-order` (`/{locale}/print/budget/purchase-order/{commitment}`)
        - `print.budget.purchase-order.pdf` (`/{locale}/print/budget/purchase-order/{commitment}/pdf`)
        - `print.budget.mandate` (`/{locale}/print/budget/mandate/{mandate}`)
        - `print.budget.mandate.pdf` (`/{locale}/print/budget/mandate/{mandate}/pdf`)
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/school-budget-execution-ledger.blade.php`: État d'exécution du budget scolaire (كشف تنفيذ ميزانية المؤسسة وحساب التسيير - A4 Landscape) detailing chapters, articles, allocations, commitments, liquidations, balances, and multi-member audit signatures (Bursar, Principal, Board).
      - `resources/views/print/school-purchase-order.blade.php`: Bon de Commande officiel (سند طلب بضائع وخدمات - A4 Portrait) with supplier tax details, budget chapter imputation, estimated amounts, and Principal/Bursar visas.
      - `resources/views/print/school-payment-mandate.blade.php`: Mandat de Paiement (حوالة دفع وتصفية نفقة - A4 Portrait) with invoice details, reception slips, liquidation certificates, payment reference, and Ordonnateur/Comptable signatures.
- [x] **Interactive Filament Livewire Cockpit (`SchoolBudgetHub`):**
      - Registered under `__('المصالح المالية والكتاب المدرسي')` with icon `heroicon-o-banknotes` (sort order 4).
      - 3 interactive tabs:
        1. **Budget Map & Allocations (خريطة الميزانية والاعتمادات):** Fiscal year selector, seed standard grid action, real-time consumption progress bars, and Add Budget Line modal.
        2. **Purchase Orders & Commitments (سندات الطلب وسجل الالتزامات):** Ledger of all commitments, Add Bon de Commande modal with auto-numbering, status tracking, and instant print/PDF actions.
        3. **Payment Mandates & Liquidations (حوالات الدفع وتصفية النفقات):** Liquidation workflow linked to committed orders, payment method tracking, and printable mandates.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Complete tri-lingual dictionary populated in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 17 comprehensive feature tests in `tests/Feature/SchoolBudgetTest.php` passing 100% green (54 assertions).
      - Schema integrity verified by `sajel:health-check`: `[PASS] schema: All required tables present (64)`.
      - Backward compatibility verified with 15 tests in `SchoolInventoryTest.php` (40 assertions).
      - Strict role navigation tailoring verified in `RoleNavigationTailoringTest.php`.
      - Code style verified with `vendor/bin/pint`.

## 50. Official National Examination Center Operations, Proctoring Staffing & Candidate Management System (منظومة تسيير مراكز الامتحانات الرسمية BEM / BAC، تأطير الحراسة، مخططات قاعات الامتحان، ومحاضر فتح الأظرفة والغش - Gestion des Centres d'Examens Officiels) (100%)

Full statutory alignment with Algerian National Education and ONEC (Office National des Examens et Concours) regulations, ministerial instructions, and Executive Decree 20-241 on the integrity of official national examinations (BEM, BAC, Primary Attainment):
- [x] **Database Schema & Official Examination Tables (68 core tables):**
      - Migration `2026_11_21_010001_create_official_examination_center_tables.php` creating 4 core domain tables:
        - `official_exam_centers`: center registry (`exam_type`, `session_year`, `center_code`, `center_name`, `head_user_id`, `vice_head_name`, `secretariat_chief_name`, `candidate_count`, `room_count`, `start_date`, `end_date`, `status`, `notes`).
        - `official_exam_rooms`: standardized exam rooms with candidate seating allocation (`center_id`, `room_number`, `room_label`, `physical_location`, `candidate_start_number`, `candidate_end_number`, `candidate_count`, `supervisors_required`, `notes`).
        - `official_exam_supervisors`: proctoring staff roster (`center_id`, `room_id`, `user_id`, `supervisor_name`, `origin_institution`, `duty_role`, `attendance_status`, `replacement_name`, `convocation_reference`).
        - `official_exam_incidents`: statutory minutes and disciplinary documentation (`center_id`, `room_id`, `incident_number`, `incident_type`, `incident_date`, `session_time`, `subject_name`, `candidate_registration_number`, `candidate_name`, `description`, `seized_evidence`, `measures_taken`, `reported_by`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding verified schema to 68 domain tables: `[PASS] schema: All required tables present (68)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `OfficialExamType`: `Bem` (شهادة التعليم المتوسط - BEM), `Bac` (شهادة البكالوريا - BAC), `PrimaryEval` (تقييم مكتسبات مرحلة التعليم الابتدائي) with standard abbreviations and badge tones.
      - `OfficialExamCenterStatus`: `Planning` (قيد التحضير والتنظيم), `Active` (الامتحان جارٍ رسمياً), `Completed` (مكتمل ومغلق).
      - `OfficialExamDutyRole`: `RoomChief` (رئيس القاعة المسؤول), `RoomSupervisor` (أستاذ حارس بالقاعة), `CorridorGuard` (حارس ممر وجناح), `Secretariat` (عضو الأمانة العامة للمركز), `Reserve` (أستاذ حارس احتياطي).
      - `OfficialExamIncidentType`: `EnvelopeOpening` (محضر فتح وتأكيد سلامة أظرفة المواضيع), `CheatingAttempt` (محضر إثبات محاولة غش [المرسوم 20-241]), `CandidateAbsence`, `MedicalIncident`, `DisciplinaryExclusion`.
- [x] **Domain Service Layer (`OfficialExamCenterService`):**
      - Automatic center code generator: `CEN-{TYPE}-{YEAR}-{SEQ}`.
      - Sequential incident and PV reference generator: `INC/{TYPE}/{YEAR}/{SEQ}`.
      - Automated room batch generator (`generateRooms`): automatically materializes $N$ standard rooms with 20 candidates each, continuous roll call intervals (`candidate_start_number` ⟶ `candidate_end_number`), and updates center totals.
      - Telemetry metrics engine (`getCenterTelemetry`): total rooms, total candidates, proctor counts by role, required supervisor capacity, and coverage percentage.
      - High-fidelity printable data assemblers: `assembleCenterRosterData`, `assembleEnvelopeOpeningPvData`, `assembleIncidentPvData`.
- [x] **Controller Actions & Secure Routing (`OfficialExamCenterController`, `routes/web.php`):**
      - 6 dedicated routes for official sheet previews and PDF streaming via `BrowsershotFactory` with resilient HTML fallback:
        - `print.official-exam.center-roster` (`/{locale}/print/official-exam/center/{center}/roster`)
        - `print.official-exam.center-roster.pdf` (`/{locale}/print/official-exam/center/{center}/roster/pdf`)
        - `print.official-exam.envelope-pv` (`/{locale}/print/official-exam/incident/{incident}/envelope-pv`)
        - `print.official-exam.envelope-pv.pdf` (`/{locale}/print/official-exam/incident/{incident}/envelope-pv/pdf`)
        - `print.official-exam.incident-pv` (`/{locale}/print/official-exam/incident/{incident}/pv`)
        - `print.official-exam.incident-pv.pdf` (`/{locale}/print/official-exam/incident/{incident}/pv/pdf`)
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/official-exam-center-roster.blade.php`: Tableau général d'affectation des salles et de la surveillance (كشف توزيع القاعات والمترشحين ومخطط تأطير الحراسة والأمانة - A4 Landscape) formatted per ONEC standards with room locations, candidate registration number ranges, room chiefs, assistant proctors, and multi-partite visa blocks (Center Head, Ministerial Observer).
      - `resources/views/print/official-exam-envelope-pv.blade.php`: Procès-verbal d'ouverture des plis de sujets (محضر فتح أظرفة مواضيع الامتحانات الرسمية وتأكيد سلامتها - A4 Portrait) featuring sealed wax verification, 2 candidate witnesses, room chief, and center head authentication.
      - `resources/views/print/official-exam-incident-pv.blade.php`: Procès-verbal de constat de fraude et d'incident (محضر إثبات محاولة غش / إخلال بسير الامتحان الرسمي - المرسوم التنفيذي 20-241 - A4 Portrait) detailing candidate registration number, subject, seized forbidden devices (phones/earphones/cheat sheets), eyewitness statements, and disciplinary commission report.
- [x] **Interactive Filament Livewire Cockpit (`OfficialExamCenterHub`):**
      - Registered under `__('التقويمات والامتحانات والمجالس')` with icon `heroicon-o-academic-cap` (sort order 3).
      - 3 interactive tabs:
        1. **Rooms & Candidate Allocation (قاعات الامتحان وتوزيع المترشحين):** Center selector, live telemetry cards, room grid with candidate numbering range and capacity, and automated room generator action.
        2. **Supervision Staff Roster (تأطير الحراسة والأمانة العامة):** Supervisors table with origin school, assigned room, duty role, add/assign supervisor modal, and instant print/PDF center roster shortcuts.
        3. **Official Minutes & Incidents (محاضر الأظرفة ووقائع الامتحان [20-241]):** Minutes ledger, envelope opening PVs, cheating/incident workflow with seized evidence fields, and instant print/PDF PV actions.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Complete tri-lingual dictionary populated in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 18 comprehensive feature tests in `tests/Feature/OfficialExamCenterTest.php` passing 100% green (52 assertions).
      - Schema integrity verified by `sajel:health-check`: `[PASS] schema: All required tables present (68)`.
      - Strict role navigation tailoring verified in `RoleNavigationTailoringTest.php`.
      - Code style verified with `vendor/bin/pint`.

## 51. School Social Solidarity, 5,000 DZD Special School Allowance & Free Textbooks/Supplies Program (منظومة التضامن المدرسي، منحة التمدرس الخاصة 5000 دج ومجانية الكتاب والحقيبة المدرسية - Prime de Scolarité 5 000 DA et Solidarité Sociale) (100%)

Full statutory alignment with Algerian legislation, notably **Executive Decree No. 21-61** (المرسوم التنفيذي 21-61 المؤرخ في 08 فبراير 2021 المحدد لشروط وكيفيات منح منحة التمدرس الخاصة) and Joint Ministerial Circulars of the Ministry of National Education and Ministry of Interior / Local Authorities on school solidarity, allowance disbursement via postal accounts (CCP), and free provision of educational kits and school bags:
- [x] **Database Schema & Core Domain Tables (expanded to 73 core tables):**
      - Migration `2026_11_23_010001_create_school_solidarity_tables.php` creating 4 domain tables:
        - `school_solidarity_campaigns`: annual campaigns ledger (`campaign_year`, `academic_year`, `title`, `start_date`, `end_date`, `grant_amount` default 5000.00, `status`, `allocated_budget`, `approved_beneficiaries_count`, `total_disbursed_amount`, `notes`, `created_by`).
        - `student_solidarity_beneficiaries`: student dossier and applicant registry (`campaign_id`, `student_id`, `file_number`, `eligibility_category`, `guardian_type`, `guardian_national_id`, `guardian_full_name`, `guardian_profession`, `monthly_income`, `ccp_account_number`, `ccp_key`, `rip_number`, `status`, `rejection_reason`, `payment_reference`, `payment_date`, `submitted_documents`, `notes`, `verified_by`).
        - `school_solidarity_committee_members`: institutional solidarity commission (`campaign_id`, `user_id`, `member_name`, `committee_role`, `institution_role_label`, `attended_deliberation`, `signature_notes`).
        - `school_solidarity_aid_distributions`: in-kind aid delivery register (`campaign_id`, `student_id`, `aid_type`, `voucher_number`, `distribution_date`, `items_description`, `estimated_value`, `donor_entity`, `received_by_guardian`, `guardian_signature_confirmed`, `issued_by`, `notes`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding verified schema to 73 domain tables: `[PASS] schema: All required tables present (73)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `SolidarityCampaignStatus`: `Planning` (قيد الإعداد وتجميع الملفات), `InReview` (قيد التدقيق الإداري والاجتماعي), `Deliberated` (تمت مداولة لجنة المؤسسة), `SubmittedToDaira` (مرسل للجنة الدائرية والمديرية), `Approved` (مؤشر ومصادق عليه رسمياً), `Disbursed` (تم صرف المنحة عبر البريد), `Closed` (مختتم ومؤرشف).
      - `SocialEligibilityCategory`: `Orphan` (يتيم فاقد الأب أو الوالدين معاً), `NeedyNoIncome` (معوز - أسرة بدون أي دخل), `LowIncome` (دخل ضعيف ≤ SNMG), `SpecialNeeds` (ذوو الاحتياجات الخاصة والإعاقة), `NationalTragedy` (ضحايا المأساة الوطنية وحالات خاصة).
      - `SolidarityBeneficiaryStatus`: `Submitted` (ملف مودع), `VerifiedBySchool` (مدقق ومستوفٍ بالمؤسسة), `DeliberatedApproved` (مقبول بمداولة اللجنة), `ApprovedByDaira` (مصادق عليه من الدائرة), `Paid` (تم الصرف والتحويل), `Rejected` (ملف مرفوض).
      - `SolidarityCommitteeRole`: `President` (مدير المؤسسة - رئيس اللجنة), `FinancialReporter` (المقتصد / المسير المالي - المقرر), `CensorMember` (الناظر / مستشار التربية - عضو), `GuidanceMember` (مستشار التوجيه المدرسي - عضو), `TeacherRepresentative` (ممثل هيئة التدريس - عضو), `ParentAssociationRepresentative` (ممثل جمعية أولياء التلاميذ - عضو), `BoardMember` (ممثل مجلس الإدارة والتسيير).
      - `SolidarityAidType`: `FreeTextbooks` (مجانية طقم الكتب المدرسية), `SchoolBag` (الحقيبة والمحفظة المدرسية المجهزة), `StationerySupplies` (مستلزمات الأدوات والكراريس), `ClothingAid` (كسوة التضامن المدرسي), `Other` (إعانات تضامنية ومساعدات عينية أخرى).
- [x] **Domain Service Layer (`SchoolSolidarityService`):**
      - Auto-sequential reference numbering: `SOL/{year}/{seq}` for applicant dossiers, `AID/{year}/{seq}` for delivery vouchers.
      - Automated commission initialization (`initializeDefaultCommittee`): auto-assigns Principal, Financial Officer, Censor, Guidance Counselor, and representatives.
      - Dossier management & workflow: `registerBeneficiary` with student deduplication; `updateBeneficiaryStatus` with instant campaign totals recalculation; `markBeneficiaryPaid` with payment reference and disbursement summation.
      - Real-time telemetry & KPIs engine (`getSolidarityKpis`): applicants, approved, paid, total disbursed volume in DZD, category breakdowns, and aid values.
      - Printable data assemblers: `assembleNominativeListData`, `assembleCommitteePvData`, `assembleAidVoucherData`.
- [x] **Controller Actions & Secure Routing (`SchoolSolidarityController`, `routes/web.php`):**
      - 6 dedicated routes for official Algerian administrative documents with Browsershot PDF streaming and HTML preview:
        - `print.solidarity.nominative-list` & `.pdf` (`/{locale}/print/solidarity/campaign/{campaign}/nominative-list`)
        - `print.solidarity.committee-pv` & `.pdf` (`/{locale}/print/solidarity/campaign/{campaign}/committee-pv`)
        - `print.solidarity.aid-voucher` & `.pdf` (`/{locale}/print/solidarity/aid/{distribution}/voucher`)
      - Protected by strict permission check `User::canManageSolidarity()`.
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/solidarity-nominative-list.blade.php`: État nominatif des bénéficiaires de la prime de scolarité de 5 000 DA (الكشف الإسمي للمستفيدين من منحة التمدرس الخاصة 5000 دج - A4 Landscape) formatted per ministerial standards with student matricule, birth date, eligibility category, guardian national ID, CCP account & key, individual 5,000 DZD allocation, totals, and tripartite signatures (Bursar, Principal, Daira / Directorate Commission).
      - `resources/views/print/solidarity-committee-pv.blade.php`: Procès-verbal des délibérations de la commission de solidarité scolaire (محضر مداولات لجنة التضامن المدرسي بالمؤسسة - A4 Portrait) featuring statutory legal references (Loi 08-04, Décret 21-61), quorum validation, summary statistics of examined files, category breakdown table, deliberation clauses, and full committee members signatures table.
      - `resources/views/print/solidarity-aid-voucher.blade.php`: Bon de décharge et récépissé de remise de gratuité (سند ومحضر تسليم واستلام مجانية المستلزمات والكتب المدرسية - A4 Portrait) detailing student, guardian, in-kind aid specification, legal undertaking of receipt and maintenance, and multi-partite validation.
- [x] **Interactive Filament Livewire Cockpit (`SchoolSolidarityHub`):**
      - Registered under `__('المصالح المالية والكتاب المدرسي')` with icon `heroicon-o-heart` (sort order 5).
      - 4 interactive tabs:
        1. **حملات منحة التمدرس (5000 دج):** Campaign switcher, 5 live telemetry KPI cards, campaign status pill, quick buttons to print/PDF the official Nominative CCP list and Committee PV, and Launch Campaign modal.
        2. **ملفات التلاميذ وقوائم الاستحقاق:** Searchable & filterable applicant registry, eligibility category badges, guardian CCP info, status workflow actions (Approve, Reject with justification, Record Postal Transfer & Pay), and Add Beneficiary Dossier modal.
        3. **لجنة التضامن ومحضر المداولة:** Statutory commission information banner, members roster, deliberation attendance status, Add Member modal, and instant print/PDF PV action.
        4. **مجانية الكتاب والحقيبة المدرسية:** In-kind aid delivery register, Record Aid Distribution modal, and instant print/PDF delivery voucher actions.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Complete tri-lingual dictionary populated in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 17 comprehensive feature tests in `tests/Feature/SchoolSolidarityTest.php` passing 100% green (62 assertions).
      - Strict role navigation tailoring verified in `RoleNavigationTailoringTest.php` (6 tests, 117 assertions).
      - Schema integrity verified by `sajel:health-check`: `[PASS] schema: All required tables present (73)`.
      - Code style verified with `vendor/bin/pint`.

## 52. School Boarding, Dormitories Allocation, Night Supervision & Student Transportation System (منظومة النظام الداخلي، الإيواء والمراقد، المداومة والمذاكرة الليلية، والنقل المدرسي - Régime d'Internat, Dortoirs, Permanences de Nuit et Transport Scolaire) (100%)

Full statutory alignment with Algerian educational legislation on boarding institutions (Decree 91-311 and Ministerial instructions on Internat management, dormitory hygiene, evening supervised study, night educational watch, and municipal school transportation):
- [x] **Database Schema & Core Domain Tables (expanded to 79 core tables):**
      - Migration `2026_11_24_010001_create_school_boarding_and_transport_tables.php` creating 6 domain tables:
        - `dormitory_rooms`: dormitories and pavilions ledger (`wing` [boys/girls], `room_code`, `room_name`, `floor_level`, `bed_capacity`, `active_occupants_count`, `supervisor_in_charge`, `status`, `notes`).
        - `student_dormitory_allocations`: bed and locker allocations (`dormitory_room_id`, `student_id`, `academic_year`, `bed_number`, `locker_number`, `check_in_date`, `check_out_date`, `status`, `linen_received`, `notes`).
        - `boarding_night_sessions`: evening study and night supervision logbook (`academic_year`, `session_date`, `session_type`, `supervisor_user_id`, `supervisor_name`, `boarders_present_count`, `boarders_absent_count`, `sickbay_count`, `authorized_leave_count`, `incident_report`, `recorded_by`).
        - `boarding_exit_passes`: weekend and holiday boarder exit permits (`pass_number`, `student_id`, `academic_year`, `pass_type`, `departure_datetime`, `expected_return_datetime`, `actual_return_datetime`, `escort_guardian_name`, `destination`, `status`, `supervisor_signature_notes`, `authorized_by`).
        - `school_bus_routes`: school transport lines and bus fleet (`route_code`, `route_name`, `bus_license_plate`, `provider_entity`, `driver_name`, `driver_phone`, `chaperone_name`, `passenger_capacity`, `subscribed_students_count`, `morning_departure_time`, `evening_return_time`, `stops_list`, `status`, `notes`).
        - `student_bus_subscriptions`: student bus passes and subscriptions (`bus_route_id`, `student_id`, `academic_year`, `card_number`, `pickup_stop`, `issue_date`, `expiry_date`, `status`, `notes`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding verified schema to 79 domain tables: `[PASS] schema: All required tables present (79)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `DormitoryWing`: `Boys` (جناح الذكور - Pavillon Garçons), `Girls` (جناح الإناث - Pavillon Filles).
      - `DormitoryAllocationStatus`: `Active` (مقيم حالياً بالمرقد), `Suspended` (موقوف مؤقتاً), `Transferred` (محول لمرقد آخر), `Vacated` (أخلى المرقد وسلّم الفراش).
      - `BoardingNightSessionType`: `EveningStudy` (مذاكرة مسائية محروسة), `NightSupervision` (مداومة ليلية بالمراقد), `WeekendDuty` (مداومة نهاية الأسبوع).
      - `BoardingExitPassType`: `WeekendLeave` (خروج عطلة نهاية الأسبوع), `HolidayLeave` (خروج العطلة المدرسية الرسمية), `ExceptionalLeave` (رخصة خروج استثنائية), `MedicalLeave` (خروج صحي واستشفائي).
      - `BoardingExitPassStatus`: `Authorized` (مرخص ومقبول), `Departed` (غادر المؤسسة فعلياً), `Returned` (عاد والتحق بالمرقد), `Overdue` (متأخر عن موعد العودة), `Cancelled` (ملغى).
      - `BusRouteStatus`: `Active` (خط عامل بانتظام), `Suspended` (معلق مؤقتاً), `Inactive` (متوقف عن الخدمة).
- [x] **Domain Service Layer (`SchoolBoardingService`):**
      - Auto-sequential reference numbering: `DORT-{W}-{F}{seq}` for room codes, `PASS/{year}/{seq}` for exit permits, `TRANS/{year}/{seq}` for bus subscription cards, and `BUS-L{seq}` for bus routes.
      - Allocation engine: `allocateBed` with duplicate check, capacity increment, and automated student `regime` switch to `boarder`; `vacateBed` with check-out timestamp and active occupants count decrement.
      - Night supervision logger: `recordNightSession` tracking roll call, sickbay transfers, and authorized leaves.
      - Exit permit workflow: `issueExitPass` and `markPassReturned`.
      - Transport engine: `createBusRoute` and `subscribeStudentToBus`.
      - Real-time telemetry & KPIs engine (`getBoardingTelemetry`): total bed capacity, occupied beds, vacant beds, occupancy rate, active boarders, and subscribed bus passengers.
      - Printable data assemblers: `assembleDormitoryRosterData`, `assembleExitPassData`, `assembleBusCardData`.
- [x] **Controller Actions & Secure Routing (`SchoolBoardingController`, `routes/web.php`):**
      - 6 dedicated routes with Browsershot PDF streaming and HTML fallback:
        - `print.boarding.room-roster` & `.pdf` (`/{locale}/print/boarding/room/{room}/roster`)
        - `print.boarding.exit-pass` & `.pdf` (`/{locale}/print/boarding/pass/{pass}/billet`)
        - `print.boarding.bus-card` & `.pdf` (`/{locale}/print/boarding/transport/{subscription}/card`)
      - Protected by strict permission check `User::canManageBoarding()`.
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/dormitory-room-roster.blade.php`: Tableau d'affectation du dortoir et inventaire de literie (مخطط توزيع نزلاء المرقد وجرد الأسرّة والخزانات الفردية - A4 Landscape) detailing wing, floor, bed numbers, locker numbers, student names, matricules, classes, bedding reception status, and tripartite signatures (Wing Supervisor, Bursar, General Supervisor).
      - `resources/views/print/boarding-exit-pass.blade.php`: Billet et autorisation de sortie d'interne (بطاقة ورخصة خروج تلميذ داخلي - A4 Portrait) featuring departure/return timestamps, escort guardian commitment, destination, and visas (General Supervisor, Gate Guard).
      - `resources/views/print/student-bus-card.blade.php`: Carte officielle d'abonnement au transport scolaire (بطاقة اشتراك وركوب النقل المدرسي الرسمية - A4 Portrait badge) with photo box, student credentials, bus route, pickup stop, driver contacts, and official school seal.
- [x] **Interactive Filament Livewire Cockpit (`SchoolBoardingHub`):**
      - Registered under `__('الحياة المدرسية وشؤون التلاميذ')` with icon `heroicon-o-home-modern` (sort order 4).
      - 4 interactive tabs:
        1. **المراقد وتوزيع الأسرّة والخزانات:** Wing filter pills, 5 live telemetry KPI cards, rooms grid with occupancy progress bars, selected room occupants ledger with bed vacating action, Add Room modal, and Allocate Bed modal.
        2. **المداومة والمذاكرة الليلية:** Night study and supervision logbook with present/absent counters, sickbay alerts, and Record Night Session modal.
        3. **رخص الخروج الأسبوعي والعطل:** Exit permits table with search, status badges, return confirmation action, and Issue Exit Pass modal.
        4. **حافلات وخطوط النقل المدرسي:** Bus fleet cards with driver details and schedules, student subscriptions ledger, Add Route modal, and Subscribe Student modal.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Complete tri-lingual dictionary populated in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest`.
      - 17 comprehensive feature tests in `tests/Feature/SchoolBoardingTest.php` passing 100% green (67 assertions).
      - Strict role navigation tailoring verified in `RoleNavigationTailoringTest.php` (6 tests, 117 assertions).
      - Schema integrity verified by `sajel:health-check`: `[PASS] schema: All required tables present (79)`.
      - Code style verified with `vendor/bin/pint`.

## 53. Comprehensive School Physical Security, Internal Security Plan (PSI), Emergency Evacuation Drills & School Accidents Management System (منظومة الوقاية والأمن الداخلي للمؤسسة PSI، تمارين الإخلاء، سجل أجهزة الإطفاء والغاز، والتصريح بالحوادث المدرسية وحوادث العمل - Plan de Sécurité Interne (PSI), Prévention & Accidents Scolaires) (100%)

Full statutory alignment with Algerian legislation on occupational hygiene, safety, major hazards management, civil protection joint drills, and school accidents reporting, notably **Loi n° 88-07** (relative à l'hygiène, à la sécurité et à la médecine du travail), **Loi n° 04-20** (relative à la prévention des risques majeurs et à la gestion des catastrophes), Joint Ministerial Instructions (MEN + DGPC) on the **Plan de Sécurité Interne (PSI)**, and Ministerial Circulars on the mandatory 48-hour declaration of school accidents to the Directorate of Education and school insurance (MAATEC):
- [x] **Database Schema & Core Domain Tables (expanded to 85 core tables):**
      - Migration `2026_11_25_010001_create_school_safety_and_psi_tables.php` creating 6 domain tables:
        - `safety_emergency_plans`: school internal emergency & security plan (`academic_year`, `plan_code`, `title`, `crisis_cell_leader`, `evacuation_coordinator`, `first_aid_leader`, `technical_safety_officer`, `assembly_points`, `emergency_contacts`, `hazardous_zones`, `status`, `approval_date`, `approved_by`, `notes`).
        - `safety_intervention_teams`: crisis response squads & first-aiders (`safety_plan_id`, `team_type`, `member_name`, `institution_role`, `user_id`, `assigned_zone`, `phone_number`, `is_certified_first_aider`, `notes`).
        - `school_safety_incidents`: school & work accidents ledger (`academic_year`, `incident_number`, `incident_type`, `severity_level`, `incident_date`, `location`, `victim_type`, `student_id`, `user_id`, `victim_name`, `circumstances_description`, `bodily_injuries_description`, `immediate_care_administered`, `hospital_evacuation`, `hospital_name`, `medical_certificate_ref`, `temporary_incapacity_days`, `witnesses_statements`, `supervising_personnel_at_time`, `insurance_declared`, `declaration_date`, `directorate_notified`, `status`, `investigating_officer_name`, `notes`).
        - `safety_fire_equipment`: fire extinguishers and gas detection ledger (`equipment_code`, `equipment_type`, `exact_location`, `installation_date`, `last_inspection_date`, `next_inspection_due_date`, `inspection_status`, `inspector_entity`, `notes`).
        - `safety_evacuation_drills`: disaster evacuation simulation ledger (`academic_year`, `drill_code`, `drill_date`, `drill_time`, `simulation_scenario`, `evacuation_time_seconds`, `total_persons_evacuated`, `students_evacuated_count`, `staff_evacuated_count`, `assembly_zone_used`, `civil_protection_partner`, `civil_protection_representatives`, `evaluation_rating`, `bottlenecks_and_remarks`, `corrective_measures`, `status`).
        - `safety_committee_meetings`: hygiene & safety commission (CHS) sessions (`academic_year`, `session_code`, `meeting_date`, `session_type`, `meeting_chair`, `members_present`, `agenda_items`, `deliberation_summary`, `decisions_and_recommendations`, `status`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding verified schema to 85 domain tables: `[PASS] schema: All required tables present (85)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `SafetyIncidentType`: `StudentAccident` (حادث مدرسي لتلميذ - Accident scolaire), `WorkAccident` (حادث عمل لموظف - Accident de travail), `InfrastructureHazard` (خطر هيكلي بالمنشأة - Risque bâtiment), `MaterialDamage` (أضرار مادية وتلف تجهيزات - Dégâts matériels).
      - `SafetyIncidentSeverity`: `Minor` (بسيط / طفيف - Léger), `Moderate` (متوسط / إسعاف أولي - Moyen), `Severe` (خطير / إخلاء مستشفى - Grave), `Critical` (حرج جداً - Critique).
      - `SafetyIncidentStatus`: `Draft` (مسودة قيد التحرير), `Declared` (تم التصريح رسمياً), `InTreatment` (قيد المتابعة الطبية), `Settled` (تمت التسوية والشفاء), `Archived` (مؤرشف).
      - `SafetyEquipmentType`: `ExtinguisherPowder` (مطفأة بودرة جافة ABC), `ExtinguisherCo2` (مطفأة ثاني أكسيد الكربون CO2), `ExtinguisherWater` (مطفأة ماء رغوي), `FireAlarm` (جرس وإنذار حريق صوتي), `EmergencyLighting` (وحدات الإنارة الذاتية للطوارئ BAES), `CoDetector` (كاشف غاز أحادي أكسيد الكربون CO), `GasShutoffValve` (صمام أمان قطع الغاز المركزي).
      - `SafetyEquipmentStatus`: `Operational` (صالح وجاهز للاستعمال), `NeedsRefill` (بحاجة لإعادة شحن وتعبئة), `NeedsMaintenance` (بحاجة للصيانة والتصليح), `OutOfService` (معطل وخارج الخدمة).
      - `SafetyDrillScenario`: `Earthquake` (تمرين وقاية وإخلاء ضد الزلازل - Simulation Séisme), `FireOutbreak` (تمرين إخلاء ضد نشوب حريق - Simulation Incendie), `GasLeak` (تمرين إخلاء ضد تسرب غازي - Simulation Fuite de gaz), `GeneralDisaster` (تمرين إخلاء شامل ضد الأخطار الكبرى).
      - `SafetyInterventionTeamType`: `FirstAid` (فوج الإسعافات الأولية والتضميد), `EvacuationGuide` (فوج التوجيه وتأمين مسالك الإخلاء), `FireContainment` (فوج التدخل الأولي والإطفاء), `SecurityPerimeter` (فوج تأمين المنافذ والمحيط الخارجي).
- [x] **Domain Service Layer (`SchoolSafetyService`):**
      - Sequential reference generators: `ACC/{year}/{seq}` for accidents, `PSI-{$year}-{$seq}` for security plans, `EXT-{$seq}` / `CO-DET-{$seq}` for fire safety equipment, `DRIL-{$year}-{$seq}` for disaster drills, and `CHS-{$year}-{$seq}` for committee sessions.
      - Core operations: `declareAccident` with student/staff resolution and automatic incident numbering; `updateAccidentStatus` with statutory 48-hour insurance declaration and directorate notification timestamps; `createOrUpdateSafetyPlan` with default safe assembly zones, emergency phone directory, and hazard hotspots; `addInterventionMember` with civil protection certification flag; `registerEquipment` and `updateEquipmentInspection` with dynamic expiration and validity extension; `recordEvacuationDrill` with stopwatch elapsed time formatting (`2 د 45 ث`); `recordCommitteeMeeting`.
      - Real-time telemetry & KPIs engine (`getSafetyTelemetry`): total incidents, student accidents, work accidents, hospitalized transfers, pending insurance declarations, total fire equipment, operational units, due/expired units, operational readiness rate (%), drills count, and certified first-aiders count.
      - Printable data assemblers: `assembleAccidentDeclarationData`, `assembleEvacuationDrillPvData`, `assembleSafetyEquipmentRegisterData`.
- [x] **Controller Actions & Secure Routing (`SchoolSafetyController`, `routes/web.php`):**
      - 6 dedicated routes with Browsershot PDF streaming and HTML fallback:
        - `print.safety.accident-declaration` & `.pdf` (`/{locale}/print/safety/incident/{incident}/declaration`)
        - `print.safety.drill-pv` & `.pdf` (`/{locale}/print/safety/drill/{drill}/pv`)
        - `print.safety.equipment-register` & `.pdf` (`/{locale}/print/safety/equipment/register`)
      - Protected by strict permission check `User::canManageSchoolSafety()`.
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/safety-accident-declaration.blade.php`: Déclaration d'accident scolaire et procès-verbal de constat (استمارة التصريح الرسمي بحادث مدرسي / حادث عمل ومحضر المعاينة الإدارية - A4 Portrait) featuring victim credentials, class, matricule, precise date/time/location, detailed narrative of the event, witness statements, bodily injuries description, emergency hospital evacuation details, initial medical certificate reference, ITT days, and tripartite visas (Supervising Teacher, School Bursar, School Principal).
      - `resources/views/print/safety-evacuation-drill-pv.blade.php`: Procès-verbal de l'exercice d'évacuation d'urgence et de simulation (محضر تمرين الإخلاء الافتراضي المشترك للوقاية من الكوارث والأخطار الكبرى - A4 Landscape) detailing simulation scenario, stopwatch duration, total evacuated count, assembly zone compliance, Civil Protection joint delegation attendees, bottlenecks and flow issues observed, corrective measures, and dual signatures (Evacuation Coordinator, Civil Protection Inspector, School Principal).
      - `resources/views/print/safety-equipment-register.blade.php`: Registre de contrôle périodique des extincteurs, détecteurs de gaz et réseaux de sécurité (سجل المراقبة الدورية لأجهزة الإطفاء ومعدات السلامة وشبكة الغاز - A4 Landscape) tracking equipment codes, technical types, building locations, last inspection, next mandatory due date, operational status badges, certified inspecting body, and administrative certifying visas.
- [x] **Interactive Filament Livewire Cockpit (`SchoolSafetyHub`):**
      - Registered under `__('الحياة المدرسية وشؤون التلاميذ')` with icon `heroicon-o-shield-check` (sort order 5).
      - 4 interactive tabs:
        1. **الحوادث والتصريح الرسمي:** 5 live telemetry cards (Total, student accidents, work accidents, hospital evacuations, pending declarations), search and severity/type filters, incidents ledger with severity badges and declaration workflow buttons, Declare Accident modal, and instant Print/PDF official declaration shortcuts.
        2. **أجهزة الإطفاء والغاز:** 4 live telemetry cards (Total equipment, operational, due for check, readiness rate %), operational/due filter pills, equipment ledger with expiration badges, Add Equipment modal, Quick Periodic Inspection modal, and instant Print/PDF Safety Register shortcuts.
        3. **تمارين الإخلاء والمحاكاة:** Disaster simulations grid with scenario badges, stopwatch duration, evacuated counters, assembly zones, bottlenecks alert, Record Evacuation Drill modal, and instant Print/PDF Drill PV shortcuts.
        4. **مخطط PSI وفرق التدخل:** Internal Security Plan leadership overview, safe assembly points list, official emergency phone numbers directory, intervention teams squad ledger (First Aid, Evacuation Guides, Fire Containment, Perimeter Security), and Add Member modal.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Complete tri-lingual dictionary populated in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest` (2 passed, 7 assertions).
      - 18 comprehensive feature tests in `tests/Feature/SchoolSafetyTest.php` passing 100% green (76 assertions).
      - Strict role navigation tailoring verified in `RoleNavigationTailoringTest.php` (6 tests, 122 assertions).

## 54. School Board of Education and Management / Guidance and Management, School Strategic Multi-Year Project, Internal Regulations & Parents-Teachers Association (منظومة مجلس التربية والتسيير / التوجيه والتسيير CEG / COG، مشروع المؤسسة، النظام الداخلي النموذجي، وجمعية أولياء التلاميذ APE) (100%)

Full statutory alignment with Algerian educational governance and institutional legislation, strictly adhering to **Décret exécutif n° 90-12** (fixant les dispositions applicables aux Collèges d'Enseignement Moyen - CEM et instituant le Conseil d'Éducation et de Gestion - CEG), **Décret exécutif n° 90-13** (fixant les dispositions applicables aux Lycées et instituant le Conseil d'Orientation et de Gestion - COG), **Arrêté ministériel n° 65/771** (portant règlement intérieur type des établissements d'enseignement moyen et secondaire), **Loi n° 12-06** (relative aux associations, régissant l'Association des Parents d'Élèves - APE), and Ministerial Circulars on the multi-year School Project contract (**Projet d'Établissement**):
- [x] **Database Schema & Core Domain Tables (expanded to 91 core tables):**
      - Migration `2026_11_26_010001_create_school_governance_and_board_tables.php` creating 6 domain tables:
        - `school_board_sessions`: statutory CEG/COG board sessions (`academic_year`, `session_reference`, `board_type` [CEG/COG], `session_term` [Term1Opening, Term2Budget, Term3Bilan, Extraordinary], `session_date`, `session_time`, `president_user_id`, `president_name`, `rapporteur_user_id`, `rapporteur_name`, `statutory_quorum`, `attendees_count`, `absentees_count`, `is_quorum_reached`, `agenda_items`, `deliberations_narrative`, `decisions_and_recommendations`, `voting_results`, `status`, `directorate_submission_date`, `notes`).
        - `school_board_members`: statutory & elected members roster (`school_board_session_id`, `member_category` [Statutory, ElectedTeacher, ElectedStaff, ElectedParent], `full_name`, `institution_role`, `user_id`, `election_date`, `mandate_start_year`, `mandate_end_year`, `attendance_status`, `notes`).
        - `school_projects`: multi-year strategic project contract (`project_code`, `title`, `start_academic_year`, `end_academic_year`, `strategic_axes`, `quantitative_targets`, `action_plan_matrix`, `kpi_indicators`, `completion_percentage`, `status`, `approval_date`, `approved_by`, `evaluation_summary`, `notes`).
        - `school_internal_regulations`: model school internal rules (`regulation_code`, `title`, `legal_basis_reference`, `chapters_and_articles`, `adoption_date`, `adopted_by_board_session_id`, `directorate_approval_date`, `is_currently_active`, `signed_undertakings_count`, `notes`).
        - `parents_associations`: parents-teachers association (APE) identity (`association_name`, `receipt_registration_number`, `accreditation_date`, `wilaya_accreditation_ref`, `president_name`, `president_phone`, `secretary_name`, `treasurer_name`, `executive_bureau_members`, `active_members_count`, `annual_subscription_fee`, `status`, `notes`).
        - `parents_association_meetings`: APE general assemblies & school joint coordination sessions (`parents_association_id`, `meeting_reference`, `meeting_type` [OrdinaryGeneralAssembly, ExtraordinaryGeneralAssembly, JointSchoolMeeting], `meeting_date`, `meeting_time`, `location`, `chairperson_name`, `attendees_count`, `agenda_items`, `assembly_resolutions`, `renewed_bureau_summary`, `status`, `notes`).
      - Updated `SajelHealthCheckCommand`, `SchoolBackupService`, and `SchoolRestoreService` expanding verified schema to 91 domain tables: `[PASS] schema: All required tables present (91)`.
- [x] **Domain Enums Architecture (`app/Enums`):**
      - `SchoolBoardType`: `Ceg` (مجلس التربية والتسيير - CEG), `Cog` (مجلس التوجيه والتسيير - COG).
      - `SchoolBoardSessionTerm`: `Term1Opening` (الدورة العادية الأولى: افتتاح السنة والمصادقة على التنظيم التربوي), `Term2Budget` (الدورة العادية الثانية: دراسة ومناقشة مشروع الميزانية السنوية), `Term3Bilan` (الدورة العادية الثالثة: الحصيلة الإدارية والمالية ونهاية السنة), `Extraordinary` (دورة استثنائية طارئة).
      - `SchoolBoardSessionStatus`: `Draft` (مسودة محضر قيد التحرير), `Deliberated` (تمت المداولة واعتماد القرارات), `SubmittedToDirectorate` (أُرسل المحضر لمديرية التربية), `Approved` (مصادق عليه رسمياً), `Archived` (مؤرشف).
      - `SchoolBoardMemberCategory`: `Statutory` (عضو بحكم القانون والوظيفة - Membre de droit), `ElectedTeacher` (أستاذ منتخب عن هيئة التدريس), `ElectedStaff` (موظف منتخب عن المساعدين والعمال), `ElectedParent` (مندوب منتخب عن أولياء التلاميذ).
      - `SchoolProjectStatus`: `Draft` (مشروع قيد الإعداد), `Submitted` (مرفوع للمصادقة), `ValidatedByDirectorate` (مصادق عليه من مديرية التربية), `InProgress` (قيد التنفيذ والمتابعة), `Evaluated` (تم التقييم المرحلي / النهائي), `Archived` (مؤرشف).
      - `ParentsMeetingType`: `OrdinaryGeneralAssembly` (جمعية عامة عادية وانتخابية), `ExtraordinaryGeneralAssembly` (جمعية عامة استثنائية), `JointSchoolMeeting` (جلسة عمل وتنسيق مشتركة مع إدارة المؤسسة).
- [x] **Domain Service Layer (`SchoolBoardGovernanceService`):**
      - Sequential reference generators: `CEG/{year}/{seq}`, `COG/{year}/{seq}`, `PRJ-{year}-{seq}`, `REG-{year}-{seq}`, and `APE-{year}-{seq}`.
      - Core operations: `recordBoardSession` with statutory quorum verification (`attendees_count >= statutory_quorum`), `autoBootstrapBoardMembers` (automatically populating statutory members: Principal, Censor, Bursar, Counselor, UDS Doctor, and elected slots for teachers, staff, and parents), `addBoardMember` with mandate tracking, `createOrUpdateSchoolProject` with strategic axes & completion %, `adoptInternalRegulations` (with automatic deactivation of older versions), `registerParentsAssociation`, and `recordParentsMeeting`.
      - Real-time telemetry & KPIs engine (`getGovernanceTelemetry`): total sessions, current year sessions, quorum-compliant sessions, board members count, elected teachers/staff count, active project status, project completion %, active regulations reference, signed parental undertakings count, registered APE info, and general assemblies held.
      - Printable data assemblers: `assembleBoardSessionPvData`, `assembleSchoolProjectSummaryData`, and `assembleParentsGeneralAssemblyPvData`.
- [x] **Controller Actions & Secure Routing (`SchoolBoardGovernanceController`, `routes/web.php`):**
      - 6 dedicated routes with Browsershot PDF streaming and HTML fallback:
        - `print.governance.board-session-pv` & `.pdf` (`/{locale}/print/governance/session/{session}/pv`)
        - `print.governance.school-project-summary` & `.pdf` (`/{locale}/print/governance/project/{project}/summary`)
        - `print.governance.parents-assembly-pv` & `.pdf` (`/{locale}/print/governance/parents-meeting/{meeting}/pv`)
      - Protected by strict permission check `User::canManageSchoolGovernance()`.
- [x] **Official Algerian Printable Templates:**
      - `resources/views/print/governance-board-session-pv.blade.php`: Procès-verbal des délibérations du Conseil d'Éducation et de Gestion / Orientation et Gestion (محضر مداولات دورة مجلس التربية والتسيير / التوجيه والتسيير - A4 Portrait) featuring official ministerial header, session type and term, statutory quorum verification statement, comprehensive members attendance and voting table, detailed deliberations narrative by agenda item, adopted resolutions, and signatures (Board President, Rapporteur).
      - `resources/views/print/governance-school-project-summary.blade.php`: Fiche de synthèse et contrat d'objectifs du Projet d'Établissement (بطاقة تركيبية وبطاقة أهداف مشروع المؤسسة - A4 Landscape) detailing multi-year horizon, strategic priorities, quantitative target indicators (BEM/BAC success rate targets, drop-out reduction, pedagogical support), action plan matrices, resource mobilization, and validating committee visas (Principal, Board Representative, Inspector / Director of Education).
      - `resources/views/print/governance-parents-assembly-pv.blade.php`: Procès-verbal de l'Assemblée Générale de l'Association des Parents d'Élèves (محضر الجمعية العامة لجمعية أولياء التلاميذ - A4 Portrait) featuring legal accreditation reference (Loi 12-06), bureau members, attendees quorum, resolutions adopted, moral and financial reports approval, newly elected executive bureau members, and dual validation (Association President, General Secretary).
- [x] **Interactive Filament Livewire Cockpit (`SchoolBoardGovernanceHub`):**
      - Registered under `__('الحياة المدرسية وشؤون التلاميذ')` with icon `heroicon-o-building-library` (sort order 6).
      - 4 interactive tabs:
        1. **دورات ومحاضر المجلس:** Session switcher, 4 live telemetry KPI cards (Total Sessions, Quorum Compliance, Directorate Submission, Pending Minutes), sessions table with quorum badges, status workflow actions (Submit to Directorate, Mark Approved), Record Board Session modal, and instant Print/PDF Official PV shortcuts.
        2. **أعضاء المجلس والهيئة:** Board membership ledger grouped by category (Statutory, Elected Teacher, Elected Staff, Elected Parent), mandate validity period, Statutory Bootstrap action (initializes mandatory members in one click), and Add Board Member modal.
        3. **مشروع المؤسسة والنجاعة:** Strategic multi-year contract overview, completion progress bar (0-100%), strategic axes cards, quantitative targets breakdown, Create/Edit Project modal, and instant Print/PDF Project Summary sheet.
        4. **جمعية الأولياء والنظام الداخلي:** APE identity card with executive bureau contact details, official accreditation receipt number, general assemblies ledger, Record APE Meeting modal, Internal Regulations adoption banner with signed parental undertakings counter, and Adopt Regulations modal.
- [x] **Tri-Lingual Localization & Automated Verification:**
      - Complete tri-lingual dictionary populated in `lang/ar.json`, `lang/en.json`, and `lang/fr.json` verified by `LocalizationCompletenessTest` (2 passed, 7 assertions).
      - 18 comprehensive feature tests in `tests/Feature/SchoolBoardGovernanceTest.php` passing 100% green (71 assertions).
      - Strict role navigation tailoring verified in `RoleNavigationTailoringTest.php` (6 tests, 127 assertions).
      - Schema integrity verified by `sajel:health-check`: `[PASS] schema: All required tables present (91)`.
      - Code style verified with `vendor/bin/pint`.












