<?php

namespace App\Services;

use App\Filament\Pages\TeacherReceiptsHub;
use App\Models\DailyLog;
use App\Models\EvaluationWorkReceipt;
use App\Models\SchoolSetting;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class DailyLogEvaluationWorkSyncService
{
    /**
     * Map of evaluation keys to their human-readable labels.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        'diagnostic_test' => 'اختبار كشف المستوى',
        'homework_1' => 'الواجب المنزلي (1)',
        'homework_2' => 'الواجب المنزلي (2)',
        'homework_3' => 'الواجب المنزلي (3)',
        'quiz_1' => 'الفرض المحروس (1)',
        'quiz_2' => 'الفرض المحروس (2)',
        'quiz_3' => 'الفرض المحروس (3)',
        'exam_1' => 'اختبار الفصل الأول',
        'exam_2' => 'اختبار الفصل الثاني',
        'exam_3' => 'اختبار الفصل الثالث',
    ];

    /**
     * Get label for an evaluation key.
     */
    public function getLabel(string $key): string
    {
        return self::LABELS[$key] ?? $key;
    }

    /**
     * Intelligently detect if a DailyLog represents an evaluation deliverable.
     */
    public function detectEvaluationType(DailyLog $log): ?string
    {
        $title = mb_strtolower($log->title ?? '');
        $date = $log->date ? Carbon::parse($log->date) : now();
        $month = (int) $date->format('n');

        // Trimester resolution based on Algerian academic calendar:
        // T1: Sep (9) to Dec (12)
        // T2: Jan (1) to Mar (3)
        // T3: Apr (4) to Jun (6)
        $trimester = match (true) {
            $month >= 9 && $month <= 12 => 1,
            $month >= 1 && $month <= 3 => 2,
            default => 3,
        };

        // 1. Diagnostic test detection
        if (str_contains($title, 'تشخيصي') || str_contains($title, 'كشف المستوى') || str_contains($title, 'تقويم المكتسبات')) {
            return 'diagnostic_test';
        }

        // 2. Exam detection (الاختبارات الفصلية)
        if (str_contains($title, 'اختبار') || str_contains($title, 'امتحان') || str_contains($title, 'composition')) {
            if (str_contains($title, 'أول') || str_contains($title, 'اول') || str_contains($title, '1') || str_contains($title, '01')) {
                return 'exam_1';
            }
            if (str_contains($title, 'ثاني') || str_contains($title, '2') || str_contains($title, '02')) {
                return 'exam_2';
            }
            if (str_contains($title, 'ثالث') || str_contains($title, '3') || str_contains($title, '03')) {
                return 'exam_3';
            }

            return "exam_{$trimester}";
        }

        // 3. Quiz detection (الفروض المحروسة)
        if (str_contains($title, 'فرض') || str_contains($title, 'الفرض') || str_contains($title, 'devoir')) {
            if (str_contains($title, 'أول') || str_contains($title, 'اول') || str_contains($title, '1') || str_contains($title, '01')) {
                return 'quiz_1';
            }
            if (str_contains($title, 'ثاني') || str_contains($title, '2') || str_contains($title, '02')) {
                return 'quiz_2';
            }
            if (str_contains($title, 'ثالث') || str_contains($title, '3') || str_contains($title, '03')) {
                return 'quiz_3';
            }

            return "quiz_{$trimester}";
        }

        // 4. Homework detection (الواجبات المنزلية)
        $homeworkText = mb_strtolower($log->homework ?? '');
        if (str_contains($title, 'واجب') || str_contains($title, 'منزلي') || (filled($homeworkText) && str_contains($homeworkText, 'واجب'))) {
            if (str_contains($title, 'أول') || str_contains($title, 'اول') || str_contains($title, '1') || str_contains($title, '01') || str_contains($homeworkText, '1')) {
                return 'homework_1';
            }
            if (str_contains($title, 'ثاني') || str_contains($title, '2') || str_contains($title, '02') || str_contains($homeworkText, '2')) {
                return 'homework_2';
            }
            if (str_contains($title, 'ثالث') || str_contains($title, '3') || str_contains($title, '03') || str_contains($homeworkText, '3')) {
                return 'homework_3';
            }

            return "homework_{$trimester}";
        }

        return null;
    }

    /**
     * Synchronize a completed DailyLog with the teacher's EvaluationWorkReceipt.
     *
     * @return array{type: string, label: string, date: string, was_already_submitted: bool}|null
     */
    public function sync(DailyLog $log, ?string $forcedType = null): ?array
    {
        if (! $log->isCompleted()) {
            return null;
        }

        $type = $forcedType ?: $this->detectEvaluationType($log);

        if (! $type) {
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

        $year = SchoolSetting::current()?->academic_year ?? '2026-2027';

        $receipt = EvaluationWorkReceipt::firstOrNew([
            'user_id' => $teacher->id,
            'academic_year' => $year,
        ]);

        $subCol = "{$type}_submitted";
        $dateCol = "{$type}_date";

        $wasSubmitted = (bool) $receipt->{$subCol};
        $targetDate = $log->date ? $log->date->format('Y-m-d') : now()->format('Y-m-d');

        $receipt->{$subCol} = true;
        $receipt->{$dateCol} = $targetDate;
        $receipt->save();

        $label = $this->getLabel($type);

        // Send a celebratory database notification to the teacher if newly marked
        if (! $wasSubmitted) {
            Notification::make()
                ->title("تم ربط وتأشير «{$label}» آلياً")
                ->body("تم رصد الحصة المنجزة في دفتر النصوص وتأشيرها تلقائياً في سجلك التقويمي المعتمد بتاريخ {$targetDate}.")
                ->icon('heroicon-o-check-circle')
                ->success()
                ->actions([
                    Action::make('viewReceipt')
                        ->label('معاينة كشف المتابعة')
                        ->url(TeacherReceiptsHub::getUrl()),
                ])
                ->sendToDatabase($teacher);
        }

        return [
            'type' => $type,
            'label' => $label,
            'date' => $targetDate,
            'was_already_submitted' => $wasSubmitted,
        ];
    }
}
