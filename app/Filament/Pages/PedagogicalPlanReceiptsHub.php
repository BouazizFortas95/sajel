<?php

namespace App\Filament\Pages;

use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Services\AdministrativeArchiveService;
use App\Services\PedagogicalPlanReceiptService;
use App\Services\PedagogicalReceiptReminderService;
use Filament\Actions\Action;
use Filament\Forms;
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

            Action::make('bulkMonthly')
                ->label('تأشير جماعي للمخطط الشهري')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('monthKey')
                        ->label('الشهر المستهدف')
                        ->required()
                        ->options([
                            'september' => 'سبتمبر',
                            'october' => 'أكتوبر',
                            'november' => 'نوفمبر',
                            'december' => 'ديسمبر',
                            'january' => 'جانفي',
                            'february' => 'فيفري',
                            'march' => 'مارس',
                            'april' => 'أفريل',
                            'may' => 'ماي',
                        ])
                        ->default(app(PedagogicalPlanReceiptService::class)->getCurrentAcademicMonthKey()),
                    Forms\Components\DatePicker::make('date')
                        ->label('تاريخ الاستلام والتأشير')
                        ->required()
                        ->default(now()->toDateString()),
                    Forms\Components\Toggle::make('submitted')
                        ->label('تأكيد الاستلام والتأشير (إلغاء التفعيل للإلغاء)')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $service = app(PedagogicalPlanReceiptService::class);
                    $teachers = $service->getTeachersWithReceipts($this->selectedSubjectId, $this->academicYear);
                    $userIds = $teachers->pluck('id')->all();
                    $count = $service->bulkMarkMonthlyPlan(
                        $userIds,
                        $data['monthKey'],
                        (bool) $data['submitted'],
                        $data['date'],
                        $this->academicYear
                    );

                    Notification::make()
                        ->title("تم تحديث تأشير المخطط الشهري لـ {$count} أستاذ بنجاح")
                        ->success()
                        ->send();
                }),

            Action::make('sendReminders')
                ->label('إرسال تذكير للمتأخرين')
                ->icon('heroicon-o-bell-alert')
                ->color('danger')
                ->form([
                    Forms\Components\Select::make('itemKey')
                        ->label('نوع المخطط المطلوب تسليمه')
                        ->required()
                        ->options([
                            'annual_plan' => 'المخطط السنوي المعتمد',
                            'monthly_plan' => 'تدرج الشهر الجاري',
                            'september' => 'تدرج شهر سبتمبر',
                            'october' => 'تدرج شهر أكتوبر',
                            'november' => 'تدرج شهر نوفمبر',
                            'december' => 'تدرج شهر ديسمبر',
                            'january' => 'تدرج شهر جانفي',
                            'february' => 'تدرج شهر فيفري',
                            'march' => 'تدرج شهر مارس',
                            'april' => 'تدرج شهر أفريل',
                            'may' => 'تدرج شهر ماي',
                        ])
                        ->default('monthly_plan'),
                    Forms\Components\Textarea::make('customMessage')
                        ->label('رسالة توجيهية إضافية (اختياري)')
                        ->placeholder('مثال: يُرجى تسليم التدرج قبل نهاية الأسبوع الحالي لدى ناظر الدروس.'),
                ])
                ->action(function (array $data): void {
                    $reminderService = app(PedagogicalReceiptReminderService::class);
                    $count = $reminderService->sendBulkReminders(
                        $data['itemKey'],
                        $this->selectedSubjectId,
                        $this->academicYear,
                        $data['customMessage'] ?? null
                    );

                    Notification::make()
                        ->title("تم إرسال إشعار تذكيري لـ {$count} أستاذ بنجاح")
                        ->body($count > 0 ? 'وصل التنبيه للأساتذة في صندوق الإشعارات العلوي بحساباتهم.' : 'جميع الأساتذة المعنيين قد سلموا هذا المخطط بالفعل.')
                        ->success()
                        ->send();
                }),

            Action::make('archiveToArchive')
                ->label('أرشفة في سجل المؤسسة')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('أرشفة الوثيقة في الأرشيف الإداري والتربوي')
                ->modalDescription('هل ترغب في حفظ نسخة رقمية رسمية معتمدة بصيغة PDF وتوثيقها برقم قيد رسمي داخل الأرشيف الإداري للمؤسسة؟')
                ->action(function (): void {
                    try {
                        $planService = app(PedagogicalPlanReceiptService::class);
                        $archiveService = app(AdministrativeArchiveService::class);
                        $subject = $this->selectedSubjectId ? Subject::find($this->selectedSubjectId) : null;

                        $pdfContent = $planService->generatePdf($this->selectedSubjectId, $this->academicYear, 'separate');
                        $res = $archiveService->archivePedagogicalReceiptRegister(
                            'plans',
                            $pdfContent,
                            $this->academicYear ?? $planService->getCurrentAcademicYear(),
                            $subject?->name
                        );

                        Notification::make()
                            ->title('تمت أرشفة الوثيقة في سجل المؤسسة بنجاح')
                            ->body("رقم القيد الرسمي للأرشيف: {$res['mail_number']}")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('تعذر إتمام الأرشفة')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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
