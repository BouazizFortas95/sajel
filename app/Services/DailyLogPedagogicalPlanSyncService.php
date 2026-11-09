<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Filament\Pages\TeacherReceiptsHub;
use App\Models\DailyLog;
use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class DailyLogPedagogicalPlanSyncService
{
    /**
     * Map of month numbers (1-12) to PedagogicalPlanReceipt month keys.
     *
     * @var array<int, string>
     */
    public const MONTH_KEYS = [
        9 => 'september',
        10 => 'october',
        11 => 'november',
        12 => 'december',
        1 => 'january',
        2 => 'february',
        3 => 'march',
        4 => 'april',
        5 => 'may',
        6 => 'may',
    ];

    /**
     * Human-readable labels.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        'annual_plan' => 'المخطط والتدرج السنوي',
        'september' => 'مخطط شهر سبتمبر',
        'october' => 'مخطط شهر أكتوبر',
        'november' => 'مخطط شهر نوفمبر',
        'december' => 'مخطط شهر ديسمبر',
        'january' => 'مخطط شهر جانفي',
        'february' => 'مخطط شهر فيفري',
        'march' => 'مخطط شهر مارس',
        'april' => 'مخطط شهر أفريل',
        'may' => 'مخطط شهر ماي',
    ];

    /**
     * Required minimum completed lessons in a calendar month to certify the monthly plan milestone.
     */
    public const MONTHLY_LESSON_MILESTONE = 3;

    public function getLabel(string $key): string
    {
        return self::LABELS[$key] ?? $key;
    }

    /**
     * Detect if a DailyLog directly represents an annual or monthly pedagogical plan submission.
     */
    public function detectPlanType(DailyLog $log): ?string
    {
        $text = mb_strtolower(trim(($log->title ?? '').' '.($log->homework ?? '').' '.($log->observations ?? '')));

        if (empty($text)) {
            return null;
        }

        // 1. Annual plan keywords
        if (str_contains($text, 'مخطط سنوي')
            || str_contains($text, 'المخطط السنوي')
            || str_contains($text, 'التدرج السنوي')
            || str_contains($text, 'تدرج سنوي')
            || str_contains($text, 'التوزيع السنوي')
            || str_contains($text, 'توزيع سنوي')
            || str_contains($text, 'plan annuel')
            || str_contains($text, 'progression annuelle')) {
            return 'annual_plan';
        }

        // 2. Explicit monthly plan keywords
        $isPlanContext = str_contains($text, 'مخطط')
            || str_contains($text, 'تدرج')
            || str_contains($text, 'توزيع')
            || str_contains($text, 'تخطيط')
            || str_contains($text, 'شهري')
            || str_contains($text, 'حصيلة');

        if ($isPlanContext) {
            if (str_contains($text, 'سبتمبر') || str_contains($text, 'septembre')) {
                return 'september';
            }
            if (str_contains($text, 'أكتوبر') || str_contains($text, 'اكتوبر') || str_contains($text, 'octobre')) {
                return 'october';
            }
            if (str_contains($text, 'نوفمبر') || str_contains($text, 'novembre')) {
                return 'november';
            }
            if (str_contains($text, 'ديسمبر') || str_contains($text, 'decembre') || str_contains($text, 'décembre')) {
                return 'december';
            }
            if (str_contains($text, 'جانفي') || str_contains($text, 'يناير') || str_contains($text, 'janvier')) {
                return 'january';
            }
            if (str_contains($text, 'فيفري') || str_contains($text, 'فبراير') || str_contains($text, 'fevrier') || str_contains($text, 'février')) {
                return 'february';
            }
            if (str_contains($text, 'مارس') || str_contains($text, 'mars')) {
                return 'march';
            }
            if (str_contains($text, 'أفريل') || str_contains($text, 'افريل') || str_contains($text, 'ابريل') || str_contains($text, 'avril')) {
                return 'april';
            }
            if (str_contains($text, 'ماي') || str_contains($text, 'مايو') || str_contains($text, 'mai')) {
                return 'may';
            }
        }

        return null;
    }

    /**
     * Resolve month key from a date.
     */
    public function monthKeyFromDate(Carbon|string $date): ?string
    {
        $d = $date instanceof Carbon ? $date : Carbon::parse($date);
        $monthNum = (int) $d->format('n');

        return self::MONTH_KEYS[$monthNum] ?? null;
    }

    /**
     * Sync a completed DailyLog with the teacher's PedagogicalPlanReceipt.
     *
     * @return array{type: string, label: string, date: string, was_already_submitted: bool}|null
     */
    public function sync(DailyLog $log, ?string $forcedType = null): ?array
    {
        if (! $log->isCompleted()) {
            return null;
        }

        $teacherId = $log->schedule?->user_id ?? $log->substitute_teacher_id;
        if (! $teacherId) {
            return null;
        }

        $teacher = User::find($teacherId);
        if (! $teacher) {
            return null;
        }

        $detectedType = $forcedType ?: $this->detectPlanType($log);
        $year = SchoolSetting::current()?->academic_year ?? '2026-2027';

        $receipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $teacher->id,
            'academic_year' => $year,
        ]);

        $targetDate = $log->date ? $log->date->format('Y-m-d') : now()->format('Y-m-d');
        $syncedType = null;
        $wasSubmitted = false;

        if ($detectedType === 'annual_plan') {
            $syncedType = 'annual_plan';
            $wasSubmitted = (bool) $receipt->annual_plan_submitted;
            $receipt->annual_plan_submitted = true;
            $receipt->annual_plan_date = $targetDate;
            $receipt->save();
        } elseif ($detectedType && isset(PedagogicalPlanReceipt::MONTHS[$detectedType])) {
            $syncedType = $detectedType;
            $colSub = "{$detectedType}_submitted";
            $colDate = "{$detectedType}_date";
            $wasSubmitted = (bool) $receipt->{$colSub};
            $receipt->{$colSub} = true;
            $receipt->{$colDate} = $targetDate;
            $receipt->save();
        } else {
            // Check monthly lesson milestone
            $logMonthKey = $log->date ? $this->monthKeyFromDate($log->date) : null;
            if ($logMonthKey) {
                $milestoneSynced = $this->checkAndSyncMonthlyMilestone($teacher, $receipt, $logMonthKey, $targetDate);
                if ($milestoneSynced) {
                    return $milestoneSynced;
                }
            }
        }

        if ($syncedType) {
            $label = $this->getLabel($syncedType);

            if (! $wasSubmitted) {
                Notification::make()
                    ->title("تم ربط وتأشير «{$label}» آلياً")
                    ->body("تم رصد إنجاز الحصة في دفتر النصوص وتأشير المخطط البيداغوجي تلقائياً بتاريخ {$targetDate}.")
                    ->icon('heroicon-o-check-badge')
                    ->success()
                    ->actions([
                        Action::make('viewReceipt')
                            ->label('معاينة كشف المخططات')
                            ->url(TeacherReceiptsHub::getUrl()),
                    ])
                    ->sendToDatabase($teacher);
            }

            return [
                'type' => $syncedType,
                'label' => $label,
                'date' => $targetDate,
                'was_already_submitted' => $wasSubmitted,
            ];
        }

        return null;
    }

    /**
     * Check if teacher meets the completed lesson milestone in a month and sync accordingly.
     *
     * @return array{type: string, label: string, date: string, was_already_submitted: bool}|null
     */
    protected function checkAndSyncMonthlyMilestone(
        User $teacher,
        PedagogicalPlanReceipt $receipt,
        string $monthKey,
        string $targetDate
    ): ?array {
        $colSub = "{$monthKey}_submitted";
        $colDate = "{$monthKey}_date";

        if ($receipt->{$colSub}) {
            return null;
        }

        $monthNumber = array_search($monthKey, self::MONTH_KEYS, true);
        if (! $monthNumber) {
            return null;
        }

        // Count completed lessons for this teacher in this calendar month
        $completedCount = DailyLog::query()
            ->forTeacher($teacher)
            ->where('status', DailyLogStatus::Completed->value)
            ->whereMonth('date', $monthNumber)
            ->count();

        if ($completedCount >= self::MONTHLY_LESSON_MILESTONE) {
            $receipt->{$colSub} = true;
            $receipt->{$colDate} = $targetDate;

            // If annual plan wasn't submitted yet and it's September or October, also credit the annual plan
            if (! $receipt->annual_plan_submitted && in_array($monthKey, ['september', 'october'], true)) {
                $receipt->annual_plan_submitted = true;
                $receipt->annual_plan_date = $targetDate;
            }

            $receipt->save();

            $label = $this->getLabel($monthKey);

            Notification::make()
                ->title("تم اعتماد «{$label}» آلياً")
                ->body("بناءً على وتيرة إنجاز الدروس في دفتر النصوص ({$completedCount} حصص منجزة)، تم تأشير المخطط الشهري تلقائياً.")
                ->icon('heroicon-o-calendar-days')
                ->success()
                ->actions([
                    Action::make('viewReceipt')
                        ->label('معاينة كشف المخططات')
                        ->url(TeacherReceiptsHub::getUrl()),
                ])
                ->sendToDatabase($teacher);

            return [
                'type' => $monthKey,
                'label' => $label,
                'date' => $targetDate,
                'was_already_submitted' => false,
            ];
        }

        return null;
    }

    /**
     * Bulk sync all completed daily logs for a teacher or all teachers.
     *
     * @return array<string, int>
     */
    public function bulkSync(?User $teacher = null, ?string $academicYear = null): array
    {
        $year = $academicYear ?: (SchoolSetting::current()?->academic_year ?? '2026-2027');
        $teachers = $teacher ? collect([$teacher]) : User::where('role', 'teacher')->get();

        $syncedMonths = 0;
        $syncedAnnual = 0;

        foreach ($teachers as $t) {
            $receipt = PedagogicalPlanReceipt::firstOrNew([
                'user_id' => $t->id,
                'academic_year' => $year,
            ]);

            // Scan each month
            foreach (self::MONTH_KEYS as $monthNum => $monthKey) {
                $colSub = "{$monthKey}_submitted";
                $colDate = "{$monthKey}_date";

                if (! $receipt->{$colSub}) {
                    $completedLogs = DailyLog::query()
                        ->forTeacher($t)
                        ->where('status', DailyLogStatus::Completed->value)
                        ->whereMonth('date', $monthNum)
                        ->orderBy('date')
                        ->get();

                    if ($completedLogs->count() >= self::MONTHLY_LESSON_MILESTONE) {
                        $receipt->{$colSub} = true;
                        $receipt->{$colDate} = $completedLogs->last()->date?->format('Y-m-d') ?? now()->format('Y-m-d');
                        $syncedMonths++;
                    }
                }
            }

            // Annual plan check
            if (! $receipt->annual_plan_submitted) {
                $earlyLog = DailyLog::query()
                    ->forTeacher($t)
                    ->where('status', DailyLogStatus::Completed->value)
                    ->where(function ($q) {
                        $q->whereMonth('date', 9)->orWhereMonth('date', 10);
                    })
                    ->orderBy('date')
                    ->first();

                if ($earlyLog) {
                    $receipt->annual_plan_submitted = true;
                    $receipt->annual_plan_date = $earlyLog->date?->format('Y-m-d') ?? now()->format('Y-m-d');
                    $syncedAnnual++;
                }
            }

            $receipt->save();
        }

        return [
            'teachers_count' => $teachers->count(),
            'synced_months' => $syncedMonths,
            'synced_annual' => $syncedAnnual,
        ];
    }
}
