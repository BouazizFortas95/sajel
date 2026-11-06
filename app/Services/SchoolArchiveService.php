<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Models\Classroom;
use App\Models\CurriculumResource;
use App\Models\DailyLog;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\Concerns\ConfiguresBrowsershot;
use App\Support\BrowsershotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;

class SchoolArchiveService
{
    use ConfiguresBrowsershot;
    /**
     * Resolve date boundaries for Algerian academic trimesters.
     *
     * @return array{from: ?string, to: ?string, name: string}
     */
    public function getTrimesterPeriod(?int $trimester = null, ?int $year = null): array
    {
        if ($trimester === null || $trimester === 0) {
            return [
                'from' => null,
                'to' => null,
                'name' => __('الموسم الدراسي كاملاً'),
            ];
        }

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
     * Assemble full annual digital register book for a classroom.
     *
     * @return array<string, mixed>
     */
    public function assembleAnnualRegisterBook(
        Classroom $classroom,
        ?int $subjectId = null,
        ?int $trimester = null,
        ?int $teacherId = null
    ): array {
        $classroom->loadMissing(['level.educationPhase', 'groups']);
        $period = $this->getTrimesterPeriod($trimester);

        // 1. Fetch completed daily logs scoped to the classroom and filters
        $logsQuery = DailyLog::query()
            ->with([
                'schedule.user',
                'schedule.subject',
                'schedule.classroom',
                'schedule.group',
                'curriculumResource.sequence.domain',
                'reviewer',
                'attendances',
            ])
            ->where('status', DailyLogStatus::Completed->value)
            ->whereHas('schedule', function (Builder $query) use ($classroom): void {
                $query->where('classroom_id', $classroom->id)
                    ->orWhereHas('group', fn (Builder $gq): Builder => $gq->where('classroom_id', $classroom->id));
            })
            ->when($subjectId, fn (Builder $q): Builder => $q->whereHas('schedule', fn ($sq) => $sq->where('subject_id', $subjectId)))
            ->when($teacherId, function (Builder $q) use ($teacherId): void {
                $q->where(function (Builder $subQ) use ($teacherId): void {
                    $subQ->whereHas('schedule', fn ($sq) => $sq->where('user_id', $teacherId))
                        ->orWhere('substitute_teacher_id', $teacherId);
                });
            })
            ->when($period['from'], fn (Builder $q) => $q->whereDate('date', '>=', $period['from']))
            ->when($period['to'], fn (Builder $q) => $q->whereDate('date', '<=', $period['to']))
            ->orderBy('date')
            ->orderBy('id');

        $logs = $logsQuery->get();

        // 2. Compute Curriculum Progress Index
        $curriculumIndex = $this->computeCurriculumIndex($logs, $classroom, $subjectId);

        // 3. Group logs chronologically: Trimester -> Month -> Logs
        $groupedLogs = $this->groupLogsChronologically($logs);

        // 4. Extract Administrative & Inspection Visas
        $visas = $this->extractAdministrativeVisas($logs);

        // 5. Compute Annual Statistical Balance Sheet
        $stats = $this->computeAnnualStatistics($logs, $classroom, $subjectId);

        $schoolSetting = SchoolSetting::current();
        $academicYear = $classroom->academic_year ?? $schoolSetting?->academic_year ?? '2026/2027';
        $targetSubject = $subjectId ? Subject::find($subjectId) : null;
        $targetTeacher = $teacherId ? User::find($teacherId) : null;

        $archivalCode = sprintf(
            'ARCH-%s-%s-%s',
            str_replace(['/', ' '], '-', $academicYear),
            $classroom->id,
            $targetSubject ? $targetSubject->id : 'ALL'
        );

        return [
            'classroom' => $classroom,
            'subject' => $targetSubject,
            'teacher' => $targetTeacher,
            'trimester' => $trimester,
            'period' => $period,
            'logs' => $logs,
            'curriculumIndex' => $curriculumIndex,
            'groupedLogs' => $groupedLogs,
            'visas' => $visas,
            'stats' => $stats,
            'schoolSetting' => $schoolSetting,
            'academicYear' => $academicYear,
            'archivalCode' => $archivalCode,
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Compute curriculum domains, sequences, and syllabus completion.
     *
     * @param  Collection<int, DailyLog>  $logs
     * @return array<string, mixed>
     */
    public function computeCurriculumIndex(Collection $logs, Classroom $classroom, ?int $subjectId = null): array
    {
        $mappedLogs = $logs->filter(fn (DailyLog $log): bool => $log->curriculumResource !== null);

        // Group taught resources by domain and sequence
        $domains = [];
        $distinctResourceIds = [];

        foreach ($mappedLogs as $log) {
            $resource = $log->curriculumResource;
            if (! $resource) {
                continue;
            }

            $distinctResourceIds[$resource->id] = true;
            $sequence = $resource->sequence;
            $domain = $sequence?->domain;

            if (! $domain) {
                continue;
            }

            $domainId = $domain->id;
            if (! isset($domains[$domainId])) {
                $domains[$domainId] = [
                    'domain' => $domain,
                    'name' => $domain->name,
                    'sequences' => [],
                    'resources_count' => 0,
                ];
            }

            $sequenceId = $sequence->id;
            if (! isset($domains[$domainId]['sequences'][$sequenceId])) {
                $domains[$domainId]['sequences'][$sequenceId] = [
                    'sequence' => $sequence,
                    'name' => $sequence->name,
                    'resources' => [],
                ];
            }

            if (! isset($domains[$domainId]['sequences'][$sequenceId]['resources'][$resource->id])) {
                $domains[$domainId]['sequences'][$sequenceId]['resources'][$resource->id] = [
                    'resource' => $resource,
                    'name' => $resource->name,
                    'first_taught_at' => $log->date,
                ];
                $domains[$domainId]['resources_count']++;
            }
        }

        // Total official resources available in the curriculum for this subject & level
        $totalOfficialResources = 0;
        $completionRate = null;

        if ($subjectId && $classroom->level_id) {
            $totalOfficialResources = CurriculumResource::query()
                ->whereHas('sequence.domain', function (Builder $q) use ($subjectId, $classroom): void {
                    $q->where('subject_id', $subjectId)
                        ->where('level_id', $classroom->level_id);
                })
                ->count();

            if ($totalOfficialResources > 0) {
                $taughtCount = count($distinctResourceIds);
                $completionRate = min(100.0, round(($taughtCount / $totalOfficialResources) * 100, 1));
            }
        }

        return [
            'domains' => array_values($domains),
            'taught_resources_count' => count($distinctResourceIds),
            'total_official_resources' => $totalOfficialResources,
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * Group daily logs chronologically by trimester and month.
     *
     * @param  Collection<int, DailyLog>  $logs
     * @return array<string, array<string, Collection<int, DailyLog>>>
     */
    public function groupLogsChronologically(Collection $logs): array
    {
        $grouped = [];

        foreach ($logs as $log) {
            $date = $log->date;
            if (! $date) {
                continue;
            }

            $month = (int) $date->format('m');

            // Determine trimester
            $trimesterKey = match (true) {
                $month >= 9 || $month <= 12 => __('الفصل الأول (سبتمبر - ديسمبر)'),
                $month >= 1 && $month <= 3 => __('الفصل الثاني (جانفي - مارس)'),
                default => __('الفصل الثالث (أفريل - جوان)'),
            };

            $monthKey = $date->translatedFormat('F Y');

            if (! isset($grouped[$trimesterKey])) {
                $grouped[$trimesterKey] = [];
            }

            if (! isset($grouped[$trimesterKey][$monthKey])) {
                $grouped[$trimesterKey][$monthKey] = collect();
            }

            $grouped[$trimesterKey][$monthKey]->push($log);
        }

        return $grouped;
    }

    /**
     * Extract all administrative visas stamped across daily logs.
     *
     * @param  Collection<int, DailyLog>  $logs
     * @return Collection<int, array<string, mixed>>
     */
    public function extractAdministrativeVisas(Collection $logs): Collection
    {
        return $logs->filter(fn (DailyLog $log): bool => $log->reviewed_at !== null)
            ->map(function (DailyLog $log): array {
                return [
                    'log' => $log,
                    'date' => $log->date,
                    'reviewed_at' => $log->reviewed_at,
                    'reviewer' => $log->reviewer,
                    'reviewer_name' => $log->reviewer?->name ?? __('الإدارة التربوية'),
                    'notes' => $log->admin_notes,
                    'lesson_title' => $log->title,
                    'subject' => $log->schedule?->subject?->name,
                    'teacher' => $log->schedule?->user?->name,
                ];
            })
            ->values();
    }

    /**
     * Compute annual statistical balance sheet.
     *
     * @param  Collection<int, DailyLog>  $logs
     * @return array<string, mixed>
     */
    public function computeAnnualStatistics(Collection $logs, Classroom $classroom, ?int $subjectId = null): array
    {
        $totalSessions = $logs->count();

        // Instructional hours calculation
        $totalMinutes = 0;
        foreach ($logs as $log) {
            $schedule = $log->schedule;
            if ($schedule && $schedule->start_time && $schedule->end_time) {
                $totalMinutes += $schedule->start_time->diffInMinutes($schedule->end_time);
            } else {
                $totalMinutes += 60; // 1 hour standard slot fallback
            }
        }
        $totalHours = round($totalMinutes / 60, 1);

        // Homework and notes counts
        $homeworkCount = $logs->filter(fn (DailyLog $log): bool => filled($log->homework))->count();
        $observationsCount = $logs->filter(fn (DailyLog $log): bool => filled($log->observations))->count();

        // Visas count
        $visasCount = $logs->filter(fn (DailyLog $log): bool => $log->reviewed_at !== null)->count();

        // Attendances rollup
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalLate = 0;

        foreach ($logs as $log) {
            foreach ($log->attendances as $att) {
                match ($att->status) {
                    AttendanceStatus::Present => $totalPresent++,
                    AttendanceStatus::Absent => $totalAbsent++,
                    AttendanceStatus::Late => $totalLate++,
                    default => null,
                };
            }
        }

        $allAttendanceMarks = $totalPresent + $totalAbsent + $totalLate;
        $presenceRate = $allAttendanceMarks > 0
            ? round((($totalPresent + $totalLate) / $allAttendanceMarks) * 100, 1)
            : 100.0;

        // Distinct subjects and teachers
        $distinctSubjects = $logs->pluck('schedule.subject.name')->filter()->unique()->values();
        $distinctTeachers = $logs->pluck('schedule.user.name')->filter()->unique()->values();

        return [
            'total_sessions' => $totalSessions,
            'total_hours' => $totalHours,
            'homework_count' => $homeworkCount,
            'observations_count' => $observationsCount,
            'visas_count' => $visasCount,
            'total_present' => $totalPresent,
            'total_absent' => $totalAbsent,
            'total_late' => $totalLate,
            'presence_rate' => $presenceRate,
            'distinct_subjects' => $distinctSubjects,
            'distinct_teachers' => $distinctTeachers,
            'dates_span' => [
                'first' => $logs->first()?->date,
                'last' => $logs->last()?->date,
            ],
        ];
    }

    /**
     * Render the Annual Register Book HTML.
     */
    public function renderAnnualBookHtml(
        Classroom $classroom,
        ?int $subjectId = null,
        ?int $trimester = null,
        ?int $teacherId = null
    ): string {
        $data = $this->assembleAnnualRegisterBook($classroom, $subjectId, $trimester, $teacherId);

        return view('print.annual-register-book', $data)->render();
    }

    /**
     * Generate the Annual Register Book PDF using Browsershot.
     */
    public function generateAnnualBookPdf(
        Classroom $classroom,
        ?int $subjectId = null,
        ?int $trimester = null,
        ?int $teacherId = null
    ): string {
        $html = $this->renderAnnualBookHtml($classroom, $subjectId, $trimester, $teacherId);
        $css = $this->compiledCss();

        if ($css !== null) {
            $html = $this->inlineCss($html, $css);
        }

        return $this->browsershot($html)->pdf();
    }

    protected function browsershot(string $html): Browsershot
    {
        return BrowsershotFactory::html($html)
            ->portrait()
            ->timeout(240);
    }

    private function inlineCss(string $html, string $css): string
    {
        $baseUrl = e(rtrim((string) config('app.url'), '/').'/');

        return str_replace(
            '</head>',
            '<base href="'.$baseUrl.'"><style>'.str_replace('</style', '<\/style', $css).'</style></head>',
            $html,
        );
    }

    private function compiledCss(): ?string
    {
        try {
            return Vite::content('resources/css/app.css');
        } catch (ViteException) {
            return null;
        }
    }
}
