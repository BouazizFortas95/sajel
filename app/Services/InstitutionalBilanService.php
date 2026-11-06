<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\InstitutionalReportStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\CounselingSession;
use App\Models\DailyLog;
use App\Models\DisciplinaryRecord;
use App\Models\Level;
use App\Models\QuarterlyInstitutionalReport;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAbsence;
use App\Models\User;
use App\Services\Concerns\ConfiguresBrowsershot;
use App\Support\BrowsershotFactory;

class InstitutionalBilanService
{
    use ConfiguresBrowsershot;

    /**
     * Resolve academic year, defaulting to institutional settings or current calendar year.
     */
    public function resolveAcademicYear(?string $year = null): string
    {
        if (filled($year)) {
            return $year;
        }

        return SchoolSetting::currentAcademicYear();
    }

    /**
     * Resolve date boundaries for Algerian academic trimesters.
     *
     * @return array{from: string, to: string, name: string}
     */
    public function getTrimesterPeriod(int $trimester = 1, ?int $year = null): array
    {
        $year = $year ?? (int) now()->format('Y');
        $currentMonth = (int) now()->format('m');
        $academicStartYear = $currentMonth >= 9 ? $year : $year - 1;

        return match ($trimester) {
            2 => [
                'from' => sprintf('%d-01-01', $academicStartYear + 1),
                'to' => sprintf('%d-03-31', $academicStartYear + 1),
                'name' => __('الفصل الثاني'),
            ],
            3 => [
                'from' => sprintf('%d-04-01', $academicStartYear + 1),
                'to' => sprintf('%d-06-30', $academicStartYear + 1),
                'name' => __('الفصل الثالث'),
            ],
            default => [
                'from' => sprintf('%d-09-01', $academicStartYear),
                'to' => sprintf('%d-12-31', $academicStartYear),
                'name' => __('الفصل الأول'),
            ],
        };
    }

    /**
     * Compute comprehensive live metrics from across all Sajel domain tables.
     *
     * @return array<string, mixed>
     */
    public function computeLiveMetrics(?string $academicYear = null, int $trimester = 1): array
    {
        $academicYear = $this->resolveAcademicYear($academicYear);
        $period = $this->getTrimesterPeriod($trimester);

        // 1. Performance & Academic Grades
        $performance = $this->computePerformanceMetrics($trimester, $period);

        // 2. Attendance & Sessions Volume
        $attendance = $this->computeAttendanceMetrics($period);

        // 3. Discipline & Psycho-pedagogical Counseling
        $climate = $this->computeClimateMetrics($period);

        return [
            'academic_year' => $academicYear,
            'trimester' => $trimester,
            'trimester_name' => $period['name'],
            'period_from' => $period['from'],
            'period_to' => $period['to'],
            ...$performance,
            ...$attendance,
            ...$climate,
        ];
    }

    /**
     * Compute academic success rates, honor distributions, and level/subject breakdowns.
     */
    private function computePerformanceMetrics(int $trimester, array $period): array
    {
        // Fetch all assessments in this trimester
        $assessments = Assessment::query()
            ->where(function ($q) use ($period) {
                $q->whereBetween('assessment_date', [$period['from'], $period['to']])
                    ->orWhereNull('assessment_date');
            })
            ->with(['grades.student.classroom.level', 'subject'])
            ->get();

        // Calculate student averages across subjects
        $studentGrades = [];
        $subjectAverages = [];
        $levelStats = [];

        // Pre-populate levels
        $levels = Level::query()->with('classrooms')->orderBy('grade_number')->get();
        foreach ($levels as $lvl) {
            $code = $lvl->code ?: ($lvl->grade_number.'AM');
            $levelStats[$code] = [
                'level_name' => $lvl->name ?: $code,
                'evaluated' => 0,
                'passed' => 0,
                'success_rate' => 0.0,
                'sum_averages' => 0.0,
                'average' => 0.0,
                'highest_grade' => 0.0,
                'lowest_grade' => 20.0,
            ];
        }

        foreach ($assessments as $assessment) {
            $subjName = $assessment->subject?->name ?? __('مادة عامة');
            if (! isset($subjectAverages[$subjName])) {
                $subjectAverages[$subjName] = ['total_score' => 0, 'count' => 0, 'passed_count' => 0];
            }

            foreach ($assessment->grades as $grade) {
                if ($grade->score === null) {
                    continue;
                }

                $score = (float) $grade->score;
                $studentId = $grade->student_id;

                if (! isset($studentGrades[$studentId])) {
                    $studentGrades[$studentId] = [
                        'scores' => [],
                        'level_code' => $grade->student?->classroom?->level?->code ?? ($grade->student?->classroom?->level?->grade_number ? $grade->student->classroom->level->grade_number.'AM' : '1AM'),
                    ];
                }

                $studentGrades[$studentId]['scores'][] = $score;

                $subjectAverages[$subjName]['total_score'] += $score;
                $subjectAverages[$subjName]['count']++;
                if ($score >= 10.0) {
                    $subjectAverages[$subjName]['passed_count']++;
                }
            }
        }

        $evaluated = count($studentGrades);
        $passed = 0;
        $excellence = 0;
        $encouragement = 0;
        $honor = 0;
        $passing = 0;
        $failing = 0;

        foreach ($studentGrades as $data) {
            $avg = count($data['scores']) > 0 ? array_sum($data['scores']) / count($data['scores']) : 0.0;
            $lvlCode = $data['level_code'];

            if (isset($levelStats[$lvlCode])) {
                $levelStats[$lvlCode]['evaluated']++;
                $levelStats[$lvlCode]['sum_averages'] += $avg;
                if ($avg >= 10.0) {
                    $levelStats[$lvlCode]['passed']++;
                }
                $levelStats[$lvlCode]['highest_grade'] = max($levelStats[$lvlCode]['highest_grade'], $avg);
                $levelStats[$lvlCode]['lowest_grade'] = min($levelStats[$lvlCode]['lowest_grade'], $avg);
            }

            if ($avg >= 10.0) {
                $passed++;
            }

            if ($avg >= 16.0) {
                $excellence++;
            } elseif ($avg >= 14.0) {
                $encouragement++;
            } elseif ($avg >= 12.0) {
                $honor++;
            } elseif ($avg >= 10.0) {
                $passing++;
            } else {
                $failing++;
            }
        }

        // Finalize level stats
        foreach ($levelStats as $code => &$lvlData) {
            if ($lvlData['evaluated'] > 0) {
                $lvlData['success_rate'] = round(($lvlData['passed'] / $lvlData['evaluated']) * 100, 2);
                $lvlData['average'] = round($lvlData['sum_averages'] / $lvlData['evaluated'], 2);
            } else {
                $lvlData['lowest_grade'] = 0.0;
            }
            unset($lvlData['sum_averages']);
        }
        unset($lvlData);

        // Finalize subject breakdown
        $subjectBreakdown = [];
        foreach ($subjectAverages as $subj => $sData) {
            if ($sData['count'] > 0) {
                $subjectBreakdown[] = [
                    'subject' => $subj,
                    'average' => round($sData['total_score'] / $sData['count'], 2),
                    'passing_rate' => round(($sData['passed_count'] / $sData['count']) * 100, 2),
                ];
            }
        }

        // If no grades yet in the system, provide safe population fallback
        if ($evaluated === 0) {
            $totalStudents = Student::query()->count();
            if ($totalStudents > 0) {
                $evaluated = $totalStudents;
            }
        }

        $generalSuccessRate = $evaluated > 0 ? round(($passed / $evaluated) * 100, 2) : 0.0;

        return [
            'general_success_rate' => $generalSuccessRate,
            'evaluated_students_count' => $evaluated,
            'passed_students_count' => $passed,
            'excellence_count' => $excellence,
            'encouragement_count' => $encouragement,
            'honor_count' => $honor,
            'passing_count' => $passing,
            'failing_count' => $failing,
            'level_breakdown' => $levelStats,
            'subject_breakdown' => $subjectBreakdown,
        ];
    }

    /**
     * Compute sessions volume, teacher absences, and student absences.
     */
    private function computeAttendanceMetrics(array $period): array
    {
        $completedSessions = DailyLog::query()
            ->where('status', DailyLogStatus::Completed)
            ->whereBetween('log_date', [$period['from'], $period['to']])
            ->count();

        $totalDueSessions = DailyLog::query()
            ->whereBetween('log_date', [$period['from'], $period['to']])
            ->count();

        if ($totalDueSessions < $completedSessions) {
            $totalDueSessions = $completedSessions;
        }

        // Teacher absences in trimester
        $teacherAbsenceHours = (int) TeacherAbsence::query()
            ->whereBetween('start_date', [$period['from'], $period['to']])
            ->sum('duration_days') * 6; // Average 6 pedagogical hours per day

        // If 0, check hours count directly
        if ($teacherAbsenceHours === 0) {
            $teacherAbsenceHours = (int) TeacherAbsence::query()
                ->whereBetween('start_date', [$period['from'], $period['to']])
                ->count() * 4;
        }

        // Substitute hours covered
        $teacherSubstitutionHours = (int) DailyLog::query()
            ->where('status', DailyLogStatus::Completed)
            ->whereBetween('log_date', [$period['from'], $period['to']])
            ->whereNotNull('substitute_teacher_id')
            ->count();

        // Student absences
        $studentAbsenceRecords = Attendance::query()
            ->where('status', AttendanceStatus::Absent)
            ->whereHas('dailyLog', function ($q) use ($period) {
                $q->whereBetween('log_date', [$period['from'], $period['to']]);
            })
            ->count();

        return [
            'total_due_sessions' => max($totalDueSessions, $completedSessions),
            'completed_sessions' => $completedSessions,
            'teacher_absence_hours' => $teacherAbsenceHours,
            'teacher_substitution_hours' => $teacherSubstitutionHours,
            'student_absence_records' => $studentAbsenceRecords,
        ];
    }

    /**
     * Compute school climate, disciplinary records, and guidance counseling metrics.
     */
    private function computeClimateMetrics(array $period): array
    {
        $councilsCount = DisciplinaryRecord::query()
            ->whereBetween('incident_date', [$period['from'], $period['to']])
            ->count();

        $counselingCount = CounselingSession::query()
            ->whereBetween('session_date', [$period['from'], $period['to']])
            ->count();

        return [
            'disciplinary_councils_count' => $councilsCount,
            'counseling_sessions_count' => $counselingCount,
        ];
    }

    /**
     * Find existing report or create a new Draft with current live metrics.
     */
    public function getOrCreateReport(?string $academicYear = null, int $trimester = 1): QuarterlyInstitutionalReport
    {
        $academicYear = $this->resolveAcademicYear($academicYear);

        $report = QuarterlyInstitutionalReport::query()
            ->where('academic_year', $academicYear)
            ->where('trimester', $trimester)
            ->first();

        if ($report) {
            return $report;
        }

        $metrics = $this->computeLiveMetrics($academicYear, $trimester);

        return QuarterlyInstitutionalReport::create([
            'academic_year' => $academicYear,
            'trimester' => $trimester,
            'status' => InstitutionalReportStatus::Draft,
            'general_success_rate' => $metrics['general_success_rate'],
            'evaluated_students_count' => $metrics['evaluated_students_count'],
            'passed_students_count' => $metrics['passed_students_count'],
            'excellence_count' => $metrics['excellence_count'],
            'encouragement_count' => $metrics['encouragement_count'],
            'honor_count' => $metrics['honor_count'],
            'passing_count' => $metrics['passing_count'],
            'failing_count' => $metrics['failing_count'],
            'total_due_sessions' => $metrics['total_due_sessions'],
            'completed_sessions' => $metrics['completed_sessions'],
            'teacher_absence_hours' => $metrics['teacher_absence_hours'],
            'teacher_substitution_hours' => $metrics['teacher_substitution_hours'],
            'student_absence_records' => $metrics['student_absence_records'],
            'disciplinary_councils_count' => $metrics['disciplinary_councils_count'],
            'counseling_sessions_count' => $metrics['counseling_sessions_count'],
            'level_breakdown' => $metrics['level_breakdown'],
            'subject_breakdown' => $metrics['subject_breakdown'],
            'executive_summary' => null,
            'pedagogical_recommendations' => null,
        ]);
    }

    /**
     * Refresh draft report with fresh real-time metrics.
     */
    public function syncLiveMetrics(QuarterlyInstitutionalReport $report): QuarterlyInstitutionalReport
    {
        if ($report->isValidated()) {
            return $report;
        }

        $metrics = $this->computeLiveMetrics($report->academic_year, $report->trimester);

        $report->update([
            'general_success_rate' => $metrics['general_success_rate'],
            'evaluated_students_count' => $metrics['evaluated_students_count'],
            'passed_students_count' => $metrics['passed_students_count'],
            'excellence_count' => $metrics['excellence_count'],
            'encouragement_count' => $metrics['encouragement_count'],
            'honor_count' => $metrics['honor_count'],
            'passing_count' => $metrics['passing_count'],
            'failing_count' => $metrics['failing_count'],
            'total_due_sessions' => $metrics['total_due_sessions'],
            'completed_sessions' => $metrics['completed_sessions'],
            'teacher_absence_hours' => $metrics['teacher_absence_hours'],
            'teacher_substitution_hours' => $metrics['teacher_substitution_hours'],
            'student_absence_records' => $metrics['student_absence_records'],
            'disciplinary_councils_count' => $metrics['disciplinary_councils_count'],
            'counseling_sessions_count' => $metrics['counseling_sessions_count'],
            'level_breakdown' => $metrics['level_breakdown'],
            'subject_breakdown' => $metrics['subject_breakdown'],
        ]);

        return $report->fresh();
    }

    /**
     * Validate and freeze the report as an official institutional document.
     */
    public function validateReport(QuarterlyInstitutionalReport $report, User $admin): QuarterlyInstitutionalReport
    {
        $report->update([
            'status' => InstitutionalReportStatus::Validated,
            'validated_by' => $admin->id,
            'validated_at' => now(),
        ]);

        return $report->fresh();
    }

    /**
     * Reopen a validated report to draft status.
     */
    public function reopenReport(QuarterlyInstitutionalReport $report): QuarterlyInstitutionalReport
    {
        $report->update([
            'status' => InstitutionalReportStatus::Draft,
            'validated_at' => null,
        ]);

        return $report->fresh();
    }

    /**
     * Generate A4 landscape official PDF using BrowsershotFactory.
     */
    public function generateBilanPdf(QuarterlyInstitutionalReport $report): string
    {
        $schoolSetting = SchoolSetting::first();
        $html = view('print.annual-institutional-bilan', [
            'report' => $report,
            'schoolSetting' => $schoolSetting,
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->landscape()
            ->margins(8, 8, 8, 8)
            ->pdf();
    }
}
