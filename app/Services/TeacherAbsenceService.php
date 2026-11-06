<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Enums\TeacherAbsenceReason;
use App\Enums\TeacherAbsenceStatus;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\SchoolSetting;
use App\Models\TeacherAbsence;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TeacherAbsenceService
{
    /**
     * Record a new teacher absence and initialize its coverage status.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordAbsence(array $data, ?User $recordedBy = null): TeacherAbsence
    {
        $absence = TeacherAbsence::create([
            'teacher_id' => $data['teacher_id'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'reason' => $data['reason'] instanceof TeacherAbsenceReason ? $data['reason'] : TeacherAbsenceReason::from($data['reason']),
            'status' => TeacherAbsenceStatus::Pending,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by_id' => $recordedBy?->id ?? auth()->id(),
        ]);

        $this->refreshAbsenceStatus($absence);

        return $absence;
    }

    /**
     * Get active academic school dates between two bounds, skipping weekends and official holidays.
     *
     * @return Collection<int, Carbon>
     */
    public function getActiveSchoolDates(CarbonInterface|string $start, CarbonInterface|string $end): Collection
    {
        $startDate = $start instanceof CarbonInterface ? Carbon::instance($start) : Carbon::parse($start);
        $endDate = $end instanceof CarbonInterface ? Carbon::instance($end) : Carbon::parse($end);

        if ($startDate->gt($endDate)) {
            return collect();
        }

        $dates = collect();
        $cursor = $startDate->copy()->startOfDay();

        while ($cursor->lte($endDate)) {
            // In Algeria: Friday & Saturday are the weekend
            if (! $cursor->isFriday() && ! $cursor->isSaturday() && ! SchoolHoliday::isHoliday($cursor)) {
                $dates->push($cursor->copy());
            }

            $cursor->addDay();
        }

        return $dates;
    }

    /**
     * Resolve all scheduled sessions affected by the given teacher absence.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getAffectedSessions(TeacherAbsence $absence): Collection
    {
        $dates = $this->getActiveSchoolDates($absence->starts_at, $absence->ends_at);
        $sessions = collect();

        foreach ($dates as $date) {
            $dayOfWeek = DayOfWeek::fromCarbon($date);

            $schedules = Schedule::where('user_id', $absence->teacher_id)
                ->where('day_of_week', $dayOfWeek)
                ->with(['subject', 'classroom', 'group.classroom'])
                ->orderBy('start_time')
                ->get();

            foreach ($schedules as $schedule) {
                $dailyLog = DailyLog::where('schedule_id', $schedule->id)
                    ->whereDate('date', $date->toDateString())
                    ->with(['substituteTeacher'])
                    ->first();

                $isCovered = filled($dailyLog?->substitute_teacher_id);
                $isCompleted = $dailyLog?->status === DailyLogStatus::Completed;

                $startCarbon = Carbon::parse($schedule->start_time);
                $endCarbon = Carbon::parse($schedule->end_time);
                $durationHours = round($startCarbon->diffInMinutes($endCarbon) / 60, 2);

                $sessions->push([
                    'date' => $date->toDateString(),
                    'date_carbon' => $date,
                    'day_name' => $dayOfWeek->label(),
                    'schedule' => $schedule,
                    'schedule_id' => $schedule->id,
                    'classroom' => $schedule->resolvedClassroom(),
                    'group' => $schedule->group,
                    'subject' => $schedule->subject,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'duration_hours' => $durationHours,
                    'daily_log' => $dailyLog,
                    'substitute_teacher' => $dailyLog?->substituteTeacher,
                    'is_covered' => $isCovered,
                    'is_completed' => $isCompleted,
                    'status' => $isCompleted ? 'completed' : ($isCovered ? 'covered' : 'uncovered'),
                ]);
            }
        }

        return $sessions;
    }

    /**
     * Find available teachers who can substitute for a specific scheduled session without time collisions.
     *
     * @return Collection<int, array{teacher: User, match_type: string, match_label: string, priority: int}>
     */
    public function findAvailableSubstitutes(Schedule $schedule, CarbonInterface|string $date): Collection
    {
        $dateCarbon = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $dateString = $dateCarbon->toDateString();
        $dayOfWeek = $schedule->day_of_week;

        // 1. All other teachers
        $teachers = User::whereIn('role', [Role::Teacher, Role::HeadTeacher])
            ->where('id', '!=', $schedule->user_id)
            ->with(['primarySubject', 'secondarySubject'])
            ->get();

        $available = collect();

        $targetStart = Carbon::parse($schedule->start_time)->format('H:i');
        $targetEnd = Carbon::parse($schedule->end_time)->format('H:i');

        foreach ($teachers as $teacher) {
            // Check if this teacher is absent on this date
            $isAbsent = TeacherAbsence::where('teacher_id', $teacher->id)
                ->activeOnDate($dateString)
                ->exists();

            if ($isAbsent) {
                continue;
            }

            // Check if this teacher has an overlapping scheduled lesson at this day and time
            $hasScheduleConflict = Schedule::where('user_id', $teacher->id)
                ->where('day_of_week', $dayOfWeek)
                ->get(['start_time', 'end_time'])
                ->contains(function (Schedule $existing) use ($targetStart, $targetEnd): bool {
                    $existingStart = $existing->start_time?->format('H:i');
                    $existingEnd = $existing->end_time?->format('H:i');

                    return $existingStart !== null && $existingEnd !== null
                        && $existingStart < $targetEnd && $existingEnd > $targetStart;
                });

            if ($hasScheduleConflict) {
                continue;
            }

            // Priority match score based on subject specialization
            $priority = 3;
            $matchType = 'general';
            $matchLabel = __('إشراف بيداغوجي / مداومة تربوية');

            if ($teacher->primary_subject_id === $schedule->subject_id) {
                $priority = 1;
                $matchType = 'primary';
                $matchLabel = __('نفس تخصص المادة الأساسي (:subject)', ['subject' => $teacher->primarySubject?->name]);
            } elseif ($teacher->secondary_subject_id === $schedule->subject_id) {
                $priority = 2;
                $matchType = 'secondary';
                $matchLabel = __('تخصص المادة الثانوي (:subject)', ['subject' => $teacher->secondarySubject?->name]);
            }

            $available->push([
                'teacher' => $teacher,
                'match_type' => $matchType,
                'match_label' => $matchLabel,
                'priority' => $priority,
            ]);
        }

        return $available->sortBy([
            ['priority', 'asc'],
            [fn ($item) => $item['teacher']->name, 'asc'],
        ])->values();
    }

    /**
     * Assign a substitute teacher to an affected session.
     */
    public function assignSubstitute(
        Schedule $schedule,
        CarbonInterface|string $date,
        User $substitute,
        ?string $instructions = null
    ): DailyLog {
        $dateString = $date instanceof CarbonInterface ? $date->toDateString() : (string) $date;

        $log = DailyLog::where('schedule_id', $schedule->id)
            ->whereDate('date', $dateString)
            ->first();

        if (! $log) {
            $log = new DailyLog([
                'schedule_id' => $schedule->id,
                'date' => $dateString,
                'title' => __('حصة استخلاف بيداغوجي').' — '.($schedule->subject?->name ?? ''),
                'status' => DailyLogStatus::Planned,
                'content_data' => [],
            ]);
        }

        $log->substitute_teacher_id = $substitute->id;

        if (filled($instructions)) {
            $note = __('توجيهات الاستخلاف: :notes', ['notes' => $instructions]);
            $log->observations = filled($log->observations)
                ? $log->observations."\n".$note
                : $note;
        }

        $log->save();

        // Refresh parent absence status
        $absence = TeacherAbsence::where('teacher_id', $schedule->user_id)
            ->activeOnDate($dateString)
            ->first();

        if ($absence) {
            $this->refreshAbsenceStatus($absence);
        }

        return $log;
    }

    /**
     * Unassign a substitute teacher from a daily log.
     */
    public function unassignSubstitute(DailyLog $dailyLog): void
    {
        $teacherId = $dailyLog->schedule?->user_id;
        $date = $dailyLog->date?->toDateString();

        $dailyLog->substitute_teacher_id = null;
        $dailyLog->save();

        if ($teacherId && $date) {
            $absence = TeacherAbsence::where('teacher_id', $teacherId)
                ->activeOnDate($date)
                ->first();

            if ($absence) {
                $this->refreshAbsenceStatus($absence);
            }
        }
    }

    /**
     * Recalculate and update the coverage status of a teacher absence.
     */
    public function refreshAbsenceStatus(TeacherAbsence $absence): void
    {
        $sessions = $this->getAffectedSessions($absence);

        if ($sessions->isEmpty()) {
            $absence->update(['status' => TeacherAbsenceStatus::Completed]);

            return;
        }

        $totalSessions = $sessions->count();
        $coveredSessions = $sessions->where('is_covered', true)->count();

        if ($coveredSessions === $totalSessions) {
            $absence->update(['status' => TeacherAbsenceStatus::Covered]);
        } elseif ($coveredSessions > 0) {
            $absence->update(['status' => TeacherAbsenceStatus::PartiallyCovered]);
        } else {
            $absence->update(['status' => TeacherAbsenceStatus::Uncovered]);
        }
    }

    /**
     * Compute radar KPIs across all teacher absences.
     *
     * @return array<string, mixed>
     */
    public function getRadarKpis(?int $year = null, ?int $month = null): array
    {
        $query = TeacherAbsence::query();

        if ($year && $month) {
            $query->whereYear('starts_at', $year)->whereMonth('starts_at', $month);
        }

        $absences = $query->with('teacher')->get();
        $totalAbsences = $absences->count();

        $today = today()->toDateString();
        $activeTodayCount = TeacherAbsence::activeOnDate($today)->count();

        $allSessions = collect();
        foreach ($absences as $absence) {
            $allSessions = $allSessions->merge($this->getAffectedSessions($absence));
        }

        $totalSessionsCount = $allSessions->count();
        $coveredSessionsCount = $allSessions->where('is_covered', true)->count();
        $uncoveredSessionsCount = $totalSessionsCount - $coveredSessionsCount;
        $totalMissedHours = $allSessions->sum('duration_hours');

        $coverageRate = $totalSessionsCount > 0
            ? round(($coveredSessionsCount / $totalSessionsCount) * 100, 1)
            : 100.0;

        $distinctSubstitutesCount = $allSessions
            ->pluck('substitute_teacher')
            ->filter()
            ->unique('id')
            ->count();

        return [
            'total_absences' => $totalAbsences,
            'active_today' => $activeTodayCount,
            'total_sessions' => $totalSessionsCount,
            'covered_sessions' => $coveredSessionsCount,
            'uncovered_sessions' => $uncoveredSessionsCount,
            'total_missed_hours' => $totalMissedHours,
            'coverage_rate' => $coverageRate,
            'distinct_substitutes' => $distinctSubstitutesCount,
        ];
    }

    /**
     * Assemble monthly absence and replacement data for official administrative reporting.
     *
     * @return array<string, mixed>
     */
    public function assembleMonthlyLedger(int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $absences = TeacherAbsence::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('starts_at', [$startDate, $endDate])
                ->orWhereBetween('ends_at', [$startDate, $endDate]);
        })
            ->with(['teacher.primarySubject', 'recordedBy'])
            ->orderBy('starts_at')
            ->get();

        $records = collect();
        $totalHoursMissed = 0;
        $totalHoursCovered = 0;

        foreach ($absences as $absence) {
            $sessions = $this->getAffectedSessions($absence);
            $hours = $sessions->sum('duration_hours');
            $coveredHours = $sessions->where('is_covered', true)->sum('duration_hours');

            $substitutes = $sessions->pluck('substitute_teacher')
                ->filter()
                ->unique('id')
                ->map(fn ($t) => $t->name)
                ->implode(', ');

            $records->push([
                'absence' => $absence,
                'teacher' => $absence->teacher,
                'subject' => $absence->teacher?->primarySubject?->name ?? '—',
                'starts_at' => $absence->starts_at,
                'ends_at' => $absence->ends_at,
                'duration_days' => $absence->durationInDays(),
                'reason_label' => $absence->reason->label(),
                'reference' => $absence->reference_number ?: '—',
                'affected_sessions_count' => $sessions->count(),
                'hours_missed' => $hours,
                'hours_covered' => $coveredHours,
                'substitutes_names' => $substitutes ?: '—',
                'status' => $absence->status,
            ]);

            $totalHoursMissed += $hours;
            $totalHoursCovered += $coveredHours;
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->translatedFormat('F Y'),
            'records' => $records,
            'total_absences' => $absences->count(),
            'total_hours_missed' => $totalHoursMissed,
            'total_hours_covered' => $totalHoursCovered,
            'overall_coverage_rate' => $totalHoursMissed > 0 ? round(($totalHoursCovered / $totalHoursMissed) * 100, 1) : 100.0,
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Generate printable PDF for a substitution assignment mandate.
     */
    public function generateMandatePdf(TeacherAbsence $absence, DailyLog $log, User $substitute): string
    {
        $html = view('print.substitute-mandate', [
            'absence' => $absence,
            'log' => $log,
            'schedule' => $log->schedule,
            'classroom' => $log->schedule?->resolvedClassroom(),
            'subject' => $log->schedule?->subject,
            'absentTeacher' => $absence->teacher,
            'substituteTeacher' => $substitute,
            'school' => SchoolSetting::current(),
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable PDF for the official monthly teacher absence & replacement ledger.
     */
    public function generateMonthlyLedgerPdf(int $year, int $month): string
    {
        $data = $this->assembleMonthlyLedger($year, $month);
        $data['inlineCss'] = true;

        $html = view('print.monthly-absence-ledger', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    }

    /**
     * Record statutory work resumption (محضر استئناف العمل).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordWorkResumption(TeacherAbsence $absence, array $data): TeacherAbsence
    {
        $resumedAt = isset($data['resumed_at']) ? Carbon::parse($data['resumed_at']) : now();
        $pvNumber = $data['resumption_pv_number'] ?? sprintf('PV-REP-%d-%04d', $resumedAt->year, $absence->id);

        $absence->update([
            'resumed_at' => $resumedAt,
            'resumption_pv_number' => $pvNumber,
            'resumption_notes' => $data['resumption_notes'] ?? $absence->resumption_notes,
        ]);

        return $absence->fresh();
    }

    /**
     * Issue an official salary deduction notice (إشعار بالخصم من الراتب).
     *
     * @param  array<string, mixed>  $data
     */
    public function issueSalaryDeductionNotice(TeacherAbsence $absence, array $data): TeacherAbsence
    {
        $deductionDays = (int) ($data['deduction_days'] ?? $absence->durationInDays());
        $noticeNumber = $data['deduction_ref_number'] ?? sprintf('NOTIF-RET-%d-%04d', now()->year, $absence->id);
        $notifiedAt = isset($data['deduction_notified_at']) ? Carbon::parse($data['deduction_notified_at']) : now();

        $absence->update([
            'is_deduction_notified' => true,
            'deduction_days' => max(1, $deductionDays),
            'deduction_ref_number' => $noticeNumber,
            'deduction_notified_at' => $notifiedAt,
        ]);

        return $absence->fresh();
    }

    /**
     * Mark absence as covered by an administrative requisition (تسخير إداري رسمي).
     *
     * @param  array<string, mixed>  $data
     */
    public function issueRequisitionMandate(TeacherAbsence $absence, array $data): TeacherAbsence
    {
        $reqNumber = $data['requisition_ref_number'] ?? sprintf('ORD-REQ-%d-%04d', now()->year, $absence->id);
        $justification = $data['requisition_justification'] ?? __('ضرورة المصلحة وضمان السير المنتظم للدروس وفق التشريع المدرسي الجزائري');

        $absence->update([
            'is_requisition' => true,
            'requisition_ref_number' => $reqNumber,
            'requisition_justification' => $justification,
        ]);

        return $absence->fresh();
    }

    /**
     * Generate printable PDF for official work resumption minutes (PV de reprise de travail).
     */
    public function generateResumptionPvPdf(TeacherAbsence $absence): string
    {
        $absence->load(['teacher.primarySubject', 'recordedBy']);
        $html = view('print.teacher-resumption-pv', [
            'absence' => $absence,
            'teacher' => $absence->teacher,
            'school' => SchoolSetting::current(),
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable PDF for salary deduction notice (إشعار بالخصم من الراتب).
     */
    public function generateDeductionNoticePdf(TeacherAbsence $absence): string
    {
        $absence->load(['teacher.primarySubject', 'recordedBy']);
        $html = view('print.salary-deduction-notice', [
            'absence' => $absence,
            'teacher' => $absence->teacher,
            'school' => SchoolSetting::current(),
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable PDF for administrative requisition order (قرار تسخير إداري رسمي).
     */
    public function generateRequisitionOrderPdf(TeacherAbsence $absence, DailyLog $log, User $substitute): string
    {
        $absence->load(['teacher.primarySubject']);
        $log->load(['schedule.subject', 'schedule.classroom', 'schedule.group']);

        $html = view('print.administrative-requisition-order', [
            'absence' => $absence,
            'log' => $log,
            'schedule' => $log->schedule,
            'classroom' => $log->schedule?->resolvedClassroom(),
            'subject' => $log->schedule?->subject,
            'absentTeacher' => $absence->teacher,
            'substituteTeacher' => $substitute,
            'school' => SchoolSetting::current(),
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }
}
