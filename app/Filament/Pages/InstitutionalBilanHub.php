<?php

namespace App\Filament\Pages;

use App\Models\QuarterlyInstitutionalReport;
use App\Models\SchoolSetting;
use App\Services\InstitutionalBilanService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class InstitutionalBilanHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.institutional-bilan-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'performance'; // 'performance', 'attendance', 'climate', 'executive'

    #[Url(as: 'trimester')]
    public int $trimester = 1;

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    public ?int $reportId = null;

    public string $status = 'draft';

    public ?string $validatedAt = null;

    public ?string $validatedByName = null;

    public ?string $executiveSummary = null;

    public ?string $pedagogicalRecommendations = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('الحصيلة التربوية والفصلية (Bilan)');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإشراف والمراقبة الإدارية');
    }

    public function getTitle(): string
    {
        return __('الحصيلة البيداغوجية والتربوية الشاملة للمؤسسة (Bilan Trimestriel)');
    }

    public function mount(InstitutionalBilanService $service): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('-', '/', $setting->academic_year);
        }

        $this->loadReport($service);
    }

    public function loadReport(InstitutionalBilanService $service): void
    {
        $report = $service->getOrCreateReport($this->academicYear, $this->trimester);

        $this->reportId = $report->id;
        $this->status = $report->status->value;
        $this->validatedAt = $report->validated_at?->format('Y-m-d H:i');
        $this->validatedByName = $report->validator?->name;
        $this->executiveSummary = $report->executive_summary;
        $this->pedagogicalRecommendations = $report->pedagogical_recommendations;
    }

    public function updatedTrimester(InstitutionalBilanService $service): void
    {
        $this->loadReport($service);
    }

    public function updatedAcademicYear(InstitutionalBilanService $service): void
    {
        $this->loadReport($service);
    }

    public function getReportProperty(): ?QuarterlyInstitutionalReport
    {
        if (! $this->reportId) {
            return null;
        }

        return QuarterlyInstitutionalReport::with('validator')->find($this->reportId);
    }

    public function syncLiveMetrics(InstitutionalBilanService $service): void
    {
        $report = $this->report;
        if (! $report) {
            return;
        }

        if ($report->isValidated()) {
            Notification::make()
                ->title(__('الحصيلة مصادق عليها ومجمدة'))
                ->body(__('لا يمكن تحديث المؤشرات لحصيلة مجمدة. يرجى إلغاء التجميد أولاً.'))
                ->warning()
                ->send();

            return;
        }

        $service->syncLiveMetrics($report);
        $this->loadReport($service);

        Notification::make()
            ->title(__('تم تحديث المؤشرات بنجاح'))
            ->body(__('تمت مزامنة جميع معدلات النجاح وإحصائيات الحضور والانضباط مباشرة من قاعدة البيانات.'))
            ->success()
            ->send();
    }

    public function saveDraftNotes(InstitutionalBilanService $service): void
    {
        $report = $this->report;
        if (! $report) {
            return;
        }

        if ($report->isValidated()) {
            Notification::make()
                ->title(__('الحصيلة مجمدة'))
                ->body(__('لا يمكن تعديل التقرير بعد المصادقة الرسمية.'))
                ->warning()
                ->send();

            return;
        }

        $report->update([
            'executive_summary' => $this->executiveSummary,
            'pedagogical_recommendations' => $this->pedagogicalRecommendations,
        ]);

        Notification::make()
            ->title(__('تم حفظ المسودة بنجاح'))
            ->body(__('تم حفظ تقرير المدير والتوصيات البيداغوجية.'))
            ->success()
            ->send();
    }

    public function validateAndFreeze(InstitutionalBilanService $service): void
    {
        $report = $this->report;
        if (! $report) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Save current notes first
        $report->update([
            'executive_summary' => $this->executiveSummary,
            'pedagogical_recommendations' => $this->pedagogicalRecommendations,
        ]);

        $service->validateReport($report, $user);
        $this->loadReport($service);

        Notification::make()
            ->title(__('تمت المصادقة وتجميد الحصيلة'))
            ->body(__('أصبحت الحصيلة الفصلية وثيقة إدارية رسمية مجمدة وقابلة للطباعة والتصدير لمديرية التربية.'))
            ->success()
            ->send();
    }

    public function reopenReport(InstitutionalBilanService $service): void
    {
        $report = $this->report;
        if (! $report) {
            return;
        }

        $service->reopenReport($report);
        $this->loadReport($service);

        Notification::make()
            ->title(__('تم إلغاء التجميد'))
            ->body(__('تمت إعادة الحصيلة إلى حالة مسودة ديناميكية قابلة للتعديل والمزامنة الحية.'))
            ->info()
            ->send();
    }
}
