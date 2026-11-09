<?php

namespace App\Services;

use App\Filament\Pages\TeacherReceiptsHub;
use App\Models\SchoolSetting;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class PedagogicalReceiptReminderService
{
    /**
     * Map of reminder items to their configuration.
     *
     * @var array<string, array{title: string, body: string, group: string, column: string}>
     */
    public const REMINDER_DEFINITIONS = [
        'annual_plan' => [
            'title' => 'تذكير إداري: تسليم المخطط السنوي',
            'body' => 'نذكركم بضرورة تسليم المخطط السنوي المعتمد لدى إدارة المؤسسة للمطابقة والتأشير.',
            'group' => 'plans',
            'column' => 'annual_plan_submitted',
        ],
        'monthly_plan' => [
            'title' => 'تذكير إداري: تسليم التدرج الشهري',
            'body' => 'يُرجى تسليم التدرج البيداغوجي المعتمد للشهر الجاري لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => '_current_month',
        ],
        'september' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر سبتمبر',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر سبتمبر لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'september_submitted',
        ],
        'october' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر أكتوبر',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر أكتوبر لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'october_submitted',
        ],
        'november' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر نوفمبر',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر نوفمبر لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'november_submitted',
        ],
        'december' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر ديسمبر',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر ديسمبر لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'december_submitted',
        ],
        'january' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر جانفي',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر جانفي لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'january_submitted',
        ],
        'february' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر فيفري',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر فيفري لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'february_submitted',
        ],
        'march' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر مارس',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر مارس لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'march_submitted',
        ],
        'april' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر أفريل',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر أفريل لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'april_submitted',
        ],
        'may' => [
            'title' => 'تذكير إداري: تسليم تدرج شهر ماي',
            'body' => 'يُرجى تسليم التدرج البيداغوجي لشهر ماي لدى ناظر الدروس للتأشير والمتابعة.',
            'group' => 'plans',
            'column' => 'may_submitted',
        ],
        'diagnostic_test' => [
            'title' => 'تذكير إداري: تسليم كشف المستوى',
            'body' => 'يُرجى إيداع نتائج وتحليل تقويم كشف المستوى التشخيصي لدى الإدارة التربوية.',
            'group' => 'evaluations',
            'column' => 'diagnostic_test_submitted',
        ],
        'homework_1' => [
            'title' => 'تذكير إداري: تسليم الواجب المنزلي (1)',
            'body' => 'يُرجى تسليم نموذج وموضوع الواجب المنزلي رقم 01 لدى ناظر الدروس.',
            'group' => 'evaluations',
            'column' => 'homework_1_submitted',
        ],
        'homework_2' => [
            'title' => 'تذكير إداري: تسليم الواجب المنزلي (2)',
            'body' => 'يُرجى تسليم نموذج وموضوع الواجب المنزلي رقم 02 لدى ناظر الدروس.',
            'group' => 'evaluations',
            'column' => 'homework_2_submitted',
        ],
        'homework_3' => [
            'title' => 'تذكير إداري: تسليم الواجب المنزلي (3)',
            'body' => 'يُرجى تسليم نموذج وموضوع الواجب المنزلي رقم 03 لدى ناظر الدروس.',
            'group' => 'evaluations',
            'column' => 'homework_3_submitted',
        ],
        'quiz_1' => [
            'title' => 'تذكير إداري: تسليم موضوع الفرض المحروس (1)',
            'body' => 'نحيطكم علماً بضرورة تسليم موضوع وسلالم تنقيط الفرض المحروس الأول في الآجال المحددة.',
            'group' => 'evaluations',
            'column' => 'quiz_1_submitted',
        ],
        'quiz_2' => [
            'title' => 'تذكير إداري: تسليم موضوع الفرض المحروس (2)',
            'body' => 'نحيطكم علماً بضرورة تسليم موضوع وسلالم تنقيط الفرض المحروس الثاني في الآجال المحددة.',
            'group' => 'evaluations',
            'column' => 'quiz_2_submitted',
        ],
        'quiz_3' => [
            'title' => 'تذكير إداري: تسليم موضوع الفرض المحروس (3)',
            'body' => 'نحيطكم علماً بضرورة تسليم موضوع وسلالم تنقيط الفرض المحروس الثالث في الآجال المحددة.',
            'group' => 'evaluations',
            'column' => 'quiz_3_submitted',
        ],
        'exam_1' => [
            'title' => 'تذكير إداري: تسليم موضوع اختبار الفصل الأول',
            'body' => 'نذكركم بوجوب تسليم مواضيع وسلالم التصحيح لاختبارات الفصل الأول قبل انطلاق أسبوع الامتحانات.',
            'group' => 'evaluations',
            'column' => 'exam_1_submitted',
        ],
        'exam_2' => [
            'title' => 'تذكير إداري: تسليم موضوع اختبار الفصل الثاني',
            'body' => 'نذكركم بوجوب تسليم مواضيع وسلالم التصحيح لاختبارات الفصل الثاني قبل انطلاق أسبوع الامتحانات.',
            'group' => 'evaluations',
            'column' => 'exam_2_submitted',
        ],
        'exam_3' => [
            'title' => 'تذكير إداري: تسليم موضوع اختبار الفصل الثالث',
            'body' => 'نذكركم بوجوب تسليم مواضيع وسلالم التصحيح لاختبارات الفصل الثالث قبل انطلاق أسبوع الامتحانات.',
            'group' => 'evaluations',
            'column' => 'exam_3_submitted',
        ],
    ];

    /**
     * Resolve academic year defaulting to the current school setting.
     */
    protected function resolveAcademicYear(?string $academicYear): string
    {
        return $academicYear ?: (SchoolSetting::current()?->academic_year ?? '2026-2027');
    }

    /**
     * Resolve the target column for checking submission status.
     */
    protected function resolveColumn(string $itemKey): string
    {
        $def = self::REMINDER_DEFINITIONS[$itemKey] ?? null;

        if (! $def) {
            return $itemKey.'_submitted';
        }

        if ($def['column'] === '_current_month') {
            $monthKey = app(PedagogicalPlanReceiptService::class)->getCurrentAcademicMonthKey() ?? 'september';

            return $monthKey.'_submitted';
        }

        return $def['column'];
    }

    /**
     * Get teachers who have NOT yet submitted the specified deliverable.
     *
     * @return Collection<int, User>
     */
    public function getPendingTeachers(string $itemKey, ?int $subjectId = null, ?string $academicYear = null): Collection
    {
        $year = $this->resolveAcademicYear($academicYear);
        $def = self::REMINDER_DEFINITIONS[$itemKey] ?? null;
        $group = $def['group'] ?? 'plans';
        $column = $this->resolveColumn($itemKey);

        $query = User::query()
            ->where('role', 'teacher')
            ->orderBy('name');

        if ($subjectId) {
            $query->where(function ($q) use ($subjectId) {
                $q->where('primary_subject_id', $subjectId)
                    ->orWhere('secondary_subject_id', $subjectId);
            });
        }

        if ($group === 'plans') {
            $query->where(function ($q) use ($year, $column) {
                $q->whereDoesntHave('pedagogicalPlanReceipts', function ($sub) use ($year) {
                    $sub->where('academic_year', $year);
                })
                    ->orWhereHas('pedagogicalPlanReceipts', function ($sub) use ($year, $column) {
                        $sub->where('academic_year', $year)
                            ->where(function ($c) use ($column) {
                                $c->where($column, false)->orWhereNull($column);
                            });
                    });
            });
        } else {
            $query->where(function ($q) use ($year, $column) {
                $q->whereDoesntHave('evaluationWorkReceipts', function ($sub) use ($year) {
                    $sub->where('academic_year', $year);
                })
                    ->orWhereHas('evaluationWorkReceipts', function ($sub) use ($year, $column) {
                        $sub->where('academic_year', $year)
                            ->where(function ($c) use ($column) {
                                $c->where($column, false)->orWhereNull($column);
                            });
                    });
            });
        }

        return $query->get();
    }

    /**
     * Send reminder notification to a single teacher.
     */
    public function sendTeacherReminder(
        User $teacher,
        string $itemKey,
        ?string $customMessage = null
    ): void {
        $def = self::REMINDER_DEFINITIONS[$itemKey] ?? [
            'title' => 'تذكير إداري بمتابعة الوثائق التربوية',
            'body' => 'يُرجى مراجعة إدارة المؤسسة بخصوص تسليم وتأشير وثائقكم التربوية.',
        ];

        $title = $def['title'];
        $body = $customMessage ?: $def['body'];

        Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->icon('heroicon-o-bell-alert')
            ->actions([
                Action::make('viewReceipts')
                    ->label('معاينة كشف المتابعة والتأشير')
                    ->url(TeacherReceiptsHub::getUrl()),
            ])
            ->sendToDatabase($teacher);
    }

    /**
     * Send bulk reminders to all teachers who haven't submitted the item.
     *
     * @return int Number of teachers reminded
     */
    public function sendBulkReminders(
        string $itemKey,
        ?int $subjectId = null,
        ?string $academicYear = null,
        ?string $customMessage = null
    ): int {
        $pendingTeachers = $this->getPendingTeachers($itemKey, $subjectId, $academicYear);
        $count = 0;

        foreach ($pendingTeachers as $teacher) {
            $this->sendTeacherReminder($teacher, $itemKey, $customMessage);
            $count++;
        }

        return $count;
    }
}
