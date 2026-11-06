<?php

namespace App\Filament\Pages;

use App\Models\CanteenConsumptionEntry;
use App\Models\CanteenFoodItem;
use App\Services\SchoolCanteenService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolCanteenHub extends Page
{
    protected static string $view = 'filament.pages.school-canteen-hub';

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->canManageCanteen();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('المصالح المالية والكتاب المدرسي');
    }

    public static function getNavigationLabel(): string
    {
        return __('الإطعام المدرسي ونصف الداخلي');
    }

    public function getTitle(): string|Htmlable
    {
        return __('الإطعام المدرسي وتسيير نصف الداخلي');
    }

    public string $activeTab = 'sheet';

    public string $academicYear = '2026/2027';

    public string $selectedDate = '';

    // Sheet Headcount Fields
    public int $halfBoardPresent = 0;

    public int $staffPresent = 12;

    public int $workersPresent = 6;

    public string $menuDescription = '';

    public string $hygieneStatus = 'good';

    public string $allergyNotes = '';

    // Add Consumption Entry Modal
    public bool $isAddEntryModalOpen = false;

    public ?int $entryFoodItemId = null;

    public float $entryQuantity = 10.0;

    public ?float $entryUnitPrice = null;

    // Add / Restock Food Item Modal
    public bool $isPantryModalOpen = false;

    public string $pantryName = '';

    public string $pantryCategory = 'dry_goods';

    public string $pantryUnit = 'كغ';

    public float $pantryUnitPrice = 100.0;

    public float $pantryCurrentStock = 50.0;

    public float $pantryThreshold = 10.0;

    public function mount(SchoolCanteenService $service): void
    {
        $this->academicYear = $service->resolveAcademicYear();
        $this->selectedDate = now()->toDateString();
        $this->loadCurrentSheetData($service);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['sheet', 'pantry', 'menus', 'bilan']) ? $tab : 'sheet';
    }

    public function updatedSelectedDate(SchoolCanteenService $service): void
    {
        $this->loadCurrentSheetData($service);
    }

    public function loadCurrentSheetData(SchoolCanteenService $service): void
    {
        $sheet = $service->getOrCreateDailySheet($this->selectedDate, $this->academicYear);
        $this->halfBoardPresent = (int) $sheet->half_board_students_present;
        $this->staffPresent = (int) $sheet->staff_beneficiaries_count;
        $this->workersPresent = (int) $sheet->service_workers_count;
        $this->menuDescription = (string) ($sheet->menu_description ?? '');
        $this->hygieneStatus = (string) ($sheet->kitchen_hygiene_status ?? 'good');
        $this->allergyNotes = (string) ($sheet->dietary_allergy_alerts ?? '');
    }

    public function saveSheetDetails(SchoolCanteenService $service): void
    {
        $sheet = $service->getOrCreateDailySheet($this->selectedDate, $this->academicYear);

        $sheet->half_board_students_present = $this->halfBoardPresent;
        $sheet->staff_beneficiaries_count = $this->staffPresent;
        $sheet->service_workers_count = $this->workersPresent;
        $sheet->menu_description = $this->menuDescription;
        $sheet->kitchen_hygiene_status = $this->hygieneStatus;
        $sheet->dietary_allergy_alerts = $this->allergyNotes;
        $sheet->recalculateTotals();

        Notification::make()
            ->title(__('تم حفظ بيانات ورقة الاستهلاك اليومي'))
            ->success()
            ->send();
    }

    public function openAddEntryModal(): void
    {
        $firstItem = CanteenFoodItem::active()->first();
        $this->entryFoodItemId = $firstItem?->id;
        $this->entryQuantity = 10.0;
        $this->entryUnitPrice = $firstItem ? (float) $firstItem->unit_price : 100.0;
        $this->isAddEntryModalOpen = true;
    }

    public function closeAddEntryModal(): void
    {
        $this->isAddEntryModalOpen = false;
    }

    public function updatedEntryFoodItemId(): void
    {
        if ($this->entryFoodItemId) {
            $item = CanteenFoodItem::find($this->entryFoodItemId);
            if ($item) {
                $this->entryUnitPrice = (float) $item->unit_price;
            }
        }
    }

    public function saveConsumptionEntry(SchoolCanteenService $service): void
    {
        $this->validate([
            'entryFoodItemId' => ['required', 'exists:canteen_food_items,id'],
            'entryQuantity' => ['required', 'numeric', 'min:0.1'],
        ]);

        try {
            $sheet = $service->getOrCreateDailySheet($this->selectedDate, $this->academicYear);
            $service->addConsumptionEntry($sheet, $this->entryFoodItemId, (float) $this->entryQuantity, $this->entryUnitPrice);

            $this->isAddEntryModalOpen = false;

            Notification::make()
                ->title(__('تم تسجيل إخراج المادة واستهلاكها'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('تعذّر إضافة المادة'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteConsumptionEntry(int $entryId, SchoolCanteenService $service): void
    {
        $entry = CanteenConsumptionEntry::find($entryId);
        if ($entry) {
            $service->removeConsumptionEntry($entry);

            Notification::make()
                ->title(__('تم حذف المادة واسترجاع الكمية للمخزن'))
                ->success()
                ->send();
        }
    }

    public function certifySheet(SchoolCanteenService $service): void
    {
        $sheet = $service->getOrCreateDailySheet($this->selectedDate, $this->academicYear);
        $service->certifyDailySheet($sheet, Auth::user());

        Notification::make()
            ->title(__('تمت المصادقة والتأشيرة على ورقة الاستهلاك اليومي بنجاح'))
            ->success()
            ->send();
    }

    public function openPantryModal(): void
    {
        $this->pantryName = '';
        $this->pantryCategory = 'dry_goods';
        $this->pantryUnit = 'كغ';
        $this->pantryUnitPrice = 100.0;
        $this->pantryCurrentStock = 50.0;
        $this->pantryThreshold = 10.0;
        $this->isPantryModalOpen = true;
    }

    public function closePantryModal(): void
    {
        $this->isPantryModalOpen = false;
    }

    public function savePantryItem(): void
    {
        $this->validate([
            'pantryName' => ['required', 'string', 'max:255'],
            'pantryUnitPrice' => ['required', 'numeric', 'min:0'],
            'pantryCurrentStock' => ['required', 'numeric', 'min:0'],
        ]);

        CanteenFoodItem::updateOrCreate(
            ['name' => $this->pantryName],
            [
                'category' => $this->pantryCategory,
                'unit' => $this->pantryUnit,
                'unit_price' => $this->pantryUnitPrice,
                'current_stock' => $this->pantryCurrentStock,
                'safety_threshold' => $this->pantryThreshold,
                'is_active' => true,
            ]
        );

        $this->isPantryModalOpen = false;

        Notification::make()
            ->title(__('تم حفظ وتحديث المادة في المخزن'))
            ->success()
            ->send();
    }

    public function seedStandardPantry(SchoolCanteenService $service): void
    {
        $count = $service->seedStandardAlgerianPantryItems();

        Notification::make()
            ->title(__('تمت تهيئة مخزون المطعم بالمواد الجزائرية القياسية'))
            ->body(__('تمت إضافة / تحديث :count مادة غذائية.', ['count' => $count]))
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $service = app(SchoolCanteenService::class);

        return $service->getCanteenOverview($this->academicYear, $this->selectedDate);
    }
}
