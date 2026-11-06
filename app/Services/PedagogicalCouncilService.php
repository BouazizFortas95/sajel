<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Enums\PedagogicalCouncilStatus;
use App\Enums\PedagogicalCouncilType;
use App\Enums\SeminarStatus;
use App\Models\DailyLog;
use App\Models\Level;
use App\Models\PedagogicalCouncil;
use App\Models\PedagogicalCouncilAttendance;
use App\Models\PedagogicalSeminar;
use App\Models\PedagogicalSeminarAttendance;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PedagogicalCouncilService
{
    /**
     * Resolve academic year with fallback to current school setting.
     */
    public function resolveAcademicYear(?string $year = null): string
    {
        if ($year) {
            return $year;
        }

        $current = SchoolSetting::current()?->academic_year ?? '2026/2027';

        return str_replace('-', '/', $current);
    }

    /**
     * Aggregate executive pedagogical overview KPIs and telemetry.
     *
     * @return array<string, mixed>
     */
    public function getPedagogicalOverview(?string $academicYear = null, ?int $subjectId = null, ?User $user = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);

        $councilQuery = PedagogicalCouncil::query()->where('academic_year', $year);
        $seminarQuery = PedagogicalSeminar::query()->where('academic_year', $year);

        if ($subjectId) {
            $councilQuery->where('subject_id', $subjectId);
            $seminarQuery->where('subject_id', $subjectId);
        }

        // Teacher scoping: if user is teacher, filter to their subject councils or where they are an attendee
        if ($user && $user->isTeacher() && ! $user->isAdmin()) {
            $teacherSubjectIds = array_filter([$user->primary_subject_id, $user->secondary_subject_id]);
            $councilQuery->where(function ($q) use ($user, $teacherSubjectIds) {
                if (! empty($teacherSubjectIds)) {
                    $q->whereIn('subject_id', $teacherSubjectIds);
                }
                $q->orWhereHas('attendances', fn ($att) => $att->where('user_id', $user->id));
            });

            if (! empty($teacherSubjectIds)) {
                $seminarQuery->whereIn('subject_id', $teacherSubjectIds);
            }
        }

        $councils = $councilQuery->with(['subject', 'coordinator', 'attendances'])->orderByDesc('held_at')->get();
        $seminars = $seminarQuery->with(['subject', 'animator', 'attendances'])->orderBy('scheduled_at')->get();

        $totalCouncils = $councils->count();
        $completedCouncils = $councils->where('status', PedagogicalCouncilStatus::Completed)->count();
        $subjectCouncilCount = $councils->where('council_type', PedagogicalCouncilType::SubjectCouncil)->count();
        $coordinationCount = $councils->where('council_type', PedagogicalCouncilType::TermCoordination)->count();

        // Calculate attendance rate
        $totalAttSlots = 0;
        $presentAttSlots = 0;
        foreach ($councils as $c) {
            $totalAttSlots += $c->attendances->count();
            $presentAttSlots += $c->attendances->where('is_present', true)->count();
        }
        $attendanceRate = $totalAttSlots > 0 ? round(($presentAttSlots / $totalAttSlots) * 100, 1) : 100.0;

        $totalSeminars = $seminars->count();
        $upcomingSeminars = $seminars->where('scheduled_at', '>=', now()->startOfDay())->count();

        // Pacing metrics from DailyLogs
        $pacingData = $this->computeCurriculumPacingStats($year, $subjectId);

        return [
            'academicYear' => $year,
            'totalCouncils' => $totalCouncils,
            'completedCouncils' => $completedCouncils,
            'subjectCouncilCount' => $subjectCouncilCount,
            'coordinationCount' => $coordinationCount,
            'attendanceRate' => $attendanceRate,
            'totalSeminars' => $totalSeminars,
            'upcomingSeminars' => $upcomingSeminars,
            'councils' => $councils,
            'seminars' => $seminars,
            'pacingData' => $pacingData,
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Compute curriculum progression metrics per subject and grade level.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function computeCurriculumPacingStats(string $year, ?int $subjectId = null): Collection
    {
        $subjects = Subject::query()
            ->when($subjectId, fn ($q) => $q->where('id', $subjectId))
            ->get();

        $levels = Level::all();
        $stats = collect();

        foreach ($subjects as $subject) {
            foreach ($levels as $level) {
                $logs = DailyLog::query()
                    ->whereHas('schedule', function ($q) use ($subject, $level) {
                        $q->where('subject_id', $subject->id)
                            ->whereHas('classroom', fn ($c) => $c->where('level_id', $level->id));
                    })
                    ->get();

                $total = $logs->count();
                $completed = $logs->where('status', DailyLogStatus::Completed)->count();
                $percentage = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

                // Expected pacing rate based on calendar month (assuming Sep-Jun 10 months school year)
                $month = (int) now()->format('n');
                $expectedRate = match (true) {
                    $month === 9 => 10.0,
                    $month === 10 => 20.0,
                    $month === 11 => 30.0,
                    $month === 12 => 40.0,
                    $month === 1 => 50.0,
                    $month === 2 => 60.0,
                    $month === 3 => 70.0,
                    $month === 4 => 80.0,
                    $month === 5 => 90.0,
                    default => 100.0,
                };

                $lag = round($percentage - $expectedRate, 1);

                $stats->push([
                    'subject' => $subject,
                    'level' => $level,
                    'totalLessons' => $total,
                    'completedLessons' => $completed,
                    'completionRate' => $percentage,
                    'expectedRate' => $expectedRate,
                    'lag' => $lag,
                    'pacingStatus' => $lag >= 0 ? 'on_track' : ($lag >= -15 ? 'slight_delay' : 'critical_delay'),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Create a pedagogical council record and initialize its teacher attendance roster.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int|string>  $teacherIds
     */
    public function createCouncil(array $data, array $teacherIds = []): PedagogicalCouncil
    {
        return DB::transaction(function () use ($data, $teacherIds) {
            $data['academic_year'] = $this->resolveAcademicYear($data['academic_year'] ?? null);

            $council = PedagogicalCouncil::create($data);

            if (empty($council->pv_reference)) {
                $council->pv_reference = $council->generatePvReference();
                $council->save();
            }

            // If specific teacher IDs provided, add them; otherwise auto-populate subject teachers
            if (empty($teacherIds) && ! empty($council->subject_id)) {
                $teacherIds = User::where(function ($q) use ($council) {
                    $q->where('primary_subject_id', $council->subject_id)
                        ->orWhere('secondary_subject_id', $council->subject_id)
                        ->orWhereHas('schedules', fn ($s) => $s->where('subject_id', $council->subject_id));
                })->pluck('id')->all();
            }

            foreach ($teacherIds as $teacherId) {
                PedagogicalCouncilAttendance::updateOrCreate(
                    [
                        'pedagogical_council_id' => $council->id,
                        'user_id' => (int) $teacherId,
                    ],
                    [
                        'is_present' => true,
                        'role_in_council' => ((int) $teacherId === (int) $council->coordinator_id) ? 'منسق المادة' : 'أستاذ عضو',
                    ]
                );
            }

            return $council->fresh(['subject', 'coordinator', 'attendances.user']);
        });
    }

    /**
     * Update an existing pedagogical council and sync attendances.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>|null  $attendancesData
     */
    public function updateCouncil(
        PedagogicalCouncil $council,
        array $data,
        ?array $attendancesData = null
    ): PedagogicalCouncil {
        return DB::transaction(function () use ($council, $data, $attendancesData) {
            $council->update($data);

            if ($attendancesData !== null) {
                foreach ($attendancesData as $userId => $att) {
                    PedagogicalCouncilAttendance::updateOrCreate(
                        [
                            'pedagogical_council_id' => $council->id,
                            'user_id' => (int) $userId,
                        ],
                        [
                            'is_present' => (bool) ($att['is_present'] ?? true),
                            'role_in_council' => $att['role_in_council'] ?? 'أستاذ عضو',
                            'absence_justification' => $att['absence_justification'] ?? null,
                            'notes' => $att['notes'] ?? null,
                        ]
                    );
                }
            }

            return $council->fresh(['subject', 'coordinator', 'attendances.user']);
        });
    }

    /**
     * Record a new pedagogical seminar or inspectoral training day.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int|string>  $participantUserIds
     */
    public function recordSeminar(array $data, array $participantUserIds = []): PedagogicalSeminar
    {
        return DB::transaction(function () use ($data, $participantUserIds) {
            $data['academic_year'] = $this->resolveAcademicYear($data['academic_year'] ?? null);

            $seminar = PedagogicalSeminar::create($data);

            foreach ($participantUserIds as $userId) {
                PedagogicalSeminarAttendance::updateOrCreate(
                    [
                        'pedagogical_seminar_id' => $seminar->id,
                        'user_id' => (int) $userId,
                    ],
                    [
                        'attended' => true,
                    ]
                );
            }

            return $seminar->fresh(['subject', 'animator', 'attendances.user']);
        });
    }

    /**
     * Assemble printable official Minutes of Subject Council (محضر مجلس التعليم).
     *
     * @return array<string, mixed>
     */
    public function assembleCouncilPvData(PedagogicalCouncil $council): array
    {
        $council->loadMissing(['subject', 'coordinator', 'attendances.user']);

        $attendees = $council->attendances;
        $present = $attendees->where('is_present', true);
        $absent = $attendees->where('is_present', false);

        return [
            'council' => $council,
            'subject' => $council->subject,
            'coordinator' => $council->coordinator,
            'presentTeachers' => $present,
            'absentTeachers' => $absent,
            'totalTeachers' => $attendees->count(),
            'attendanceRate' => $attendees->count() > 0 ? round(($present->count() / $attendees->count()) * 100, 1) : 100.0,
            'school' => SchoolSetting::current(),
            'academicYear' => $council->academic_year,
        ];
    }

    /**
     * Assemble printable Curriculum Pacing Audit and Progress Ledger.
     *
     * @return array<string, mixed>
     */
    public function assembleCurriculumPacingData(?string $academicYear = null, ?int $subjectId = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);
        $stats = $this->computeCurriculumPacingStats($year, $subjectId);
        $subject = $subjectId ? Subject::find($subjectId) : null;

        $overallTotal = $stats->sum('totalLessons');
        $overallCompleted = $stats->sum('completedLessons');
        $overallRate = $overallTotal > 0 ? round(($overallCompleted / $overallTotal) * 100, 1) : 0.0;

        return [
            'academicYear' => $year,
            'subject' => $subject,
            'rows' => $stats,
            'overallTotal' => $overallTotal,
            'overallCompleted' => $overallCompleted,
            'overallRate' => $overallRate,
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Assemble printable Pedagogical Seminars and Training Calendar.
     *
     * @return array<string, mixed>
     */
    public function assembleSeminarCalendarData(?string $academicYear = null, ?int $subjectId = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);

        $seminars = PedagogicalSeminar::query()
            ->where('academic_year', $year)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->with(['subject', 'animator', 'attendances.user'])
            ->orderBy('scheduled_at')
            ->get();

        return [
            'academicYear' => $year,
            'subject' => $subjectId ? Subject::find($subjectId) : null,
            'seminars' => $seminars,
            'totalSeminars' => $seminars->count(),
            'completedSeminars' => $seminars->where('status', SeminarStatus::Completed)->count(),
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Generate A4 portrait printable PDF for Council Minutes.
     */
    public function generateCouncilPvPdf(PedagogicalCouncil $council): string
    {
        $data = $this->assembleCouncilPvData($council);
        $data['inlineCss'] = true;

        $html = view('print.pedagogical-council-pv', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate A4 landscape printable PDF for Curriculum Pacing Ledger.
     */
    public function generateCurriculumPacingPdf(?string $academicYear = null, ?int $subjectId = null): string
    {
        $data = $this->assembleCurriculumPacingData($academicYear, $subjectId);
        $data['inlineCss'] = true;

        $html = view('print.curriculum-pacing-ledger', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(6, 6, 6, 6)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate A4 landscape printable PDF for Pedagogical Seminars Calendar.
     */
    public function generateSeminarCalendarPdf(?string $academicYear = null, ?int $subjectId = null): string
    {
        $data = $this->assembleSeminarCalendarData($academicYear, $subjectId);
        $data['inlineCss'] = true;

        $html = view('print.pedagogical-seminars-calendar', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(6, 6, 6, 6)
            ->showBackground()
            ->pdf();
    }
}
