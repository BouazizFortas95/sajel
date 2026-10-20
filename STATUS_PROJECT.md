# Sajel (سجِّل) — Project Status

> Last updated: 2026-10-19
> Verified by: running `php artisan test` (7 passed, 17 assertions) and `php artisan migrate:status` (all migrations ran).

## 1. Overview

**Sajel** (سجِّل — "record/register") is a teacher-oriented web platform for building and managing
weekly schedules and daily lesson logs (the digital equivalent of the class register / دفتر النصوص).
Teachers prepare lessons per schedule slot, then confirm them so they become the official register entry.

The UI and domain language are Arabic (RTL), with English code identifiers.

## 2. Tech Stack

| Layer      | Choice                                   | Notes                                   |
|------------|------------------------------------------|-----------------------------------------|
| Framework  | Laravel `^12.0`                          | PHP `^8.2`                              |
| Admin UI   | Filament `^3.2`                          | Single panel, Amber brand color         |
| Database   | SQLite                                   | `database/database.sqlite` present      |
| Frontend   | Vite `^7`, Tailwind CSS `^4`, Axios      | No SPA framework; Filament Blade views  |
| Testing    | PHPUnit `^11.5`                          | 9 tests / 21 assertions, all green      |
| Tooling    | Laravel Pint, Pail, Sail, Tinker         | Dev dependencies installed              |

## 3. Domain Model

Hierarchy: `EducationPhase → Level → Classroom → Group`, with `Subject` and `Schedule` referencing them,
and `DailyLog` attached to each `Schedule`.

```
EducationPhase (name, code, settings[])
  └─ Level (name)
       └─ Classroom (name, academic_year)
            └─ Group (name)

Subject (name, color)

User (teacher)
  └─ Schedule (user, classroom?, group?, subject, day_of_week, start/end time)
       └─ DailyLog (date, title, content_data[], actual_content, homework,
                     observations, status)  — unique per (schedule, date)
```

### Models (`app/Models`)

| Model            | Purpose                                                            |
|------------------|--------------------------------------------------------------------|
| `User`           | Teacher; implements `FilamentUser`; has schedules & daily logs     |
| `EducationPhase` | Educational phase (e.g. Middle School) with JSON `settings`        |
| `Level`          | Grade/year within a phase                                          |
| `Classroom`      | Class section within a level (+ academic year)                     |
| `Group`          | Sub-division of a classroom                                        |
| `Subject`        | Subject with a display color                                       |
| `Schedule`       | A teacher's weekly recurring lesson slot; `label()` for display    |
| `DailyLog`       | Per-slot daily log; dynamic `content_data` blocks; register summary generation |

### Enums (`app/Enums`)

| Enum               | Cases                                                                    |
|--------------------|--------------------------------------------------------------------------|
| `DayOfWeek`        | Sunday…Saturday (Arabic labels, per-day colors)                          |
| `DailyLogStatus`   | `Planned` (مخطط), `Completed` (منجز), `Cancelled` (ملغى)                |
| `LessonPhaseType`  | Warmup, Discovery, Institutionalization, Application, Evaluation (مراحل الدرس) |

### Key behaviors

- `DailyLog::content_data` is a JSON array of typed blocks:
  `middle_school_lesson` (lesson title, competence, pedagogical situation, phases),
  `general_lesson` (title, objectives, content), and `warmup` (description, duration).
- `DailyLog::generateRegisterSummary()` renders those blocks into a readable Arabic plain-text entry.
- `DailyLog::markAsCompleted()` flips status to `Completed` and promotes prepared content to
  `actual_content` (official register entry).
- Query scopes: `Schedule::forTeacher/onDay`, `DailyLog::forDate/forTeacher`, `EducationPhase::search`.


## 4. Database

All 10 migrations have run (SQLite):

- Framework tables: `users` (with sessions), `cache`, `jobs`.
- Domain tables: `education_phases`, `levels`, `classrooms`, `groups`, `subjects`, `schedules`, `daily_logs`.
- Notable constraints: `daily_logs` has a unique `(schedule_id, date)` pair and a `date` index;
  `schedules` is indexed on `(user_id, day_of_week)`; FKs cascade or nullify appropriately.

Factories exist for all 7 domain models plus `User`.
`Database\Seeders\SajelSeeder` seeds a full demo: a teacher (`teacher@sajel.test` / `password`),
a middle-school phase, 2 levels, 2 classrooms, 2 groups, 3 subjects, a weekly schedule, and a
planned daily log.

## 5. Admin Panel (Filament)

- Panel ID/path: `auth` at `/{locale}/auth` (Arabic, English, and French; registered in
  `bootstrap/providers.php` via `App\Providers\Filament\AuthPanelProvider`).
- The panel locale middleware sets Laravel and Filament's locale before rendering, so Filament's
  native layout direction flips to RTL for Arabic. The language links render inside Filament's
  existing top bar, and the sidebar is collapsible on desktop.
- Brand: **سجِّل**, primary color Amber; login, registration, and password reset enabled.
- Access control: users carry a `role` (`admin` | `teacher`, see `App\Enums\Role`); `User::canAccessPanel()` grants panel entry to both roles. Fine-grained abilities are enforced by model policies: `SchedulePolicy`, `DailyLogPolicy` (ownership scoping, admin bypass via `before()`), and `UserPolicy` (admin-only user management). Teachers see only their own schedules and daily logs (query scoping in `ScheduleResource` / `DailyLogResource`); admins see everything.

### Resources (`app/Filament/Resources`) — each with List / Create / Edit pages

| Resource                | Manages                                |
|-------------------------|----------------------------------------|
| `DailyLogResource`      | Daily logs / lesson preparations (the richest resource: `Builder` content data, `Tabs` form, "تأكيد الإنجاز" action) |
| `ScheduleResource`      | Weekly schedules (cascading classroom → group selects)                       |
| `SubjectResource`       | Subjects and colors                    |
| `ClassroomResource`     | Classrooms / academic years            |
| `LevelResource`         | Levels                                 |
| `GroupResource`         | Groups                                 |
| `EducationPhaseResource`| Education phases and settings          |
| `UserResource`          | Users & roles (admin-only via `UserPolicy`) |

### Pages & Widgets

- Custom `Dashboard` page (`app/Filament/Pages/Dashboard.php`) using a custom Blade view with `@livewire('dashboard-stats')`.
- `DashboardStats` Livewire component (`app/Livewire/DashboardStats.php`) + Blade view (`resources/views/livewire/dashboard-stats.blade.php`): dark-mode-safe stat cards with `text-gray-900 dark:text-white`, `bg-white dark:bg-gray-800`, `border-gray-200 dark:border-gray-700`, and Filament theme colors (amber/primary, emerald/success, blue/info, indigo).
- `QuickLinks` widget: dashboard navigation cards to all resources (custom Blade view).

## 6. Routes & Views

- `routes/web.php`: default `/` closure returning the `welcome` view (landing page with
  login/register links for guests, dashboard link for authenticated users).
- The Filament panel is available at `/ar/auth`, `/en/auth`, and `/fr/auth`. The landing page
  remains at `/`; its panel links use the configured default locale.

## 7. Testing

`php artisan test` — **17 passing, N assertions**:

- `Tests\Unit\ExampleTest` — sanity check.
- `Tests\Feature\ExampleTest` — `/` returns a successful response.
- `Tests\Feature\WelcomeAndDashboardTest` — guest sees welcome page with login/register links;
  authenticated user sees dashboard link; login/register pages accessible; guest redirected from
  dashboard to login; dashboard shows stats and quick links.
- `Tests\Feature\DailyLogPagesTest` — the daily-log list page (table with row actions) and the
  edit page (header actions + form) render for the owning teacher. Regression test for the
  Filament v2→v3 action API fixes (`Action::table`, `disabledOnEdit`, `SaveAction`).
- `Tests\Feature\AuthorizationTest` — role-based panel access (`admin` | `teacher`); policy-driven
  scoping: teachers see only their own schedules/logs and are denied the user resource;
  admins bypass via policy `before()`; user management is admin-only.

## 8. Project Health Snapshot

| Aspect            | Status                                             |
|-------------------|----------------------------------------------------|
| Migrations        | ✅ All 11 ran                                      |
| Test suite        | ✅ 17/17 passing (42 assertions)                   |
| Admin panel       | ✅ Functional with 8 resources + Livewire dashboard   |
| Seed data         | ✅ Demo seeder available (teacher + admin)         |
| Version control   | ✅ Git repo initialized on `main` branch          |
| README            | ⚠️ Still the default Laravel skeleton README       |
| `APP_NAME`        | ✅ Set to `Sajel` in `.env`                        |

## 9. Known Gaps / Next Steps

1. **Student management**: no `Student` model yet — groups have no rosters.
2. **Gradebook / assessments**: not implemented.
3. **Reports & export**: no PDF/Excel export of the register or schedules.
4. **Notifications/reminders**: no reminders for unlogged sessions.
5. **README**: replace the skeleton README with a project-specific one (or point it to this doc).
6. **`.env` cleanup**: set a real `APP_URL` for deployment (`APP_NAME=Sajel` is set).

## 10. Dashboard Livewire Remake (dark-mode safe)

### Checklist

- [x] 1. Create `app/Livewire/DashboardStats.php` — Livewire component using Filament theme colors (primary, secondary, success, warning, info, danger) with dark/light-aware Tailwind classes
- [x] 2. Create `resources/views/livewire/dashboard-stats.blade.php` — stat cards with `text-gray-900 dark:text-white` and `bg-white dark:bg-gray-800` etc.
- [x] 3. Update `app/Filament/Pages/Dashboard.php` — import the Livewire component instead of `SajelStats` widget
- [x] 4. Remove/replace `SajelStats` widget (dark-mode text visibility issue)
- [x] 5. Verify dark mode: stat card titles, values, descriptions, and chart colors all readable
- [x] 6. Run `php artisan test` — all 17 tests still pass
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

Then open the app URL and visit `/auth` (login/register). Demo teacher: `teacher@sajel.test` / `password`.
