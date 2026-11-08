<?php

namespace App\Filament\Pages;

use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Services\PedagogicalPlanReceiptService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class PedagogicalPlanReceiptsHub extends Page
{
    protected static ?string $slug = 'pedagogical-plan-receipts';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.pedagogical-plan-receipts-hub';

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
        return __('استلام المخططات السنوية والشهرية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج والتفتيش');
    }

    public function getTitle(): string
    {
        return __('وثيقة استلام وتأشير المخططات والتدرجات السنوية والشهرية');
    }

    public function mount(): void
    {
        $this->academicYear = SchoolSetting::current()?->academic_year ?? '2026-2027';
    }

    /**
     * Interactive toggle for annual plan submission.
     */
    public function toggleAnnualPlan(int $userId, bool $submitted): void
    {
        $service = app(PedagogicalPlanReceiptService::class);
        $service->togglePlan($userId, 'annual', $submitted, null, $this->academicYear);

        Notification::make()
            ->title($submitted ? 'تم تأكيد استلام المخطط السنوي' : 'تم إلغاء تأشير المخطط السنوي')
            ->success()
            ->send();
    }

    /**
     * Interactive toggle for a specific month submission.
     */
    public function toggleMonthPlan(int $userId, string $monthKey, bool $submitted): void
    {
        $service = app(PedagogicalPlanReceiptService::class);
        $service->togglePlan($userId, $monthKey, $submitted, null, $this->academicYear);

        $monthLabel = PedagogicalPlanReceipt::MONTHS[$monthKey] ?? $monthKey;

        Notification::make()
            ->title($submitted ? "تم تسجيل استلام تدرج شهر {$monthLabel}" : "تم إلغاء استلام شهر {$monthLabel}")
            ->success()
            ->send();
    }

    /**
     * Update the submission date for a plan.
     */
    public function updatePlanDate(int $userId, string $planKey, ?string $date): void
    {
        $service = app(PedagogicalPlanReceiptService::class);
        $service->togglePlan($userId, $planKey, true, $date, $this->academicYear);

        Notification::make()
            ->title('تم تحديث تاريخ استلام المخطط')
            ->success()
            ->send();
    }

    /**
     * Save teacher receipt notes.
     */
    public function saveNotes(int $userId, ?string $notes): void
    {
        $service = app(PedagogicalPlanReceiptService::class);
        $service->updateNotes($userId, $notes, $this->academicYear);

        Notification::make()
            ->title('تم حفظ الملاحظات الإدارية')
            ->success()
            ->send();
    }

    /**
     * Bulk confirm annual plans for all currently filtered teachers.
     */
    public function bulkConfirmAnnualPlans(): void
    {
        $service = app(PedagogicalPlanReceiptService::class);
        $teachers = $service->getTeachersWithReceipts($this->selectedSubjectId, $this->academicYear);

        $userIds = $teachers->pluck('id')->all();
        $count = $service->bulkMarkAnnualPlan($userIds, true, now()->format('Y-m-d'), $this->academicYear);

        Notification::make()
            ->title("تم تأشير وتأكيد استلام المخطط السنوي لـ {$count} أستاذًا بنجاح")
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
                ->url(fn () => route('print.pedagogical-plan-receipts', [
                    'locale' => app()->getLocale(),
                    'subject_id' => $this->selectedSubjectId,
                    'layout' => 'separate',
                ]))
                ->openUrlInNewTab(),

            Action::make('printContinuous')
                ->label('طباعة مدمجة لجميع المواد')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => empty($this->selectedSubjectId))
                ->url(fn () => route('print.pedagogical-plan-receipts', [
                    'locale' => app()->getLocale(),
                    'layout' => 'continuous',
                ]))
                ->openUrlInNewTab(),

            Action::make('pdf')
                ->label('تحميل بصيغة PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('print.pedagogical-plan-receipts.pdf', [
                    'locale' => app()->getLocale(),
                    'subject_id' => $this->selectedSubjectId,
                    'layout' => 'separate',
                ]))
                ->openUrlInNewTab(),

            Action::make('word')
                ->label('تصدير Word (.docx)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(fn () => route('print.pedagogical-plan-receipts.word', [
                    'locale' => app()->getLocale(),
                    'subject_id' => $this->selectedSubjectId,
                    'layout' => 'separate',
                ]))
                ->openUrlInNewTab(),

            Action::make('bulkAnnual')
                ->label('تأكيد استلام المخطط السنوي للجميع')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('تأكيد استلام المخططات السنوية')
                ->modalDescription('هل ترغب في تأشير وتأكيد استلام المخطط السنوي لجميع الأساتذة المعروضين بتاريخ اليوم؟')
                ->action(fn () => $this->bulkConfirmAnnualPlans()),
        ];
    }

    /**
     * Get view data for rendering.
     */
    public function getViewData(): array
    {
        $service = app(PedagogicalPlanReceiptService::class);

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
            'months' => PedagogicalPlanReceipt::MONTHS,
            'stats' => $service->getSummaryStats($this->academicYear),
            'academicYear' => $this->academicYear,
        ];
    }
}
