<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use App\Services\SchoolCensusService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class SchoolCensusHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.school-census-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'demographics'; // 'demographics', 'pyramid_staffing', 'facilities'

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    // Facilities & Administrative Form State
    public int $ordinaryClassroomsCount = 12;

    public int $specializedRoomsCount = 2;

    public int $scienceLabsCount = 2;

    public int $computerLabsCount = 1;

    public int $libraryCapacity = 40;

    public int $canteenCapacity = 250;

    public int $administrativeStaffCount = 4;

    public int $educationSupervisorsCount = 6;

    public int $serviceWorkersCount = 8;

    public ?string $referenceNumber = null;

    public ?string $observations = null;

    // Record Status State
    public bool $isCertified = false;

    public ?string $certifiedAt = null;

    public ?string $certifiedByName = null;

    public function mount(SchoolCensusService $service): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('-', '/', $setting->academic_year);
        }

        $this->loadRecord($service);
    }

    public function loadRecord(SchoolCensusService $service): void
    {
        $record = $service->getOrCreateRecord($this->academicYear);

        $this->ordinaryClassroomsCount = $record->ordinary_classrooms_count ?? 12;
        $this->specializedRoomsCount = $record->specialized_rooms_count ?? 2;
        $this->scienceLabsCount = $record->science_labs_count ?? 2;
        $this->computerLabsCount = $record->computer_labs_count ?? 1;
        $this->libraryCapacity = $record->library_capacity ?? 40;
        $this->canteenCapacity = $record->canteen_capacity ?? 250;
        $this->administrativeStaffCount = $record->administrative_staff_count ?? 4;
        $this->educationSupervisorsCount = $record->education_supervisors_count ?? 6;
        $this->serviceWorkersCount = $record->service_workers_count ?? 8;
        $this->referenceNumber = $record->reference_number;
        $this->observations = $record->observations;
        $this->isCertified = $record->isCertified();
        $this->certifiedAt = $record->certified_at?->format('Y-m-d H:i');
        $this->certifiedByName = $record->certifiedBy?->name;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public static function getNavigationLabel(): string
    {
        return __('إحصاء الدخول المدرسي 15 أكتوبر');
    }

    public function getTitle(): string
    {
        return __('الكشف الإحصائي الشامل للدخول المدرسي إلى غاية 15 أكتوبر');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('printCensus')
                ->label(__('طباعة الكشف الرسمي (A4)'))
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn (): string => route('print.school-census', [
                    'locale' => app()->getLocale(),
                    'academic_year' => $this->academicYear,
                ]))
                ->openUrlInNewTab(),

            Action::make('pdfCensus')
                ->label(__('تحميل كشف الإحصاء PDF'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('print.school-census.pdf', [
                    'locale' => app()->getLocale(),
                    'academic_year' => $this->academicYear,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function saveFacilities(SchoolCensusService $service): void
    {
        $user = Auth::user();
        abort_unless($user?->isAdmin(), 403);

        $record = $service->getOrCreateRecord($this->academicYear);

        $record->update([
            'ordinary_classrooms_count' => $this->ordinaryClassroomsCount,
            'specialized_rooms_count' => $this->specializedRoomsCount,
            'science_labs_count' => $this->scienceLabsCount,
            'computer_labs_count' => $this->computerLabsCount,
            'library_capacity' => $this->libraryCapacity,
            'canteen_capacity' => $this->canteenCapacity,
            'administrative_staff_count' => $this->administrativeStaffCount,
            'education_supervisors_count' => $this->educationSupervisorsCount,
            'service_workers_count' => $this->serviceWorkersCount,
            'reference_number' => $this->referenceNumber,
            'observations' => $this->observations,
        ]);

        $this->loadRecord($service);

        Notification::make()
            ->title(__('تم حفظ بيانات الهياكل والمرافق بنجاح'))
            ->success()
            ->send();
    }

    public function certifyRecord(SchoolCensusService $service): void
    {
        $user = Auth::user();
        abort_unless($user?->isAdmin(), 403);

        $record = $service->getOrCreateRecord($this->academicYear);

        $service->certifyCensus($record, $user, $this->referenceNumber, $this->observations);
        $this->loadRecord($service);

        Notification::make()
            ->title(__('تمت المصادقة الرسمية على استمارة 15 أكتوبر وتجميد البيانات بنجاح!'))
            ->body(__('تم أرشفة النسخة الرسمية المطابقة لمعايير وزارة التربية الوطنية وتجميد أعداد التلاميذ والتأطير.'))
            ->success()
            ->send();
    }

    public function unlockRecord(SchoolCensusService $service): void
    {
        $user = Auth::user();
        abort_unless($user?->isAdmin(), 403);

        $record = $service->getOrCreateRecord($this->academicYear);

        $record->update([
            'status' => 'draft',
            'certified_at' => null,
            'certified_by_id' => null,
            'snapshot_data' => null,
        ]);

        $this->loadRecord($service);

        Notification::make()
            ->title(__('تم إلغاء تجميد الكشف والعودة للوضع التحريري (Draft)'))
            ->warning()
            ->send();
    }

    public function getViewData(): array
    {
        $service = app(SchoolCensusService::class);

        return $service->assembleCensusReport($this->academicYear);
    }
}
