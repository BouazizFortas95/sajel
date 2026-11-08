<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Services\EvaluationWorkReceiptService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class EvaluationWorkReceiptsHub extends Page
{
    protected static ?string $slug = 'evaluation-work-receipts';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.evaluation-work-receipts-hub';

    protected ?string $maxWidth = 'full';

    #[Url(as: 'subject')]
    public ?int $selectedSubjectId = null;

    #[Url(as: 'search')]
    public string $search = '';

    public ?string $academicYear = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return (bool) ($user?->isLeadership() || $user?->isAdmin() || $user?->isPrincipal() || $user?->isCensor() || $user?->isDeveloper());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('استلام الأعمال التقويمية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج والتفتيش');
    }

    public function getTitle(): string
    {
        return __('وثيقة تسليم الأعمال التقويمية');
    }

    public function mount(): void
    {
        $this->academicYear = SchoolSetting::current()?->academic_year ?? '2026-2027';
    }

    /**
     * Interactive toggle for a specific deliverable item (diagnostic_test, homework_1..3, quiz_1..3, exam_1..3).
     */
    public function toggleDeliverable(int $userId, string $itemKey, bool $submitted): void
    {
        $service = app(EvaluationWorkReceiptService::class);
        $service->toggleItem($userId, $itemKey, $submitted, null, $this->academicYear);

        $labels = [
            'diagnostic_test' => 'اختبار كشف المستوى',
            'homework_1' => 'الواجب المنزلي (1)',
            'homework_2' => 'الواجب المنزلي (2)',
            'homework_3' => 'الواجب المنزلي (3)',
            'quiz_1' => 'الفرض المحروس (1)',
            'quiz_2' => 'الفرض المحروس (2)',
            'quiz_3' => 'الفرض المحروس (3)',
            'exam_1' => 'الاختبار (1)',
            'exam_2' => 'الاختبار (2)',
            'exam_3' => 'الاختبار (3)',
        ];

        $label = $labels[$itemKey] ?? $itemKey;

        Notification::make()
            ->title($submitted ? "تم تسجيل تسليم {$label}" : "تم إلغاء تسليم {$label}")
            ->success()
            ->send();
    }

    /**
     * Update submission date for a deliverable.
     */
    public function updateDeliverableDate(int $userId, string $itemKey, ?string $date): void
    {
        $service = app(EvaluationWorkReceiptService::class);
        $service->toggleItem($userId, $itemKey, true, $date, $this->academicYear);

        Notification::make()
            ->title('تم تحديث تاريخ التسليم بنجاح')
            ->success()
            ->send();
    }

    /**
     * Save teacher receipt notes.
     */
    public function saveNotes(int $userId, ?string $notes): void
    {
        $service = app(EvaluationWorkReceiptService::class);
        $service->updateNotes($userId, $notes, $this->academicYear);

        Notification::make()
            ->title('تم حفظ الملاحظة الإدارية')
            ->success()
            ->send();
    }

    /**
     * Bulk confirm diagnostic tests for all currently filtered teachers.
     */
    public function bulkConfirmDiagnostic(): void
    {
        $service = app(EvaluationWorkReceiptService::class);
        $teachers = $service->getTeachersWithReceipts($this->selectedSubjectId, $this->academicYear);

        $userIds = $teachers->pluck('id')->all();
        $count = $service->bulkMarkItem($userIds, 'diagnostic_test', true, now()->format('Y-m-d'), $this->academicYear);

        Notification::make()
            ->title("تم تسجيل استلام اختبار كشف المستوى لـ {$count} أستاذًا بنجاح")
            ->success()
            ->send();
    }

    /**
     * Action buttons in Filament Header.
     */
    protected function getHeaderActions(): array
    {
        $subject = $this->selectedSubjectId ? Subject::find($this->selectedSubjectId) : null;
        $printLabel = $subject
            ? "طباعة وثيقة مادة {$subject->name} (صفحتين)"
            : 'طباعة جميع المواد (كل مادة في صفحة)';

        return [
            Action::make('printSeparate')
                ->label($printLabel)
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('print.evaluation-work-receipts', [
                    'locale' => app()->getLocale(),
                    'subject_id' => $this->selectedSubjectId,
                ]))
                ->openUrlInNewTab(),

            Action::make('pdf')
                ->label('تحميل بصيغة PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('print.evaluation-work-receipts.pdf', [
                    'locale' => app()->getLocale(),
                    'subject_id' => $this->selectedSubjectId,
                    'layout' => 'separate',
                ]))
                ->openUrlInNewTab(),

            Action::make('word')
                ->label('تصدير Word (.docx)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(fn () => route('print.evaluation-work-receipts.word', [
                    'locale' => app()->getLocale(),
                    'subject_id' => $this->selectedSubjectId,
                    'layout' => 'separate',
                ]))
                ->openUrlInNewTab(),

            Action::make('bulkDiagnostic')
                ->label('تأكيد استلام اختبار كشف المستوى للجميع')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('تأكيد استلام اختبار كشف المستوى')
                ->modalDescription('هل ترغب في تسجيل استلام اختبار كشف المستوى لجميع الأساتذة المعروضين بتاريخ اليوم؟')
                ->action(fn () => $this->bulkConfirmDiagnostic()),
        ];
    }

    /**
     * Get view data for rendering.
     */
    public function getViewData(): array
    {
        $service = app(EvaluationWorkReceiptService::class);

        $teachers = $service->getTeachersWithReceipts($this->selectedSubjectId, $this->academicYear);

        if (! empty($this->search)) {
            $term = trim($this->search);
            $teachers = $teachers->filter(function ($t) use ($term) {
                return str_contains($t->name, $term) ||
                       str_contains($t->email, $term) ||
                       str_contains($t->primarySubject?->name ?? '', $term);
            });
        }

        $groupedTeachers = $teachers->groupBy(fn ($t) => $t->primarySubject?->name ?? 'غير محدد');

        return [
            'school' => SchoolSetting::current(),
            'groupedTeachers' => $groupedTeachers,
            'subjects' => Subject::orderBy('name')->get(),
            'stats' => $service->getSummaryStats($this->academicYear),
            'academicYear' => $this->academicYear,
        ];
    }
}
