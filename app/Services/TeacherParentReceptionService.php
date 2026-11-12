<?php

namespace App\Services;

use App\Enums\DayOfWeek;
use App\Models\SchoolSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class TeacherParentReceptionService
{
    /**
     * Get clean Arabic label for a day without diacritics.
     */
    public function dayLabel(string|DayOfWeek $day): string
    {
        $val = $day instanceof DayOfWeek ? $day->value : strtolower(trim((string) $day));
        $labels = [
            'sunday' => 'الأحد',
            'monday' => 'الإثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
        ];

        return $labels[$val] ?? $val;
    }

    /**
     * Standard Algerian school week days (Middle School: Sunday to Thursday).
     *
     * @return list<DayOfWeek>
     */
    public function getActiveWeekDays(): array
    {
        return [
            DayOfWeek::Sunday,
            DayOfWeek::Monday,
            DayOfWeek::Tuesday,
            DayOfWeek::Wednesday,
            DayOfWeek::Thursday,
        ];
    }

    /**
     * Detect all schedule gaps for a teacher, prioritizing intra-day gaps between teaching sessions.
     *
     * @return list<array{
     *     day: string,
     *     day_label: string,
     *     start_time: string,
     *     end_time: string,
     *     duration_minutes: int,
     *     is_between_sessions: bool,
     *     score: int,
     *     label: string
     * }>
     */
    public function detectGapSlots(User $teacher): array
    {
        $schedules = $teacher->schedules()
            ->with(['classroom', 'subject'])
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $byDay = $schedules->groupBy(function ($schedule) {
            return $schedule->day_of_week instanceof DayOfWeek
                ? $schedule->day_of_week->value
                : (string) $schedule->day_of_week;
        });

        $slots = [];

        foreach ($this->getActiveWeekDays() as $dayEnum) {
            $dayKey = $dayEnum->value;
            $daySchedules = $byDay->get($dayKey);

            if (! $daySchedules || $daySchedules->isEmpty()) {
                continue;
            }

            // Sort ascending by start time
            $sorted = $daySchedules->sortBy(function ($s) {
                return $s->start_time instanceof CarbonInterface
                    ? $s->start_time->format('H:i')
                    : substr((string) $s->start_time, 0, 5);
            })->values();

            // 1. Check intra-day gaps between consecutive sessions
            for ($i = 0; $i < $sorted->count() - 1; $i++) {
                $current = $sorted->get($i);
                $next = $sorted->get($i + 1);

                $currentEnd = $current->end_time instanceof CarbonInterface
                    ? $current->end_time->format('H:i')
                    : substr((string) $current->end_time, 0, 5);

                $nextStart = $next->start_time instanceof CarbonInterface
                    ? $next->start_time->format('H:i')
                    : substr((string) $next->start_time, 0, 5);

                $diffMinutes = $this->minutesBetween($currentEnd, $nextStart);

                // Gaps between 40 and 150 minutes (e.g. 1 hour or 2 hours between morning classes)
                if ($diffMinutes >= 40 && $diffMinutes <= 150) {
                    $score = 100;

                    // Prefer morning gaps (between 08:00 and 12:00)
                    $hour = (int) substr($currentEnd, 0, 2);
                    if ($hour >= 8 && $hour <= 11) {
                        $score += 30;
                    }

                    // Standard 60 min gap gets bonus
                    if ($diffMinutes >= 55 && $diffMinutes <= 65) {
                        $score += 20;
                    }

                    $slots[] = [
                        'day' => $dayKey,
                        'day_label' => $this->dayLabel($dayEnum),
                        'start_time' => $currentEnd,
                        'end_time' => $nextStart,
                        'duration_minutes' => $diffMinutes,
                        'is_between_sessions' => true,
                        'score' => $score,
                        'label' => sprintf(
                            '%s: من %s إلى %s (%d دقيقة — فراغ بين حصتين)',
                            $this->dayLabel($dayEnum),
                            $currentEnd,
                            $nextStart,
                            $diffMinutes
                        ),
                    ];
                }
            }

            // 2. Pre-session or post-session available slots on a teaching day
            $firstSession = $sorted->first();
            $lastSession = $sorted->last();

            $firstStart = $firstSession->start_time instanceof CarbonInterface
                ? $firstSession->start_time->format('H:i')
                : substr((string) $firstSession->start_time, 0, 5);

            $lastEnd = $lastSession->end_time instanceof CarbonInterface
                ? $lastSession->end_time->format('H:i')
                : substr((string) $lastSession->end_time, 0, 5);

            // If starts at 09:00 or later, 08:00 to start is an available pre-class slot
            if ($firstStart >= '09:00' && $firstStart <= '10:00') {
                $slots[] = [
                    'day' => $dayKey,
                    'day_label' => $this->dayLabel($dayEnum),
                    'start_time' => '08:00',
                    'end_time' => $firstStart,
                    'duration_minutes' => $this->minutesBetween('08:00', $firstStart),
                    'is_between_sessions' => false,
                    'score' => 40,
                    'label' => sprintf(
                        '%s: من 08:00 إلى %s (قبل بداية الحصص)',
                        $this->dayLabel($dayEnum),
                        $firstStart
                    ),
                ];
            }

            // If finishes morning classes at 11:00, 11:00-12:00 is an available post-class slot
            if ($lastEnd === '11:00') {
                $slots[] = [
                    'day' => $dayKey,
                    'day_label' => $this->dayLabel($dayEnum),
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                    'duration_minutes' => 60,
                    'is_between_sessions' => false,
                    'score' => 50,
                    'label' => sprintf(
                        '%s: من 11:00 إلى 12:00 (بعد نهاية الحصة الصباحية)',
                        $this->dayLabel($dayEnum)
                    ),
                ];
            }
        }

        // Sort descending by score
        usort($slots, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $slots;
    }

    /**
     * Return the best recommended gap slot for a teacher.
     *
     * @return array{
     *     day: string,
     *     day_label: string,
     *     start_time: string,
     *     end_time: string,
     *     duration_minutes: int,
     *     is_between_sessions: bool,
     *     score: int,
     *     label: string
     * }|null
     */
    public function bestGapSlot(User $teacher): ?array
    {
        $gaps = $this->detectGapSlots($teacher);

        return $gaps[0] ?? null;
    }

    /**
     * Automatically assign reception slots to all teachers from their schedule gaps.
     *
     * @param  Collection<int, User>|null  $teachers
     */
    public function autoAssignReceptions(?Collection $teachers = null, bool $overwriteExisting = false): int
    {
        if ($teachers === null) {
            $teachers = User::query()
                ->where('role', 'teacher')
                ->orWhereHas('schedules')
                ->get();
        }

        $assignedCount = 0;

        foreach ($teachers as $teacher) {
            if (! $overwriteExisting && $teacher->hasParentReceptionSlot()) {
                continue;
            }

            $best = $this->bestGapSlot($teacher);

            if ($best) {
                $teacher->update([
                    'parent_reception_day' => $best['day'],
                    'parent_reception_start_time' => $best['start_time'],
                    'parent_reception_end_time' => $best['end_time'],
                    'parent_reception_location' => $teacher->parent_reception_location ?: 'مكتب الاستشارة التربوية',
                    'parent_reception_notes' => $teacher->parent_reception_notes ?: sprintf(
                        'تم الاعتماد آلياً من فترة الفراغ الأسبوعية (%s)',
                        $best['is_between_sessions'] ? 'بين الحصص' : 'فترة محاذية'
                    ),
                ]);

                $assignedCount++;
            }
        }

        return $assignedCount;
    }

    /**
     * Compute the exact datetime of the next parent reception occurrence for a teacher.
     */
    public function calculateNextReceptionDate(User $teacher, ?CarbonInterface $fromDate = null): Carbon
    {
        $from = $fromDate ? Carbon::instance($fromDate) : now();

        $day = $teacher->parent_reception_day;
        $startTime = $teacher->parent_reception_start_time;

        // If not set on model, try finding best gap slot
        if (blank($day) || blank($startTime)) {
            $best = $this->bestGapSlot($teacher);
            if ($best) {
                $day = $best['day'];
                $startTime = $best['start_time'];
            }
        }

        // Default fallback: next Tuesday at 10:00
        if (blank($day) || blank($startTime)) {
            return $from->copy()->addDay()->setTime(10, 0);
        }

        $dayInt = $this->dayOfWeekToCarbonInt($day);
        $timeParts = explode(':', (string) $startTime);
        $hour = (int) ($timeParts[0] ?? 10);
        $minute = (int) ($timeParts[1] ?? 0);

        $candidate = $from->copy();

        if ($candidate->dayOfWeek === $dayInt) {
            $candidate->setTime($hour, $minute, 0);
            if ($candidate->greaterThan($from->copy()->addHours(2))) {
                return $candidate;
            }
        }

        return $candidate->next($dayInt)->setTime($hour, $minute, 0);
    }

    /**
     * Prepare data for the official printable Parent Reception Schedule document.
     *
     * @return array<string, mixed>
     */
    public function viewData(
        bool $autoPrint = true,
        ?string $academicYear = null,
        ?int $teacherId = null,
        ?int $subjectId = null,
    ): array {
        $schoolSetting = SchoolSetting::current();
        $year = $academicYear ?: $schoolSetting->academic_year;

        $teachers = User::query()
            ->where(function ($q): void {
                $q->where('role', 'teacher')
                    ->orWhereHas('schedules');
            })
            ->when($teacherId, fn ($q) => $q->where('id', $teacherId))
            ->when($subjectId, fn ($q) => $q->where('primary_subject_id', $subjectId))
            ->with(['primarySubject', 'schedules.classroom', 'schedules.group.classroom'])
            ->get()
            ->sortBy([
                ['primarySubject.name', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        $totalTeachers = $teachers->count();
        $assignedCount = $teachers->filter(fn ($t) => $t->hasParentReceptionSlot())->count();
        $pendingCount = $totalTeachers - $assignedCount;

        return [
            'schoolSetting' => $schoolSetting,
            'academicYear' => $year,
            'teachers' => $teachers,
            'totalTeachers' => $totalTeachers,
            'assignedCount' => $assignedCount,
            'pendingCount' => $pendingCount,
            'autoPrint' => $autoPrint,
        ];
    }

    /**
     * Map day string or enum to Carbon day of week integer.
     */
    protected function dayOfWeekToCarbonInt(string|DayOfWeek $day): int
    {
        $val = $day instanceof DayOfWeek ? $day->value : strtolower(trim($day));

        return match ($val) {
            'sunday' => CarbonInterface::SUNDAY,
            'monday' => CarbonInterface::MONDAY,
            'tuesday' => CarbonInterface::TUESDAY,
            'wednesday' => CarbonInterface::WEDNESDAY,
            'thursday' => CarbonInterface::THURSDAY,
            'friday' => CarbonInterface::FRIDAY,
            'saturday' => CarbonInterface::SATURDAY,
            default => CarbonInterface::SUNDAY,
        };
    }

    /**
     * Calculate difference in minutes between two H:i time strings.
     */
    protected function minutesBetween(string $start, string $end): int
    {
        $sParts = explode(':', $start);
        $eParts = explode(':', $end);

        $sMinutes = ((int) $sParts[0] * 60) + (int) ($sParts[1] ?? 0);
        $eMinutes = ((int) $eParts[0] * 60) + (int) ($eParts[1] ?? 0);

        return max(0, $eMinutes - $sMinutes);
    }
}
