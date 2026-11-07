<?php

namespace App\Filament\Pages;

use App\Enums\InventoryReformStatus;
use App\Models\InventoryAllocation;
use App\Models\InventoryItem;
use App\Models\InventoryReform;
use App\Models\User;
use App\Services\SchoolInventoryService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class SchoolInventoryHub extends Page
{
    protected static string $view = 'filament.pages.school-inventory-hub';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 3;

    public string $activeTab = 'ledger'; // ledger | allocations | reforms

    // Ledger filters & search
    public string $search = '';

    public string $categoryFilter = 'all';

    public string $conditionFilter = 'all';

    public string $locationTypeFilter = 'all';

    // Modal state: Add Inventory Item
    public bool $isItemModalOpen = false;

    public string $itemCategory = 'furniture';

    public string $itemDesignation = '';

    public string $itemSpecifications = '';

    public string $itemAcquisitionDate = '';

    public string $itemAcquisitionSource = 'ميزانية المؤسسة';

    public ?float $itemAcquisitionCost = null;

    public int $itemQuantity = 1;

    public string $itemLocationType = 'classroom';

    public string $itemLocationName = '';

    public ?int $itemCustodianId = null;

    public string $itemCondition = 'good';

    public string $itemNotes = '';

    // Modal state: New Allocation Card
    public bool $isAllocationModalOpen = false;

    public string $allocationRoomName = '';

    public string $allocationRoomType = 'classroom';

    public ?int $allocationResponsibleId = null;

    public string $allocationNotes = '';

    // Modal state: New Reform Minutes
    public bool $isReformModalOpen = false;

    public string $reformDate = '';

    public ?int $reformPresidentId = null;

    public string $reformObservations = '';

    /** @var array<int, int|string> */
    public array $reformItemIds = [];

    public function mount(): void
    {
        $today = now(config('app.timezone', 'Africa/Algiers'))->format('Y-m-d');
        $this->itemAcquisitionDate = $today;
        $this->reformDate = $today;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageSchoolInventory();
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
        return __('سجل الجرد العام والعتاد');
    }

    public function getTitle(): string|Htmlable
    {
        return __('سجل الجرد الدائم العام والخاص وتسيير العتاد المدرسي');
    }

    // ─── Ledger Actions ─────────────────────────────────────────

    public function openItemModal(): void
    {
        $this->isItemModalOpen = true;
    }

    public function closeItemModal(): void
    {
        $this->isItemModalOpen = false;
    }

    public function saveItem(): void
    {
        $this->validate([
            'itemCategory' => 'required|string',
            'itemDesignation' => 'required|string|max:255',
            'itemAcquisitionDate' => 'required|date',
            'itemLocationName' => 'required|string|max:255',
            'itemQuantity' => 'required|integer|min:1',
        ]);

        $service = app(SchoolInventoryService::class);
        $service->registerItem([
            'category' => $this->itemCategory,
            'designation' => $this->itemDesignation,
            'specifications' => $this->itemSpecifications,
            'acquisition_date' => $this->itemAcquisitionDate,
            'acquisition_source' => $this->itemAcquisitionSource,
            'acquisition_cost' => $this->itemAcquisitionCost,
            'quantity' => $this->itemQuantity,
            'location_type' => $this->itemLocationType,
            'location_name' => $this->itemLocationName,
            'custodian_id' => $this->itemCustodianId,
            'condition' => $this->itemCondition,
            'notes' => $this->itemNotes,
            'recorded_by' => Auth::id(),
        ]);

        $this->closeItemModal();

        $this->itemDesignation = '';
        $this->itemSpecifications = '';
        $this->itemAcquisitionCost = null;
        $this->itemQuantity = 1;
        $this->itemLocationName = '';
        $this->itemNotes = '';

        Notification::make()
            ->title(__('تم قيد التجهيز في سجل الجرد الدائم بنجاح'))
            ->success()
            ->send();
    }

    public function getItemsProperty()
    {
        return InventoryItem::query()
            ->with(['custodian', 'recordedBy'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('designation', 'like', "%{$this->search}%")
                        ->orWhere('inventory_number', 'like', "%{$this->search}%")
                        ->orWhere('location_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->conditionFilter !== 'all', fn ($q) => $q->where('condition', $this->conditionFilter))
            ->when($this->locationTypeFilter !== 'all', fn ($q) => $q->where('location_type', $this->locationTypeFilter))
            ->orderByDesc('id')
            ->get();
    }

    // ─── Allocation Actions ─────────────────────────────────────

    public function openAllocationModal(): void
    {
        $this->isAllocationModalOpen = true;
    }

    public function closeAllocationModal(): void
    {
        $this->isAllocationModalOpen = false;
    }

    public function saveAllocation(): void
    {
        $this->validate([
            'allocationRoomName' => 'required|string|max:255',
            'allocationRoomType' => 'required|string',
        ]);

        $service = app(SchoolInventoryService::class);
        $service->recordAllocation([
            'room_name' => $this->allocationRoomName,
            'room_type' => $this->allocationRoomType,
            'responsible_user_id' => $this->allocationResponsibleId,
            'notes' => $this->allocationNotes,
            'signed_at' => now(config('app.timezone', 'Africa/Algiers')),
            'recorded_by' => Auth::id(),
        ]);

        $this->closeAllocationModal();
        $this->allocationRoomName = '';
        $this->allocationNotes = '';

        Notification::make()
            ->title(__('تم إنشاء وتوثيق بطاقة التخصيص بنجاح'))
            ->success()
            ->send();
    }

    public function getAllocationsProperty()
    {
        return InventoryAllocation::with(['responsibleUser', 'recordedBy'])
            ->orderByDesc('id')
            ->get();
    }

    // ─── Reform Actions ─────────────────────────────────────────

    public function openReformModal(): void
    {
        $this->isReformModalOpen = true;
    }

    public function closeReformModal(): void
    {
        $this->isReformModalOpen = false;
    }

    public function saveReform(): void
    {
        $this->validate([
            'reformDate' => 'required|date',
            'reformItemIds' => 'required|array|min:1',
        ]);

        $service = app(SchoolInventoryService::class);
        $service->recordReform([
            'reform_date' => $this->reformDate,
            'committee_president_id' => $this->reformPresidentId ?? Auth::id(),
            'commission_observations' => $this->reformObservations,
            'status' => InventoryReformStatus::Proposed,
            'recorded_by' => Auth::id(),
        ], array_map('intval', $this->reformItemIds));

        $this->closeReformModal();
        $this->reformObservations = '';
        $this->reformItemIds = [];

        Notification::make()
            ->title(__('تم إعداد محضر الإسقاط وحصر العتاد بنجاح'))
            ->success()
            ->send();
    }

    public function getReformsProperty()
    {
        return InventoryReform::with(['committeePresident', 'recordedBy'])
            ->orderByDesc('id')
            ->get();
    }
}
