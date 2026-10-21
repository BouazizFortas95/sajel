# Sajel (سجِّل) — Project Status

> Last updated: 2026-10-21
> Verified by: running `php artisan test` (**70 passed, 253 assertions**) and
> `php artisan migrate:status` (**15 migrations, all ran**).

## 1. Overview

**Sajel** (سجِّل — "record/register") is a teacher-oriented web platform for building and managing
weekly schedules and daily lesson logs (the digital equivalent of the class register / دفتر النصوص).
Teachers prepare lessons per schedule slot, then confirm them so they become the official register entry.

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
| Testing    | PHPUnit `^11.5`                          | **70 tests / 253 assertions, all green** |
| Tooling    | Laravel Pint, Pail, Sail, Tinker         | Dev dependencies installed              |

## 3. Domain Model

Hierarchy: `EducationPhase → Level → Classroom → Group`, with `Subject` and `Schedule` referencing them,
and `DailyLog` attached to each `Schedule`.

```
EducationPhase (name, code, settings[])
  └─ Level (name)
       └─ Classroom (name, academic_year)
            └─ Group (name)
                 └─ Student (first_name, last_name, registration_number?,
                            date_of_birth?)

Subject (name, color)

User (teacher)
  └─ Schedule (user, classroom?, group?, subject, day_of_week, start/end time)
       └─ DailyLog (date, title, content_data[], actual_content, homework,
                     observations, status)  — unique per (schedule, date)
```

### Models (`app/Models`)

| Model            | Purpose                                                            |
|------------------|--------------------------------------------------------------------|
| `User`           | Teacher/admin; implements `FilamentUser`; has schedules & daily logs |
| `EducationPhase` | Educational phase (e.g. Middle School) with JSON `settings`        |
| `Level`          | Grade/year within a phase                                          |
| `Classroom`      | Class section within a level (+ academic year)                     |
| `Group`          | Sub-division of a classroom; has many students                    |
| `Student`        | Enrolled student (unique nullable registration number) in a group |
| `Subject`        | Subject with a display color                                       |
| `Schedule`       | A teacher's weekly recurring lesson slot; `label()` for display    |
| `DailyLog`       | Per-slot daily log; dynamic `content_data` blocks; register summary generation |

### Enums (`app/Enums`)

| Enum               | Cases                                                                    |
|--------------------|--------------------------------------------------------------------------|
| `DayOfWeek`        | Sunday…Saturday (Arabic labels, per-day colors)                          |
| `DailyLogStatus`   | `Planned` (مخطط), `Completed` (منجز), `Cancelled` (ملغى)                |
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
- Native Filament CSV import (`StudentImporter`) links each row to a group by ID, exact display
  label, or a unique group name, and keeps blank registration numbers as `null`.
- Query scopes: `Schedule::forTeacher/onDay`, `DailyLog::forDate/forTeacher`, `EducationPhase::search`.
- `DayOfWeek::dateInCurrentWeek()` maps a weekday to its date in the current Sunday-first week,
  so weekly cards always act on the correct day's log.

## 4. Database

All 15 migrations have run (SQLite):

- Framework tables: `users` (with sessions), `cache`, `jobs`.
- Domain tables: `education_phases`, `levels`, `classrooms`, `groups`, `students`, `subjects`,
  `schedules`, `daily_logs`.
- Filament action tables: `imports`, `exports`, `failed_import_rows` (published from
  `filament-actions` for native CSV import).
- `2026_10_20_000001_add_role_to_users_table` adds the `role` column (defaults to `teacher`).
- Notable constraints: `daily_logs` has a unique `(schedule_id, date)` pair and a `date` index;
  `schedules` is indexed on `(user_id, day_of_week)`; `students.registration_number` is uniquely
  indexed but nullable; `students.group_id` cascades on group deletion.

Factories exist for all 8 domain models plus `User`.
`Database\Seeders\SajelSeeder` seeds a full demo: a teacher (`teacher@sajel.test` / `password`),
an admin (`admin@sajel.test` / `password`), a middle-school phase, 2 levels, 2 classrooms, 2 groups,
3 subjects, a weekly schedule, and a planned daily log. `DatabaseSeeder` runs it automatically after
creating its test user.

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
- Access control: users carry a `role` (`admin` | `teacher`, see `App\Enums\Role`); `User::canAccessPanel()`
  grants panel entry to any user with a role. Fine-grained abilities are enforced by model policies:
  `SchedulePolicy`, `DailyLogPolicy` (ownership scoping, admin bypass via `before()`), and `UserPolicy`
  (admin-only user management). Teachers see only their own schedules and daily logs (query scoping in
  `ScheduleResource` / `DailyLogResource`); admins see everything.

### Resources (`app/Filament/Resources`) — each with List / Create / Edit pages

| Resource                | Manages                                |
|-------------------------|----------------------------------------|
| `DailyLogResource`      | Daily logs / lesson preparations (the richest resource: `Builder` content data, `Tabs` form, "تأكيد الإنجاز" action, completion-gated `actual_content`/`homework`, generated register content) |
| `ScheduleResource`      | Weekly schedules (cascading classroom → group selects)                       |
| `SubjectResource`       | Subjects and colors                    |
| `ClassroomResource`     | Classrooms / academic years            |
| `LevelResource`         | Levels                                 |
| `GroupResource`         | Groups                                 |
| `StudentResource`       | Students, group assignment, group filter, CSV import |
| `EducationPhaseResource`| Education phases and settings          |
| `UserResource`          | Users & roles (admin-only via `UserPolicy`) |

`StudentResource` includes a native Filament CSV import action. Expected headers are
`first_name`, `last_name`, `registration_number`, `date_of_birth`, and `group`. The `group` value
may be a group ID, its exact `display_name()` label, or a group name when that name is unique.
Blank registration numbers and birth dates are stored as `null`. Imports run on the queue
(`QUEUE_CONNECTION=database`), so a queue worker must be running; `composer dev` starts one.

### Pages & Widgets

- Custom `Dashboard` page (`app/Filament/Pages/Dashboard.php`) using a custom Blade view
  (`resources/views/filament/pages/dashboard.blade.php`) with `@livewire('dashboard-stats')`.
  It renders a single full-width column; `getWidgets()` returns only `QuickLinks::class`
  (the `AccountWidget` is deliberately not shown on the dashboard).
- `DashboardStats` Livewire component (`app/Livewire/DashboardStats.php`) + Blade view
  (`resources/views/livewire/dashboard-stats.blade.php`): dark-mode-safe stat cards and
  "Smart log" tabs (Preparation / Completion).
- `MySchedule` page (`app/Filament/Pages/MySchedule.php`) at `/{locale}/auth/my-schedule`
  (teachers only), with two views toggled by `?view=day|week`:
  - **Day view**: `TeacherInteractiveSchedule` — stat cards, day tabs, search, grid/list mode,
    expandable cards, complete/cancel actions, and "فتح السجل"/"إنشاء سجل".
  - **Week view**: `TeacherTimetable` — a fixed Sunday–Thursday grid with seven one-hour slots
    (08:00–12:00 and 14:00–17:00), a lunch-break row, and explicit dashed placeholders for empty
    slots. Each populated card passes its column's current-week date to `openLog()`, which opens
    the existing log (edit) or a prefilled create page (`schedule_id` + `date` query parameters).
  Both views resolve weekday dates in the `Africa/Algiers` timezone and open the existing log
  or a prefilled create page.
- `QuickLinks` widget (`app/Filament/Widgets/QuickLinks.php`): dashboard navigation cards,
  including the new weekly schedule page (custom Blade view), using Sajel brand colours and
  responsive grid classes.

## 6. Routes & Views

- `routes/web.php`:
  - `/` closure returning the `welcome` view (landing page with a language dropdown, login/register
    links for guests, dashboard link for authenticated users).
  - `/{locale}` (where `{locale}` ∈ `ar`, `en`, `fr`) renders the same welcome view behind
    `SetFilamentLocale`, so localized home pages share the switcher state.
- The Filament panel is available at `/ar/auth`, `/en/auth`, and `/fr/auth`.
- Teacher schedule page: `/{locale}/auth/my-schedule` (day/week views; teachers only).
- Language files: `lang/ar.json`, `lang/en.json`, `lang/fr.json`.

## 7. Testing

`php artisan test` — **70 passing, 253 assertions**:

| Suite                                  | Tests | Covers                                                        |
|----------------------------------------|-------|---------------------------------------------------------------|
| `Tests\Unit\ExampleTest`               | 1     | Sanity check                                                  |
| `Tests\Feature\ExampleTest`            | 1     | `/` returns a successful response                             |
| `Tests\Feature\AuthorizationTest`      | 8     | Role-based panel access; policy-driven scoping; admin bypass  |
| `Tests\Feature\DailyLogPagesTest`      | 5     | List/edit pages, schedule+date prefilling, cross-teacher protection |
| `Tests\Feature\DashboardThemeTest`     | 7     | No missing utility classes, dark mode + theme asset, RTL Arabic panel + topbar switcher, brand colours, widget layout, stat tabs |
| `Tests\Feature\MySchedulePageTest`     | 4     | Teacher-only access, day/week views, guest redirect           |
| `Tests\Feature\TeacherInteractiveScheduleTest` | 22 | Rendering, colours, dark mode, day/search/view/expand interactions, complete/cancel, stats, date-aware log navigation, teacher scoping |
| `Tests\Feature\TeacherTimetableTest`   | 8     | Fixed five-day/seven-slot grid, matrix mapping, empty placeholders, teacher scoping, current-week click dates, stale-date fallback, date-aware log navigation |
| `Tests\Feature\StudentManagementTest`  | 7     | Student/group relationships, cascade delete, resource access, form creation, unique nullable registration, group filter, CSV importer group linking |
| `Tests\Feature\WelcomeAndDashboardTest`| 7     | Welcome page links, login/register access, guest redirect, dashboard stats/quick links, French and Arabic/RTL localization |

## 8. Project Health Snapshot

| Aspect            | Status                                             |
|-------------------|----------------------------------------------------|
| Migrations        | ✅ All 15 ran                                      |
| Test suite        | ✅ 70/70 passing (253 assertions)                  |
| Admin panel       | ✅ Functional with 9 resources + Livewire dashboard + ar/en/fr locales |
| Seed data         | ✅ Demo seeder available (teacher + admin)         |
| Version control   | ✅ Git repo initialized on `main` branch           |
| README            | ⚠️ Still the default Laravel skeleton README       |
| `APP_NAME`        | ✅ Set to `Sajel` in `.env`                        |
| `APP_URL`         | ✅ Set to `https://sajel.dev/` in `.env`           |

## 9. Known Gaps / Next Steps

1. **Gradebook / assessments**: not implemented.
2. **Reports & export**: no PDF/Excel export of the register or schedules.
3. **Notifications/reminders**: no reminders for unlogged sessions.
4. **README**: replace the skeleton README with a project-specific one (or point it to this doc).
5. **Default locale**: `.env` uses `APP_LOCALE=en`; the landing page falls back to English unless a
   `/{locale}` path is used.

## 10. Dashboard Livewire Remake (dark-mode safe)

### Checklist

- [x] 1. Create `app/Livewire/DashboardStats.php` — Livewire component using Filament theme colors (primary, secondary, success, warning, info, danger) with dark/light-aware Tailwind classes
- [x] 2. Create `resources/views/livewire/dashboard-stats.blade.php` — stat cards with `text-gray-900 dark:text-white` and `bg-white dark:bg-gray-800` etc.
- [x] 3. Update `app/Filament/Pages/Dashboard.php` — import the Livewire component instead of `SajelStats` widget
- [x] 4. Remove/replace `SajelStats` widget (dark-mode text visibility issue)
- [x] 5. Verify dark mode: stat card titles, values, descriptions, and chart colors all readable
- [x] 6. Run `php artisan test` — all 70 tests pass
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
