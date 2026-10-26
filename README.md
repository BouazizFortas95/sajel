# Sajel (سجِّل) — Digital Class Register & Classroom Assistant

> Arabic-first (RTL), English code, French locale support. Built for Algerian Middle Schools using **Laravel 12**, **Filament PHP v3.2**, **Livewire 3**, **Tailwind CSS v4**, **SQLite**, and **Spatie Browsershot** for PDF export.

---

## 1. Project Overview

**Sajel** (سجِّل — "record / register") is a teacher-oriented digital platform that replaces paper class registers (`دفتر النصوص`) with structured daily lesson logs (`DailyLog`), interactive timetables (`TeacherTimetable`), a reusable pedagogical memo bank (`PedagogicalMemoResource`), gradebooks (`Gradebook` / `Assessment`), official curriculum tracking (`CurriculumProgress`), and a full classroom-assistant suite (Quick Attendance, Behavior Observations, Active Learning Strategies, Interactive Toolbar, and Fullscreen Data Show Mode).

Key goals:
- Prepare lessons per weekly schedule slot (`Schedule`).
- Confirm logs so they become the official register entry (`DailyLogStatus::Completed`).
- Record attendance (`Attendance`) and assessment scores (`Grade`) against the same roster.
- Print reusable A4 RTL memos and official reports (Weekly Schedule, Consolidated Attendance, Grade Transcripts), all carrying the configured school identity.
- Track annual curriculum progress (`CurriculumDomain → Sequence → Knowledge Resource`) automatically when lessons are completed.
- Manage lab rooms, U-shape seating plans, and quick attendance directly from the teacher's panel.

---

## 2. Phase 2 Feature Matrix (Complete — 14 / 14)

### Axis 1 — Planning, Curriculum & Reports (`5/5` ✅)

| Feature | Resource / Service | Notes |
|---|---|---|
| Daily Log & Register Register | `DailyLogResource` + `DailyLogRegisterService` | Builder `content_data`, Confirm Completion, A4 RTL PDF / CSV export |
| Weekly Interactive Timetable | `TeacherTimetable` + `ScheduleResource` | 5-day / 7-slot grid, multi-hour row spans, workload guard |
| Pedagogical Memo Bank | `PedagogicalMemoResource` | A4 print / server-side PDF, template/archive/duplicate |
| Official Curriculum Tracking | `CurriculumProgress` (Domains / Sequences / Resources) | Auto progress % when logs confirmed |
| Official Reports & Forms | `OfficialReports` + controller/service | Weekly schedule, attendance summary, grade transcripts |

### Axis 2 — Lab & Student Management (`5/5` ✅)

| Feature | Resource / Component | Notes |
|---|---|---|
| Student Roster & CSV Import | `StudentResource` | Group-scoped filter, unique nullable registration |
| Classrooms & Labs | `ClassroomResource` + `LabResource` | Auto-generated names, device status (working / broken / maintenance) |
| U-Shape Lab Seating Plan | `LabSeatingPlan` / `LabSeatingPlanStation` + `LabSeatingPlanPage` | Livewire interactive layout, save logic |
| Quick Attendance | `QuickAttendance` (Page + Livewire) | One-click attendance, offline roster snapshots, local change queue, validated batch sync |
| Behavior & Observations | `BehaviorObservationResource` | Linked to student (`student_id`), category, notes, follow-up status |

### Axis 3 — Classroom Activities & Presentation (`3/3` ✅)

| Feature | Component / Page | Notes |
|---|---|---|
| Active Learning Strategies | `ActiveLearningStrategyResource` + `DailyLogResource` phase `Select` | 8 seeded cards (Think-Pair-Share, Jigsaw, Exit Ticket, etc.) |
| Interactive Classroom Toolbar | `ClassroomToolbar` (Livewire + Page) | Timer, random student picker, auto group generator |
| Fullscreen Data Show Mode | `DataShowMode` (Livewire + Page + Blade) | Auto-select today's log; 3 themes; font zoom; HTML-free slide narration; locale-aware Web Speech controls and voice selection; keyboard nav; quick tools drawer |

### Axis 4 — Assessment & Pedagogical Remediation (`1/1` ✅)

| Feature | Resource / Model | Notes |
|---|---|---|
| Pedagogical Remediation | `PedagogicalRemediationResource` + `PedagogicalRemediation` | Student-linked session log with reason, activities, attendance, follow-up status |

---

## 3. Tech Stack

| Layer | Technology | Version / Note |
|---|---|---|
| Framework | Laravel | 12 |
| Admin UI | Filament | v3.2 (Livewire bundled) |
| Livewire | Livewire | 3 |
| Frontend | Vite / Tailwind CSS | v7 / v4 |
| Database | SQLite | `database/database.sqlite` |
| PDF / Print | `spatie/browsershot` / Puppeteer | A4 RTL PDF with Arabic shaping |
| Localization | `mcamara/laravel-localization` | `ar`, `en`, `fr` |
| Timezone | Africa/Algiers | `APP_TIMEZONE` |
| Font | Cairo (Google Fonts) | RTL body + headings |
| Testing | PHPUnit | 316 passed, 0 failures (14,671 assertions in the latest run) |

---

## 4. Domain Model (Quick Reference)

```
EducationPhase → Level → Classroom → Group → Student
Subject → CurriculumDomain → CurriculumSequence → CurriculumResource
User (Teacher) → Schedule → DailyLog → Attendance / Assessment / Grade
DailyLog → content_data (Builder blocks: lesson_structure, warmup, phases)
Student → BehaviorObservation, PedagogicalRemediation
```

---

## 5. Quick Start

```bash
# Install dependencies
composer install
npm install && npm run build

# Database setup (43 migrations + seeders)
php artisan migrate:fresh --seed

# Demo data (600 students, 30 teachers, ~1,200 daily logs, full curriculum)
php artisan db:seed --class=MiddleSchoolDatabaseSeeder

# Run tests (316 feature + unit, all green)
php artisan test
vendor/bin/pint

# Clear caches
php artisan optimize:clear
```

**Demo credentials (seeder):**
- Admin: `admin@sajel.local` / `password`
- Teacher: any `teacher_*@sajel.local` / `password`

---

## 6. Project Health (Phases 2–6 Complete)

- **Migrations:** 43 ran
- **Tests:** 339 passed, 0 failures (14,868 assertions in the latest run)
- **Filament Resources:** 20 (includes `SchoolHolidayResource` and `PedagogicalMeetingResource`)
- **Custom Pages:** Dashboard, CurriculumProgress, OfficialReports, MySchedule, QuickAttendance, LabSeatingPlanPage, DataShowMode, SchoolSupervisionHub, ClassCouncilHub, ManageSchoolSettings
- **Branch:** `main` — Phase 2/3/4 all complete (100% across the board)
- **Build:** `npm run build` passes; `vendor/bin/pint` passes

---

## 7. Phase 3 — Complete (100%)

- **Done:** AI lesson-plan prompts follow the active Arabic, English, or French locale; unsupported locales fall back to Arabic.
- **Done:** Quick Attendance is installable as an RTL PWA with offline page/static asset caching, local roster snapshots, a last-write-wins attendance queue, and authenticated, validated, idempotent batch synchronization.
- **Done:** Data Show Mode provides clean per-slide narration with locale-aware, local-voice-preferred Web Speech selection, playback controls, a persisted speech-rate preference, and keyboard shortcut.

---

The offline roster snapshot is stored at `sajel.attendance.roster.{schedule_id}.{date}`; pending
changes use `sajel.attendance.queue` with one last-write-wins entry per
`{schedule_id}:{date}:{student_id}`. The browser retries when connectivity returns or when the
teacher selects **Sync now**. The server authorizes the teacher's schedule and each student’s roster
membership, creates a planned daily log when necessary, materializes missing attendance rows, and
upserts status and remarks in batches of at most 200. Use this feature on a teacher-controlled,
protected device: the roster and unsynced attendance contain student information in browser storage.

## 8. Phase 4 — School Supervision & Administrative Auditing

- **Digital administrative visa:** Admins can stamp completed daily logs individually or in bulk;
  teachers see review details read-only, and printed/PDF registers include the official stamp and
  notes.
- **Educational Counselor supervision hub:** An admin-only, auto-refreshing cockpit filters by date,
  level, and classroom; computes daily compliance over scheduled sessions, flags only elapsed
  unlogged slots using the selected date and Algiers time, and summarizes attendance alerts and
  repeat absences across distinct days in the prior 30 days. Pending behavior cases include reporter
  and optional schedule context, with audited follow-up notes and one-click resolution.
- **Student parent report card:** Localized browser print and A4 RTL Browsershot PDF combine student
  identity, attendance totals and recent alerts, subject grade details and averages normalized to
  /20, behavior/follow-up history, and remediation outcomes. Teacher access is checked through
  `StudentPolicy`; attendance and grades are restricted to their schedules, and teacher-entered
  behavior/remediation records are restricted to their authorship and linked schedules. Admins see
  all of the student's records. Official Reports provides classroom/group/student filters, and the
  Student resource provides row actions.

## 9. School Identity Settings (هوية المؤسسة التربوية)

- **One official identity:** The admin-only `ManageSchoolSettings` page (`إعدادات المؤسسة التربوية`,
  under `التخطيط والمناهج`) edits the single `school_settings` row: school name, directorate,
  municipality/district, academic year, principal, counselor, and mechanical school code.
- **Automatic propagation:** `SchoolSetting::current()` caches the row forever and returns safe
  Algerian middle-school defaults when it is unseeded; saving or deleting busts the cache. A global
  view composer injects it into every `print.*` view and the Official Reports banner, so register
  sheets, student report cards, class councils, teacher portfolios, pedagogical memos, and their
  Browsershot PDFs always show the configured directorate, school, academic year, and signatures.
- **Authorization & tests:** `ManageSchoolSettings::canAccess()` restricts the page to admins
  (teachers receive 403). `tests/Feature/SchoolSettingsTest.php` covers the settings update, cache
  refresh, and dynamic identity output on printed registers, class councils, and Official Reports.

*Last updated: 2026-10-25 — Phases 2–6 all complete (100%) plus school identity settings. Suite: 339 passing, 0 failures, 14,868 assertions. Health check: all PASS (25 tables; AI provider gemini configured).*

---
## Sajel Pulse — Motivation Engine (نبض التميز والتحفيز التربوي)
- Zero-Click Automatic Badges: attendance_streak, comeback_champion, academic_excellence, positive_role_model
- Classroom Synergy Meter: weekly attendance + positive behavior + completed lessons
- DataShow quick kudos (`awardQuickKudos`) with instant toast and positive BehaviorObservation
- MotivationPulseHub Filament page with spotlight podium cards
- A4 RTL certificate (`print.student-certificate`) with official school header and signature boxes
- Badges surfaced in StudentReportCardService and ParentPortalService

---
## Phase 8 — Institutional Backup & CI
- `SchoolBackupService`: timestamped JSON snapshot (`metadata` + row counts for 25 tables + institutional config), excludes password/session tokens.
- `ManageSchoolSettings` header action `"تحميل نسخة احتياطية للمؤسسة (JSON)"` (Admin-only).
- `.github/workflows/ci.yml`: automated build/test on push/PR (PHP 8.3, Node 20, SQLite, build, pint, health-check, test suite).

---
## Phase 9 — Sajel Pulse Motivation System
- `StudentMotivationService`: zero-click badges + pulse score + classroom synergy meter + 3 champion cards.
- `DataShowMode`: synergy bar (`⚡ طاقة تفاعل القسم`) and instant kudos (`awardQuickKudos`).
- `MotivationPulseHub` (`heroicon-o-sparkles`): classroom filter, synergy meter, spotlight podium cards, student pulse table.
- A4 RTL certificate (`print.student-certificate`) with `StudentPolicy::view` authorization + `.pdf` download.
- Badges surfaced in `ParentPortalService` and `StudentReportCardService`.
- Tests (`MotivationPulseTest.php`), translations (`ar/en/fr`), docs (`STATUS_PROJECT.md`, `README.md`).
