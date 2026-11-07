<?php

namespace App\Services;

use App\Enums\ClassCouncilSessionStatus;
use App\Enums\CouncilHonors;
use App\Enums\CouncilSanctions;
use App\Enums\DailyLogStatus;
use App\Enums\EndOfYearDecision;
use App\Enums\PacingStatus;
use App\Models\ClassCouncilDecision;
use App\Models\ClassCouncilSession;
use App\Models\Classroom;
use App\Models\CurriculumPacingAudit;
use App\Models\CurriculumResource;
use App\Models\DailyLog;
use App\Models\Grade;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectCoordinationProtocol;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ClassCouncilDeliberationService
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
     * Find or create a class council session for a classroom and trimester.
     */
    public function getOrCreateSession(
        Classroom $classroom,
        int $trimester = 1,
        ?string $academicYear = null,
        ?User $president = null
    ): ClassCouncilSession {
        $year = $this->resolveAcademicYear($academicYear);

        return ClassCouncilSession::firstOrCreate(
            [
                'classroom_id' => $classroom->id,
                'trimester' => $trimester,
                'academic_year' => $year,
            ],
            [
                'session_date' => now(config('app.timezone', 'Africa/Algiers')),
                'president_id' => $president?->id,
                'status' => ClassCouncilSessionStatus::Draft,
            ]
        );
    }

    /**
     * Synchronize and compute student decisions and statistical distributions from grades.
     */
    public function syncDecisionsFromGrades(ClassCouncilSession $session): ClassCouncilSession
    {
        $classroom = $session->classroom;
        $classroom->loadMissing(['groups', 'level.educationPhase']);

        $groupIds = $classroom->groups->pluck('id');
        $students = Student::query()
            ->whereIn('group_id', $groupIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($students->isEmpty()) {
            return $session;
        }

        // Fetch grades for all students in this classroom
        $studentIds = $students->pluck('id');
        $grades = Grade::query()
            ->whereIn('student_id', $studentIds)
            ->with('assessment.schedule')
            ->get()
            ->groupBy('student_id');

        // Calculate student averages
        $studentAverages = $students->map(function (Student $student) use ($grades) {
            $studentGrades = $grades->get($student->id, collect())
                ->filter(fn (Grade $g) => $g->score !== null);

            $average = $studentGrades->isNotEmpty()
                ? round($studentGrades->avg('score'), 2)
                : null;

            return [
                'student' => $student,
                'average' => $average,
            ];
        });

        // Sort by average descending to compute ranks (nulls at end)
        $ranked = $studentAverages->sort(function ($a, $b) {
            if ($a['average'] === null && $b['average'] === null) {
                return 0;
            }
            if ($a['average'] === null) {
                return 1;
            }
            if ($b['average'] === null) {
                return -1;
            }

            return $b['average'] <=> $a['average'];
        })->values();

        $validAverages = $ranked->pluck('average')->filter(fn ($v) => $v !== null);
        $totalValid = $validAverages->count();

        $classAverage = $totalValid > 0 ? round($validAverages->avg(), 2) : null;
        $highestAverage = $totalValid > 0 ? $validAverages->max() : null;
        $lowestAverage = $totalValid > 0 ? $validAverages->min() : null;
        $successCount = $validAverages->filter(fn ($v) => $v >= 10.0)->count();
        $successRate = $totalValid > 0 ? round(($successCount / $totalValid) * 100, 2) : 0.0;

        DB::transaction(function () use ($session, $ranked, $classAverage, $highestAverage, $lowestAverage, $successRate, $students) {
            foreach ($ranked as $index => $item) {
                $avg = $item['average'];
                $rank = $avg !== null ? ($index + 1) : null;

                $honors = CouncilHonors::fromAverage($avg);
                $sanctions = CouncilSanctions::fromAverage($avg);

                $endOfYear = EndOfYearDecision::None;
                if ($session->trimester === 3 && $avg !== null) {
                    $endOfYear = $avg >= 10.0 ? EndOfYearDecision::Admis : EndOfYearDecision::Redouble;
                }

                ClassCouncilDecision::updateOrCreate(
                    [
                        'class_council_session_id' => $session->id,
                        'student_id' => $item['student']->id,
                    ],
                    [
                        'trimester_average' => $avg,
                        'annual_average' => $session->trimester === 3 ? $avg : null,
                        'rank' => $rank,
                        'honors' => $honors,
                        'sanctions' => $sanctions,
                        'end_of_year_decision' => $endOfYear,
                    ]
                );
            }

            $session->update([
                'class_average' => $classAverage,
                'highest_average' => $highestAverage,
                'lowest_average' => $lowestAverage,
                'success_rate' => $successRate,
                'total_students' => $students->count(),
                'status' => $session->status === ClassCouncilSessionStatus::Draft
                    ? ClassCouncilSessionStatus::Deliberated
                    : $session->status,
            ]);
        });

        return $session->fresh(['decisions.student', 'classroom']);
    }

    /**
     * Officially certify the class council deliberation minutes.
     */
    public function certifySession(ClassCouncilSession $session, ?string $summary = null): ClassCouncilSession
    {
        $session->update([
            'status' => ClassCouncilSessionStatus::Certified,
            'council_decisions_summary' => $summary ?? $session->council_decisions_summary,
        ]);

        return $session;
    }

    /**
     * Record a subject coordination protocol.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordSubjectCoordination(array $data): SubjectCoordinationProtocol
    {
        $data['academic_year'] = $this->resolveAcademicYear($data['academic_year'] ?? null);

        return SubjectCoordinationProtocol::create($data);
    }

    /**
     * Audit curriculum pacing for a classroom and subject.
     */
    public function auditCurriculumPacing(
        Classroom $classroom,
        Subject $subject,
        int $trimester = 1,
        ?string $academicYear = null,
        ?User $auditor = null
    ): CurriculumPacingAudit {
        $year = $this->resolveAcademicYear($academicYear);

        // Count expected curriculum resources for this level and subject
        $expectedCount = CurriculumResource::query()
            ->whereHas('sequence.domain', function (Builder $query) use ($subject, $classroom): void {
                $query->where('subject_id', $subject->id)
                    ->where('level_id', $classroom->level_id);
            })
            ->count();

        // Standard trimester expectation fallback if curriculum resources not fully mapped
        if ($expectedCount === 0) {
            $expectedCount = match ($trimester) {
                1 => 12,
                2 => 24,
                3 => 36,
                default => 12,
            };
        } else {
            // Proportion per trimester
            $expectedCount = match ($trimester) {
                1 => (int) ceil($expectedCount * 0.35),
                2 => (int) ceil($expectedCount * 0.70),
                3 => $expectedCount,
                default => (int) ceil($expectedCount * 0.35),
            };
        }

        // Count completed daily logs for this classroom and subject
        $completedCount = DailyLog::query()
            ->where('status', DailyLogStatus::Completed)
            ->whereHas('schedule', function (Builder $query) use ($classroom, $subject): void {
                $query->where('subject_id', $subject->id)
                    ->where(function (Builder $q) use ($classroom): void {
                        $q->where('classroom_id', $classroom->id)
                            ->orWhereHas('group', fn (Builder $g) => $g->where('classroom_id', $classroom->id));
                    });
            })
            ->count();

        $executionPercentage = $expectedCount > 0
            ? min(100.0, round(($completedCount / $expectedCount) * 100, 2))
            : 0.0;

        $pacingStatus = PacingStatus::fromExecution($expectedCount, $completedCount);

        return CurriculumPacingAudit::updateOrCreate(
            [
                'academic_year' => $year,
                'trimester' => $trimester,
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
            ],
            [
                'audit_date' => now(config('app.timezone', 'Africa/Algiers')),
                'expected_resources_count' => $expectedCount,
                'completed_resources_count' => $completedCount,
                'execution_percentage' => $executionPercentage,
                'pacing_status' => $pacingStatus,
                'audited_by' => $auditor?->id,
            ]
        );
    }

    /**
     * Assemble printable data for the official Class Council Deliberation Minutes (A4 Landscape / Portrait).
     *
     * @return array<string, mixed>
     */
    public function assembleClassCouncilPvData(ClassCouncilSession $session): array
    {
        $session->loadMissing([
            'classroom.level.educationPhase',
            'president',
            'mainTeacher',
            'counselor',
            'supervisor',
            'recordedBy',
            'decisions.student',
        ]);

        $school = SchoolSetting::current();

        $decisions = $session->decisions;
        $honoredStudents = $decisions->filter(fn (ClassCouncilDecision $d) => $d->honors !== CouncilHonors::None);
        $sanctionedStudents = $decisions->filter(fn (ClassCouncilDecision $d) => $d->sanctions !== CouncilSanctions::None);

        return [
            'session' => $session,
            'classroom' => $session->classroom,
            'school' => $school,
            'decisions' => $decisions,
            'honoredStudents' => $honoredStudents,
            'sanctionedStudents' => $sanctionedStudents,
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Subject Coordination Protocol (A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assembleCoordinationPvData(SubjectCoordinationProtocol $protocol): array
    {
        $protocol->loadMissing(['subject', 'coordinator', 'recordedBy']);

        $attendeeUsers = collect();
        if (! empty($protocol->attendees)) {
            $attendeeUsers = User::whereIn('id', $protocol->attendees)->get();
        }

        return [
            'protocol' => $protocol,
            'subject' => $protocol->subject,
            'coordinator' => $protocol->coordinator,
            'attendeeUsers' => $attendeeUsers,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Curriculum Progress and Pacing Audit Sheet (A4 Landscape).
     *
     * @return array<string, mixed>
     */
    public function assemblePacingAuditData(string $academicYear, int $trimester, ?int $levelId = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);

        $query = CurriculumPacingAudit::query()
            ->with(['classroom.level', 'subject', 'teacher', 'auditedBy'])
            ->where('academic_year', $year)
            ->where('trimester', $trimester);

        if ($levelId) {
            $query->whereHas('classroom', fn (Builder $q) => $q->where('level_id', $levelId));
        }

        $audits = $query->orderBy('classroom_id')->get();

        $criticalDelaysCount = $audits->where('pacing_status', PacingStatus::CriticalDelay)->count();
        $minorDelaysCount = $audits->where('pacing_status', PacingStatus::MinorDelay)->count();
        $onTrackCount = $audits->where('pacing_status', PacingStatus::OnTrack)->count();
        $aheadCount = $audits->where('pacing_status', PacingStatus::Ahead)->count();

        return [
            'audits' => $audits,
            'academicYear' => $year,
            'trimester' => $trimester,
            'level' => $levelId ? Level::find($levelId) : null,
            'school' => SchoolSetting::current(),
            'criticalDelaysCount' => $criticalDelaysCount,
            'minorDelaysCount' => $minorDelaysCount,
            'onTrackCount' => $onTrackCount,
            'aheadCount' => $aheadCount,
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Overview KPI stats for the cockpit.
     *
     * @return array<string, mixed>
     */
    public function getOverviewStats(?string $academicYear = null, int $trimester = 1): array
    {
        $year = $this->resolveAcademicYear($academicYear);

        $totalSessions = ClassCouncilSession::where('academic_year', $year)
            ->where('trimester', $trimester)
            ->count();

        $certifiedSessions = ClassCouncilSession::where('academic_year', $year)
            ->where('trimester', $trimester)
            ->where('status', ClassCouncilSessionStatus::Certified)
            ->count();

        $avgSuccessRate = ClassCouncilSession::where('academic_year', $year)
            ->where('trimester', $trimester)
            ->whereNotNull('success_rate')
            ->avg('success_rate') ?? 0.0;

        $totalCoordinations = SubjectCoordinationProtocol::where('academic_year', $year)
            ->where('trimester', $trimester)
            ->count();

        $pacingAudits = CurriculumPacingAudit::where('academic_year', $year)
            ->where('trimester', $trimester)
            ->get();

        $delayCount = $pacingAudits->whereIn('pacing_status', [
            PacingStatus::MinorDelay,
            PacingStatus::CriticalDelay,
        ])->count();

        return [
            'totalSessions' => $totalSessions,
            'certifiedSessions' => $certifiedSessions,
            'avgSuccessRate' => round($avgSuccessRate, 1),
            'totalCoordinations' => $totalCoordinations,
            'totalAudits' => $pacingAudits->count(),
            'delayCount' => $delayCount,
        ];
    }
}
