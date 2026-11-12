<?php

namespace App\Services;

use App\Enums\DisciplinarySanction;
use App\Enums\DisciplinaryStatus;
use App\Enums\InfractionDegree;
use App\Models\DisciplinaryRecord;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;
use Spatie\Browsershot\Browsershot;

class DisciplinaryCouncilService
{
    /**
     * Resolve and normalize academic year (handles both '2026/2027' and '2026-2027').
     */
    public function resolveAcademicYear(?string $year = null): string
    {
        if ($year) {
            return $year;
        }

        $current = SchoolSetting::current();
        if ($current?->academic_year) {
            return $current->academic_year;
        }

        return '2026/2027';
    }

    /**
     * Schedule a new formal Disciplinary Council session for a student.
     */
    public function scheduleSession(
        Student $student,
        array|string|null $data = null,
        mixed $hearingDate = null,
        mixed $degree = null,
        ?string $incidentDescription = null,
        mixed $incidentDate = null,
        ?User $reportedBy = null,
        ?string $sessionNumber = null,
        bool $guardianSummoned = false,
        ?string $academicYear = null,
        ?User $president = null,
        ?array $attendees = null,
        ?User $reporter = null
    ): DisciplinaryRecord {
        if (is_array($data)) {
            $academicYear = $data['academic_year'] ?? $academicYear;
            $hearingDate = $data['session_date'] ?? $data['hearing_date'] ?? $hearingDate;
            $degree = $data['infraction_degree'] ?? $data['degree'] ?? $degree;
            $incidentDescription = $data['incident_description'] ?? $incidentDescription;
            $incidentDate = $data['incident_date'] ?? $incidentDate;
            $reportedBy = $reporter ?? $data['reported_by'] ?? $data['reported_by_id'] ?? $reportedBy;
            $sessionNumber = $data['session_number'] ?? $sessionNumber;
            $guardianSummoned = (bool) ($data['guardian_summoned'] ?? (! empty($data['summons_sent_at'])));
            $president = $president ?? $data['president'] ?? null;
            $attendees = $data['attendees'] ?? $attendees;
        }

        $year = $this->resolveAcademicYear($academicYear);

        if ($degree instanceof InfractionDegree) {
            $degreeEnum = $degree;
        } elseif (is_string($degree) && ! empty($degree)) {
            $degreeEnum = InfractionDegree::from($degree);
        } else {
            $degreeEnum = InfractionDegree::SecondDegree;
        }

        $count = DisciplinaryRecord::forAcademicYear($year)->count();
        $finalSessionNumber = $sessionNumber ?: sprintf('CD-%s-%03d', substr(str_replace('/', '-', $year), 0, 4), $count + 1);

        $finalIncidentDate = ! empty($incidentDate) ? Carbon::parse($incidentDate)->toDateString() : now()->toDateString();
        $finalSessionDate = ! empty($hearingDate) ? Carbon::parse($hearingDate)->toDateTimeString() : now()->addDays(3)->setTime(10, 0)->toDateTimeString();

        $summonedAt = $guardianSummoned ? now()->toDateString() : null;

        return DisciplinaryRecord::create([
            'student_id' => $student->id,
            'academic_year' => $year,
            'session_number' => $finalSessionNumber,
            'incident_date' => $finalIncidentDate,
            'session_date' => $finalSessionDate,
            'infraction_degree' => $degreeEnum,
            'incident_description' => $incidentDescription ?: __('مخالفة انضباطية تستوجب المثول أمام مجلس التأديب.'),
            'reported_by_id' => $reportedBy?->id ?? auth()->id(),
            'status' => DisciplinaryStatus::Scheduled,
            'summons_sent_at' => $summonedAt,
            'guardian_attended' => false,
            'president_id' => $president?->id,
            'attendees' => $attendees ?: [
                'مدير المؤسسة — رئيساً',
                'المستشار الرئيسي للتربية',
                'ناظر الدروس',
                'أستاذ(ة) ممثل عن مجلس القسم',
                'مندوب جمعية أولياء التلاميذ',
            ],
        ]);
    }

    /**
     * Record council deliberation, student defense, and legal sanction decision.
     */
    public function recordDeliberation(
        DisciplinaryRecord $record,
        array|DisciplinarySanction|string|null $sanction = null,
        ?int $exclusionDays = null,
        mixed $exclusionStartDate = null,
        mixed $exclusionEndDate = null,
        ?float $restitutionAmount = null,
        ?string $defenseSummary = null,
        ?string $decisionNotes = null,
        ?User $president = null,
        mixed $deliberatedAt = null,
        bool $guardianAttended = false,
        mixed $guardianNotifiedAt = null
    ): DisciplinaryRecord {
        if (is_array($sanction)) {
            $decisionData = $sanction;
            $sanction = $decisionData['decision'] ?? $decisionData['sanction'] ?? DisciplinarySanction::Warning;
            $exclusionDays = ! empty($decisionData['exclusion_days']) ? (int) $decisionData['exclusion_days'] : $exclusionDays;
            $exclusionStartDate = $decisionData['exclusion_start_date'] ?? $exclusionStartDate;
            $exclusionEndDate = $decisionData['exclusion_end_date'] ?? $exclusionEndDate;
            $restitutionAmount = ! empty($decisionData['compensation_amount']) ? (float) $decisionData['compensation_amount'] : (! empty($decisionData['restitution_amount']) ? (float) $decisionData['restitution_amount'] : $restitutionAmount);
            $defenseSummary = $decisionData['student_statement'] ?? $decisionData['defense_summary'] ?? $defenseSummary;
            $decisionNotes = $decisionData['notes'] ?? $decisionData['decision_notes'] ?? $decisionNotes;
            $president = $president ?? $decisionData['president'] ?? null;
            $deliberatedAt = $decisionData['deliberated_at'] ?? $deliberatedAt;
            $guardianAttended = (bool) ($decisionData['guardian_attended'] ?? $guardianAttended);
            $guardianNotifiedAt = $decisionData['guardian_notified_at'] ?? $guardianNotifiedAt;
        }

        if ($sanction instanceof DisciplinarySanction) {
            $decisionEnum = $sanction;
        } elseif (is_string($sanction) && ! empty($sanction)) {
            $decisionEnum = DisciplinarySanction::from($sanction);
        } else {
            $decisionEnum = DisciplinarySanction::Warning;
        }

        $start = ! empty($exclusionStartDate) ? Carbon::parse($exclusionStartDate)->toDateString() : null;
        $end = ! empty($exclusionEndDate) ? Carbon::parse($exclusionEndDate)->toDateString() : null;
        $delibDate = ! empty($deliberatedAt) ? Carbon::parse($deliberatedAt)->toDateString() : now()->toDateString();
        $notifiedDate = ! empty($guardianNotifiedAt) ? Carbon::parse($guardianNotifiedAt)->toDateString() : null;

        $record->update([
            'status' => DisciplinaryStatus::Deliberated,
            'decision' => $decisionEnum,
            'student_statement' => $defenseSummary ?? $record->student_statement,
            'guardian_attended' => $guardianAttended,
            'exclusion_days' => $exclusionDays,
            'exclusion_start_date' => $start,
            'exclusion_end_date' => $end,
            'compensation_amount' => $restitutionAmount,
            'deliberated_at' => $delibDate,
            'guardian_notified_at' => $notifiedDate ?? $record->guardian_notified_at,
            'notes' => $decisionNotes ?? $record->notes,
            'president_id' => $president?->id ?? $record->president_id ?? auth()->id(),
        ]);

        return $record->fresh();
    }

    /**
     * Mark formal summons as dispatched to legal guardian.
     */
    public function markGuardianSummoned(DisciplinaryRecord $record, ?string $date = null): void
    {
        $record->update([
            'summons_sent_at' => $date ? Carbon::parse($date)->toDateString() : now()->toDateString(),
        ]);
    }

    /**
     * Mark formal decision notification as dispatched to legal guardian.
     */
    public function markGuardianNotified(DisciplinaryRecord $record, ?string $date = null): void
    {
        $record->update([
            'guardian_notified_at' => $date ? Carbon::parse($date)->toDateString() : now()->toDateString(),
        ]);
    }

    /**
     * Aggregate disciplinary and behavioral KPIs for the academic year.
     *
     * @return array<string, mixed>
     */
    public function getDisciplinaryKpis(?string $academicYear = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);
        $records = DisciplinaryRecord::forAcademicYear($year)->get();

        $totalCases = $records->count();
        $scheduledCount = $records->where('status', DisciplinaryStatus::Scheduled)->count();
        $deliberatedCount = $records->whereIn('status', [DisciplinaryStatus::Deliberated, DisciplinaryStatus::Executed])->count();

        $temporaryExclusionCount = $records->where('decision', DisciplinarySanction::TemporaryExclusion)->count();
        $definitiveExclusionCount = $records->where('decision', DisciplinarySanction::DefinitiveExclusion)->count();
        $warningCount = $records->where('decision', DisciplinarySanction::Warning)->count();
        $reprimandCount = $records->where('decision', DisciplinarySanction::Reprimand)->count();
        $communityServiceCount = $records->where('decision', DisciplinarySanction::CommunityService)->count();
        $acquittalCount = $records->where('decision', DisciplinarySanction::Acquittal)->count();

        $totalCompensationAmount = (float) $records->sum('compensation_amount');

        $notifiedCount = $records->whereNotNull('guardian_notified_at')->count();
        $notificationRate = $totalCases > 0 ? round(($notifiedCount / $totalCases) * 100, 1) : 0.0;

        $byDegree = [
            'first_degree' => $records->where('infraction_degree', InfractionDegree::FirstDegree)->count(),
            'second_degree' => $records->where('infraction_degree', InfractionDegree::SecondDegree)->count(),
            'third_degree' => $records->where('infraction_degree', InfractionDegree::ThirdDegree)->count(),
        ];

        $bySanction = [
            'warning' => $warningCount,
            'reprimand' => $reprimandCount,
            'temporary_exclusion' => $temporaryExclusionCount,
            'definitive_exclusion' => $definitiveExclusionCount,
            'community_service' => $communityServiceCount,
            'financial_compensation' => $records->where('decision', DisciplinarySanction::FinancialCompensation)->count(),
            'acquittal' => $acquittalCount,
        ];

        return [
            'academic_year' => $year,
            'total_records' => $totalCases,
            'total_cases' => $totalCases,
            'scheduled_count' => $scheduledCount,
            'deliberated_count' => $deliberatedCount,
            'warning_count' => $warningCount,
            'reprimand_count' => $reprimandCount,
            'temporary_exclusion_count' => $temporaryExclusionCount,
            'definitive_exclusion_count' => $definitiveExclusionCount,
            'exclusions_count' => $temporaryExclusionCount + $definitiveExclusionCount,
            'reprimands_count' => $warningCount + $reprimandCount,
            'compensations_count' => $records->filter(fn (DisciplinaryRecord $r) => $r->hasFinancialCompensation())->count(),
            'total_restitution_amount' => $totalCompensationAmount,
            'total_compensation_amount' => $totalCompensationAmount,
            'guardian_notified_rate' => $notificationRate,
            'by_degree' => $byDegree,
            'by_sanction' => $bySanction,
        ];
    }

    /**
     * Assemble data for the official hearing summons letter.
     *
     * @return array<string, mixed>
     */
    public function assembleSummonsLetter(DisciplinaryRecord $record): array
    {
        $school = SchoolSetting::current();

        return [
            'record' => $record,
            'student' => $record->student,
            'school' => $school,
            'academicYear' => $record->academic_year,
            'classroom' => $record->student?->group?->classroom,
            'guardian' => $record->student?->primaryGuardian ?? $record->student?->guardians?->first(),
            'isRtl' => app()->getLocale() === 'ar',
        ];
    }

    /**
     * Assemble data for the official Disciplinary Council session minutes (PV).
     *
     * @return array<string, mixed>
     */
    public function assembleCouncilMinutes(DisciplinaryRecord $record): array
    {
        $school = SchoolSetting::current();
        $defaultAttendees = [
            'مدير المؤسسة — رئيساً',
            'المستشار الرئيسي للتربية',
            'ناظر الدروس',
            'أستاذ(ة) ممثل عن مجلس القسم',
            'مندوب جمعية أولياء التلاميذ',
        ];

        return [
            'record' => $record,
            'student' => $record->student,
            'school' => $school,
            'academicYear' => $record->academic_year,
            'classroom' => $record->student?->group?->classroom,
            'guardian' => $record->student?->primaryGuardian ?? $record->student?->guardians?->first(),
            'president' => $record->president ?? auth()->user(),
            'attendees' => $record->attendees ?: $defaultAttendees,
            'isRtl' => app()->getLocale() === 'ar',
        ];
    }

    /**
     * Assemble data for formal sanction notification to legal guardian.
     *
     * @return array<string, mixed>
     */
    public function assembleSanctionNotification(DisciplinaryRecord $record): array
    {
        $school = SchoolSetting::current();

        return [
            'record' => $record,
            'student' => $record->student,
            'school' => $school,
            'academicYear' => $record->academic_year,
            'classroom' => $record->student?->group?->classroom,
            'guardian' => $record->student?->primaryGuardian ?? $record->student?->guardians?->first(),
            'isRtl' => app()->getLocale() === 'ar',
        ];
    }

    /**
     * Assemble data for the annual disciplinary register ledger.
     *
     * @return array<string, mixed>
     */
    public function assembleAnnualDisciplinaryLedger(?string $academicYear = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);
        $records = DisciplinaryRecord::forAcademicYear($year)
            ->with(['student.group.classroom', 'reportedBy', 'president'])
            ->orderBy('session_date')
            ->get();

        $stats = $this->getDisciplinaryKpis($year);
        $school = SchoolSetting::current();

        return [
            'records' => $records,
            'stats' => $stats,
            'school' => $school,
            'academicYear' => $year,
            'isRtl' => app()->getLocale() === 'ar',
        ];
    }

    /**
     * Generate PDF for official disciplinary summons using Browsershot.
     */
    public function generateSummonsPdf(DisciplinaryRecord $record): string
    {
        $data = $this->assembleSummonsLetter($record);
        $html = view('print.disciplinary-summons', array_merge($data, ['inlineCss' => true]))->render();

        return BrowsershotFactory::html($html)
            ->portrait()
            ->margins(4, 8, 4, 8)
            ->pdf();
    }

    /**
     * Generate PDF for official council minutes using Browsershot.
     */
    public function generateCouncilMinutesPdf(DisciplinaryRecord $record): string
    {
        $data = $this->assembleCouncilMinutes($record);
        $html = view('print.disciplinary-council-minutes', array_merge($data, ['inlineCss' => true]))->render();

        return BrowsershotFactory::html($html)
            ->portrait()
            ->margins(4, 8, 4, 8)
            ->pdf();
    }

    /**
     * Generate PDF for formal sanction notification using Browsershot.
     */
    public function generateSanctionNotificationPdf(DisciplinaryRecord $record): string
    {
        $data = $this->assembleSanctionNotification($record);
        $html = view('print.disciplinary-sanction-notification', array_merge($data, ['inlineCss' => true]))->render();

        return BrowsershotFactory::html($html)
            ->portrait()
            ->margins(6, 10, 6, 10)
            ->pdf();
    }

    /**
     * Generate PDF for annual disciplinary ledger using Browsershot.
     */
    public function generateAnnualDisciplinaryLedgerPdf(?string $academicYear = null): string
    {
        $data = $this->assembleAnnualDisciplinaryLedger($academicYear);
        $html = view('print.annual-disciplinary-ledger', array_merge($data, ['inlineCss' => true]))->render();

        return BrowsershotFactory::html($html)
            ->landscape()
            ->margins(6, 8, 6, 8)
            ->pdf();
    }
}
