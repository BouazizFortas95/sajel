# Sajel (سجِّل) — Project Status

> Last updated: 2026-10-22
> Verified by: running `php artisan test` (**123 passed, 687 assertions**) and
> `php artisan migrate:status` (**22 migrations, all ran**).

## 1. Overview

**Sajel** (سجِّل — "record/register") is a teacher-oriented web platform for building and managing
weekly schedules, daily lesson logs (the digital equivalent of the class register / دفتر النصوص),
and assessment gradebooks (دفتر العلامات). Teachers prepare lessons per schedule slot, confirm them so
they become the official register entry, then record assessment scores for the same roster.

The UI and domain language are Arabic (RTL), with English code identifiers. The landing page and
panel support Arabic, English, and French via `mcamara/laravel-localization`.

## 2. Tech Stack

| Layer      | Choice                                   | Notes                                   |
|------------|------------------------------------------|-----------------------------------------|
| Framework  | Laravel `^12.0`                          | PHP `^8.2`                              |
| Admin UI   | Filament `^3.2` (bundles Livewire)       | Single panel, Amber brand color, Cairo font |
| Database   | SQLite                                   | `database/database.sqlite` present      |
| Frontend   | Vite `^7`, Tailwind CSS `^4`, Axios      | Filament theme in `resources/css/filament.css` |
| i18n       | `mcamara/laravel-localization` `^2.4`    | `ar`, `en`, `fr` locales                |
| Timezone   | `Africa/Algiers`                         | `APP_TIMEZONE`, used for weekly dates   |
| Testing    | PHPUnit `^11.5`                          | **123 tests / 687 assertions, all green** |
| Tooling    | Laravel Pint, Pail, Sail, Tinker         | Dev dependencies installed              |

## 3. Domain Model

Hierarchy: `EducationPhase → Level → Classroom → Group`, with `Subject` and `Schedule` referencing them,
and `DailyLog` attached to each `Schedule`.

```
EducationPhase (name, code, phase_abbreviation, settings[])
  └─ Level (name, grade_number)
       └─ Classroom (name auto-generated, class_number, academic_year)
            └─ Group (name)
                 └─ Student (first_name, last_name, registration_number?,
                            date_of_birth?)

Subject (name, color)

User (teacher: primary/secondary subject, weekly hours limit)
  └─ Schedule (user, classroom?, group?, subject, day_of_week, start/end time)
       ├─ DailyLog (date, title, content_data[], actual_content, homework,
       │             observations, status)  — unique per (schedule, date)
       │    └─ Attendance (student, status, remarks?) — unique per (daily_log, student)
       └─ Assessment (title, type, date, max_score)
            └─ Grade (student, score?, remarks?) — unique per (assessment, student)
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
| `Schedule`       | A teacher's weekly recurring lesson slot; `label()` for display    |
| `DailyLog`       | Per-slot daily log; dynamic `content_data` blocks; register summary generation; has many attendances |
| `Attendance`     | Per-student status (`present`/`absent`/`late`/`excused`) and remarks for one daily log |
| `Assessment`     | Quiz/midterm/exam/continuous assessment attached to a schedule; materializes grade rows |
| `Grade`          | Per-student score and remarks for one assessment; unique per `(assessment, student)` |

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
- `Student::scopeForSchedule()` is the single roster resolver used by attendance and gradebook syncs
  and their relation managers; a schedule with neither a group nor a classroom targets no students
  rather than the whole school.
- Native Filament CSV import (`StudentImporter`) links each row to a group by ID, exact display
  label, or a unique group name, and keeps blank registration numbers as `null`.
- Native Filament CSV/XLSX export (`DailyLogExporter`) exports completed logs only, with date,
  classroom, subject, lesson title, actual content (HTML flattened to text), and homework.
- The official register can be printed from `/print/daily-logs?ids[]=...`; the route is authenticated,
  teacher-scoped, completed-only, and renders an A4 RTL Blade document that opens the browser print
  dialog automatically.
- Query scopes: `Schedule::forTeacher/onDay`, `DailyLog::forDate/forTeacher`, `EducationPhase::search`.
- Teacher specialization: each teacher has one primary subject and an optional secondary subject. The
  schedule form exposes only those subjects (defaulting to the primary), validates the submitted subject
  server-side, and lets admins choose only teacher accounts before selecting a subject.
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

All 22 migrations have run (SQLite):

- Framework tables: `users` (with sessions), `cache`, `jobs`.
- Domain tables: `education_phases`, `levels`, `classrooms`, `groups`, `students`, `subjects`,
  `schedules`, `daily_logs`, `attendances`, `assessments`, `grades`.
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
- Notable constraints: `daily_logs` has a unique `(schedule_id, date)` pair and a `date` index;
  `schedules` is indexed on `(user_id, day_of_week)`; `students.registration_number` is uniquely
  indexed but nullable; `students.group_id` cascades on group deletion; `attendances` has a unique
  `(daily_log_id, student_id)` pair and both foreign keys cascade on delete; `assessments.schedule_id`
  cascades on schedule deletion; `grades` has a unique `(assessment_id, student_id)` pair, and both
  of its foreign keys cascade on delete.

Factories exist for all 11 domain models plus `User`.
`Database\Seeders\SajelSeeder` seeds a full demo: a teacher (`teacher@sajel.test` / `password`),
an admin (`admin@sajel.test` / `password`), a middle-school phase, 2 levels, 2 classrooms, 2 groups,
4 subjects, a weekly schedule, and a planned daily log. The demo teacher is assigned Mathematics as
primary and Physics as secondary, with the standard 22-hour weekly limit. The seeded phase carries
the `م` abbreviation, levels carry grade numbers `1`/`2`, and classrooms carry class numbers so the
demo names resolve to `1 م 1` and `2 م 2`. `DatabaseSeeder` runs it automatically after creating its
test user.

## 5. Admin Panel (Filament)

- Panel ID/path: `auth` at `/{locale}/auth` (Arabic, English, and French; registered in
  `bootstrap/providers.php` via `App\Providers\Filament\AuthPanelProvider`).
- The panel locale middleware (`App\Http\Middleware\SetFilamentLocale`) validates the `{locale}`
  segment and sets Laravel's locale before rendering, so Filament's native layout direction flips
  to RTL for Arabic.
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
  - `StudentPolicy`: the master list and all mutations are admin-only. A teacher may view an individual
    student only when scheduled for that student's group, or for the whole classroom when the schedule
    has no group filter.
  - `GradePolicy` / `AttendancePolicy`: teachers can view or update rows only when they own the parent
    `Assessment` / `DailyLog`; manual create/delete is disabled (rows are materialized idempotently).
  - `UserPolicy`: admin-only user management.
  - `SubjectPolicy`, `ClassroomPolicy`, `LevelPolicy`, `GroupPolicy`, `EducationPhasePolicy`: the
    core academic structure catalogs are strictly admin-only; teachers are denied every ability.

  Teachers see only their own schedules, daily logs, and assessments (query scoping in
  `ScheduleResource`, `DailyLogResource`, and `AssessmentResource`); `StudentResource` is hidden from
  their navigation entirely. The core academic structure resources (`SubjectResource`,
  `ClassroomResource`, `LevelResource`, `GroupResource`, `EducationPhaseResource`) are also hidden
  from teacher navigation via `shouldRegisterNavigation()` and reject direct access with a 403.
  Relation managers additionally verify parent-record ownership, and inline
  grade/attendance columns are disabled unless the policy allows `update` on that row.

### Resources (`app/Filament/Resources`) — each with List / Create / Edit pages

| Resource                | Manages                                |
|-------------------------|----------------------------------------|
| `DailyLogResource`      | Daily logs / lesson preparations (the richest resource: `Builder` content data, `Tabs` form, "تأكيد الإنجاز" action, completion-gated `actual_content`/`homework`, generated register content, CSV/XLSX export, completed-only bulk register printing, and the auto-populated `AttendancesRelationManager`) |
| `AssessmentResource`    | Assessments and the lazy, auto-populated `GradesRelationManager` (inline decimal scores and remarks, dynamic max-score validation, teacher-scoped schedule choices) |
| `ScheduleResource`      | Weekly schedules (cascading classroom → group selects; teacher/admin subject choices restricted to the teacher's primary/secondary assignments) |
| `SubjectResource`       | Subjects and colors                    |
| `ClassroomResource`     | Classrooms / academic years (auto-generated `{grade} {abbr} {number}` name; class number form field) |
| `LevelResource`         | Levels (name + numeric grade number)   |
| `GroupResource`         | Groups                                 |
| `StudentResource`       | Students, group assignment, group filter, CSV import |
| `EducationPhaseResource`| Education phases, abbreviations and settings |
| `UserResource`          | Users, roles, teacher primary/secondary subjects, and weekly workload limit (admin-only via `UserPolicy`) |

The six catalog resources are admin-only (hidden from teacher navigation and fully denied by policy):
`SubjectResource`, `ClassroomResource`, `LevelResource`, `GroupResource`, `EducationPhaseResource`,
and `UserResource`.

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
    slots. The header includes a workload badge ("النصاب الزمني: total / limit ساعة") that turns
    red with a warning icon when the scheduled total exceeds the teacher's weekly limit. Each
    populated card passes its column's current-week date to `openLog()`, which opens the existing
    log (edit) or a prefilled create page (`schedule_id` + `date` query parameters).
  Both views resolve weekday dates in the `Africa/Algiers` timezone and open the existing log
  or a prefilled create page.
- `QuickLinks` widget (`app/Filament/Widgets/QuickLinks.php`): dashboard navigation cards,
  including the new weekly schedule page (custom Blade view), using Sajel brand colours and
  responsive grid classes.
- `UnloggedSessionsWidget` (`app/Filament/Widgets/UnloggedSessionsWidget.php`): a teacher-only
  dashboard reminder for missed Daily Logs. It scans the teacher's schedules from Sunday through
  today in `Africa/Algiers`, treats a session as overdue once its end time has passed, and lists
  sessions whose log is missing or still `planned`; `completed` and `cancelled` logs are resolved.
  Each item shows the day/date, subject, classroom/group, time, and a missing/planned status badge.
  The "إكمال السجل" button opens the prefilled create page with `schedule_id` and `date`, or the
  existing log's edit page. A subtle "all caught up" success state is rendered when nothing is
  pending. The widget is hidden for admins and guests via `canView()`.

## 6. Routes & Views

- `routes/web.php`:
  - `/` closure returning the `welcome` view (landing page with a language dropdown, login/register
    links for guests, dashboard link for authenticated users).
  - `/{locale}` (where `{locale}` ∈ `ar`, `en`, `fr`) renders the same welcome view behind
    `SetFilamentLocale`, so localized home pages share the switcher state.
  - `/print/daily-logs` (`print.register`, `auth`) accepts `ids[]` query parameters and renders the
    official A4 register. Completed logs only; teachers are scoped to their own schedules, admins may
    print any teacher's completed logs. Guests are redirected to the Filament login page.
- The Filament panel is available at `/ar/auth`, `/en/auth`, and `/fr/auth`.
- Teacher schedule page: `/{locale}/auth/my-schedule` (day/week views; teachers only).
- Language files: `lang/ar.json`, `lang/en.json`, `lang/fr.json`.

## 7. Testing

`php artisan test` — **123 passing, 687 assertions**:

| Suite                                  | Tests | Covers                                                        |
|----------------------------------------|-------|---------------------------------------------------------------|
| `Tests\Unit\ExampleTest`               | 1     | Sanity check                                                  |
| `Tests\Feature\ExampleTest`            | 1     | `/` returns a successful response                             |
| `Tests\Feature\AuthorizationTest`      | 18    | Role-based panel access; student/assessment/grade/attendance policies; admin-only core academic structure policies, screens, and navigation hiding; relation-manager parent ownership; admin bypass |
| `Tests\Feature\ClassroomNamingTest`    | 3     | Auto-generated classroom name from level grade, phase abbreviation, and class number; regeneration on update; graceful fallback when the convention is incomplete |
| `Tests\Feature\DailyLogPagesTest`      | 5     | List/edit pages, schedule+date prefilling, cross-teacher protection |
| `Tests\Feature\AttendanceTest`         | 7     | Attendance defaults/relationships, unique pair, group and classroom roster resolution, idempotent sync, new-student insertion, inline status update, relation-manager registration |
| `Tests\Feature\GradebookTest`          | 12    | Assessment/grade relationships and casts, unique pair, group/classroom roster sync, new-student insertion, inline decimal scores and max validation, teacher-only schedule creation/scoping, schedule immutability on edit, cascade deletes, relation-manager registration |
| `Tests\Feature\DashboardThemeTest`     | 7     | No missing utility classes, dark mode + theme asset, RTL Arabic panel + topbar switcher, brand colours, widget layout/registration, stat tabs |
| `Tests\Feature\MySchedulePageTest`     | 4     | Teacher-only access, day/week views, guest redirect           |
| `Tests\Feature\TeacherInteractiveScheduleTest` | 22 | Rendering, colours, dark mode, day/search/view/expand interactions, complete/cancel, stats, date-aware log navigation, teacher scoping |
| `Tests\Feature\TeacherTimetableTest`   | 12    | Fixed five-day/seven-slot grid, matrix mapping, workload totals and overload badge, equality boundary, empty placeholders, subject-colour mapping (all six palette colours), primary fallback for missing/unknown colours, teacher scoping, current-week click dates, stale-date fallback, date-aware log navigation |
| `Tests\Feature\TeacherSubjectAssignmentTest` | 6 | Primary/secondary relationships, default weekly limit, teacher form default/restriction, admin teacher-scoped subject options, user-form specialization assignment/editing |
| `Tests\Feature\StudentManagementTest`  | 8     | Student/group relationships, cascade delete, admin-only resource/navigation, form creation, unique nullable registration, group filter, CSV importer group linking |
| `Tests\Feature\RegisterPrintTest`      | 7     | Authenticated print route, teacher/admin scoping, completed-only filtering, validation, guest redirect, completed-only bulk action, exporter columns/filter |
| `Tests\Feature\WelcomeAndDashboardTest`| 7     | Welcome page links, login/register access, guest redirect, dashboard stats/quick links, French and Arabic/RTL localization |
| `Tests\Feature\UnloggedSessionsWidgetTest` | 3 | Teacher-only visibility, Sunday-to-today missing/planned detection, today's end-time boundary, teacher scoping, completion URLs, success state |

## 8. Project Health Snapshot

| Aspect            | Status                                             |
|-------------------|----------------------------------------------------|
| Migrations        | ✅ All 22 ran                                      |
| Test suite        | ✅ 123/123 passing (687 assertions)                |
| Admin panel       | ✅ Functional with 10 resources + Livewire dashboard + ar/en/fr locales |
| Seed data         | ✅ Demo seeder available (teacher + admin)         |
| Version control   | ✅ Git repo initialized on `main` branch           |
| README            | ⚠️ Still the default Laravel skeleton README       |
| `APP_NAME`        | ✅ Set to `Sajel` in `.env`                        |
| `APP_URL`         | ✅ Set to `https://sajel.dev/` in `.env`           |

## 9. Known Gaps / Next Steps

1. **Reports beyond the register**: schedules and aggregate reports are not exported yet; the register
   export supports CSV/XLSX, and PDF is produced through the browser's print dialog rather than a
   server-side PDF library.
2. **README**: replace the skeleton README with a project-specific one (or point it to this doc).
3. **Default locale**: `.env` uses `APP_LOCALE=en`; the landing page falls back to English unless a
   `/{locale}` path is used.

## 10. Dashboard Livewire Remake (dark-mode safe)

### Checklist

- [x] 1. Create `app/Livewire/DashboardStats.php` — Livewire component using Filament theme colors (primary, secondary, success, warning, info, danger) with dark/light-aware Tailwind classes
- [x] 2. Create `resources/views/livewire/dashboard-stats.blade.php` — stat cards with `text-gray-900 dark:text-white` and `bg-white dark:bg-gray-800` etc.
- [x] 3. Update `app/Filament/Pages/Dashboard.php` — import the Livewire component instead of `SajelStats` widget
- [x] 4. Remove/replace `SajelStats` widget (dark-mode text visibility issue)
- [x] 5. Verify dark mode: stat card titles, values, descriptions, and chart colors all readable
- [x] 6. Run `php artisan test` — all tests pass
- [x] 7. Run `vendor/bin/pint` — code formatted
- [x] 8. Update `STATUS_PROJECT.md` with completion notes

## 11. Getting Started

```bash
composer setup          # install deps, create .env, key, migrate, npm install + build
# or manually:
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed --class=SajelSeeder   # optional demo data
composer dev            # serve + queue + logs + vite concurrently
```

If you run only `php artisan serve` instead of `composer dev`, also start a queue worker so CSV
imports are processed (`php artisan queue:work`).

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
