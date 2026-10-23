# Sajel (سجِّل) — Digital Class Register & Classroom Assistant

> Arabic-first (RTL), English code, French locale support. Built for Algerian Middle Schools using **Laravel 12**, **Filament PHP v3.2**, **Livewire 3**, **Tailwind CSS v4**, **SQLite**, and **Spatie Browsershot** for PDF export.

---

## 1. Project Overview

**Sajel** (سجِّل — "record / register") is a teacher-oriented digital platform that replaces paper class registers (`دفتر النصوص`) with structured daily lesson logs (`DailyLog`), interactive timetables (`TeacherTimetable`), a reusable pedagogical memo bank (`PedagogicalMemoResource`), gradebooks (`Gradebook` / `Assessment`), official curriculum tracking (`CurriculumProgress`), and a full classroom-assistant suite (Quick Attendance, Behavior Observations, Active Learning Strategies, Interactive Toolbar, and Fullscreen Data Show Mode).

Key goals:
- Prepare lessons per weekly schedule slot (`Schedule`).
- Confirm logs so they become the official register entry (`DailyLogStatus::Completed`).
- Record attendance (`Attendance`) and assessment scores (`Grade`) against the same roster.
- Print reusable A4 RTL memos and official reports (Weekly Schedule, Consolidated Attendance, Grade Transcripts).
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
| Quick Attendance | `QuickAttendance` (Page + Livewire) + 19 translations | One-click attendance for current session roster |
| Behavior & Observations | `BehaviorObservationResource` | Linked to student (`student_id`), category, notes, follow-up status |

### Axis 3 — Classroom Activities & Presentation (`3/3` ✅)

| Feature | Component / Page | Notes |
|---|---|---|
| Active Learning Strategies | `ActiveLearningStrategyResource` + `DailyLogResource` phase `Select` | 8 seeded cards (Think-Pair-Share, Jigsaw, Exit Ticket, etc.) |
| Interactive Classroom Toolbar | `ClassroomToolbar` (Livewire + Page) | Timer, random student picker, auto group generator |
| Fullscreen Data Show Mode | `DataShowMode` (Livewire + Page + Blade) | Auto-select today's log; 3 themes; font zoom; slide deck from `content_data` phases; keyboard nav; quick tools drawer |

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
| Testing | PHPUnit | 257 passing, 14,603 assertions |

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

# Database setup (28 migrations + seeders)
php artisan migrate:fresh --seed

# Demo data (600 students, 30 teachers, ~1,200 daily logs, full curriculum)
php artisan db:seed --class=MiddleSchoolDatabaseSeeder

# Run tests (257 feature + unit, all green)
php artisan test
vendor/bin/pint

# Clear caches
php artisan optimize:clear
```

**Demo credentials (seeder):**
- Admin: `admin@sajel.local` / `password`
- Teacher: any `teacher_*@sajel.local` / `password`

---

## 6. Project Health (Updated — Phase 2 Complete)

- **Migrations:** 30 ran (28 base + behavior observations + pedagogical remediation)
- **Tests:** 257 passed (14,603 assertions)
- **Filament Resources:** 18 (includes new `BehaviorObservationResource`, `PedagogicalRemediationResource`, `ActiveLearningStrategyResource`)
- **Custom Pages:** Dashboard, CurriculumProgress, OfficialReports, MySchedule, QuickAttendance, LabSeatingPlan, LabSeatingPlanPage, QuickAttendance (done), ActiveLearningStrategies (done), ClassroomToolbar (done), DataShowMode (done)
- **Branch:** `main` — clean working tree, `git status` clean
- **Build:** `npm run build` passes; `vendor/bin/pint` passes

---

## 7. Known Gaps / Next Steps (Phase 3 — Not Yet Planned)

- Advanced AI lesson-plan integration (OpenAI / Gemini) is present via `AiLessonPlannerService` but can be extended with multi-language prompts.
- Mobile-responsive native app (optional PWA enhancement) for offline attendance.
- Full Arabic voice/narration for Data Show Mode projector display.

---

*Last updated: 2026-10-23 — Phase 2 Roadmap Complete (14 / 14 items, 100%).*
