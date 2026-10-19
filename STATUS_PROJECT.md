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

- Panel ID/path: `auth` (registered in `bootstrap/providers.php` via `App\Providers\Filament\AuthPanelProvider`).
- Brand: **سجِّل**, primary color Amber; login, registration, and password reset enabled.
- Access control is open for the MVP: `User::canAccessPanel()` returns `true` for every user.

### Resources (`app/Filament/Resources`) — each with List / Create / Edit pages

| Resource                | Manages                                |
|-------------------------|----------------------------------------|
| `DailyLogResource`      | Daily logs / lesson preparations (327 lines — the richest resource) |
| `ScheduleResource`      | Weekly schedules                       |
| `SubjectResource`       | Subjects and colors                    |
| `ClassroomResource`     | Classrooms / academic years            |
| `LevelResource`         | Levels                                 |
| `GroupResource`         | Groups                                 |
| `EducationPhaseResource`| Education phases and settings          |

### Pages & Widgets

- Custom `Dashboard` page (`app/Filament/Pages/Dashboard.php`).
- `SajelStats` widget: today's logs, confirmed today, weekly sessions, subjects (server-rendered, non-lazy).
- `QuickLinks` widget: dashboard navigation cards to all resources (custom Blade view).

## 6. Routes & Views

- `routes/web.php`: default `/` closure returning the `welcome` view (landing page with
  login/register links for guests, dashboard link for authenticated users).
- Filament serves everything under `/auth`; the panel is the default panel.

## 7. Testing

`php artisan test` — **9 passing, 21 assertions**:

- `Tests\Unit\ExampleTest` — sanity check.
- `Tests\Feature\ExampleTest` — `/` returns a successful response.
- `Tests\Feature\WelcomeAndDashboardTest` — guest sees welcome page with login/register links;
  authenticated user sees dashboard link; login/register pages accessible; guest redirected from
  dashboard to login; dashboard shows stats and quick links.
- `Tests\Feature\DailyLogPagesTest` — the daily-log list page (table with row actions) and the
  edit page (header actions + form) render for the owning teacher. Added as a regression test for
  the Filament v2→v3 action API fixes (`Action::table`, `disabledOnEdit`, `SaveAction`).

## 8. Project Health Snapshot

| Aspect            | Status                                             |
|-------------------|----------------------------------------------------|
| Migrations        | ✅ All 10 ran                                      |
| Test suite        | ✅ 9/9 passing                                     |
| Admin panel       | ✅ Functional with 7 resources + dashboard         |
| Seed data         | ✅ Demo seeder available                           |
| Version control   | ✅ Git repo initialized on `main` branch          |
| README            | ⚠️ Still the default Laravel skeleton README       |
| `APP_NAME`        | ⚠️ Still "Laravel" in `.env` (panel brand is سجِّل) |

## 9. Known Gaps / Next Steps

1. **Authorization**: every registered user can access the panel (`canAccessPanel()` → `true`).
   Add roles/permissions (e.g. Filament Shield) or per-user scoping when needed.
2. **Multi-tenancy / data isolation**: resources currently show all records; teachers should
   eventually see only their own schedules and logs.
3. **Student management**: no `Student` model yet — groups have no rosters.
4. **Gradebook / assessments**: not implemented.
5. **Reports & export**: no PDF/Excel export of the register or schedules.
6. **Notifications/reminders**: no reminders for unlogged sessions.
7. **README**: replace the skeleton README with a project-specific one (or point it to this doc).
8. **`.env` cleanup**: set `APP_NAME=Sajel` (or سجِّل) and a real `APP_URL` for deployment.

## 10. Getting Started

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
