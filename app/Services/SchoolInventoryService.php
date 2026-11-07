<?php

namespace App\Services;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Enums\InventoryRecolementStatus;
use App\Models\InventoryAllocation;
use App\Models\InventoryItem;
use App\Models\InventoryRecolement;
use App\Models\InventoryReform;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;

class SchoolInventoryService
{
    /**
     * Generate official Algerian inventory registration number (المنشور الوزاري 143/97).
     * Format: {Category Code}/{Sequential Number 4-digits}/{Year} (e.g. 02/0001/2026).
     */
    public function generateInventoryNumber(InventoryCategory $category, ?int $year = null): string
    {
        $year = $year ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $code = $category->code();

        $prefix = "{$code}/";
        $suffix = "/{$year}";

        $lastItem = InventoryItem::where('category', $category->value)
            ->whereYear('acquisition_date', $year)
            ->where('inventory_number', 'like', "{$prefix}%{$suffix}")
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($lastItem && preg_match('#^'.preg_quote($code, '#').'/(\d+)/'.$year.'$#', $lastItem->inventory_number, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        $formattedSeq = str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        return "{$code}/{$formattedSeq}/{$year}";
    }

    /**
     * Register a new inventory item.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerItem(array $data): InventoryItem
    {
        $cat = is_string($data['category']) ? InventoryCategory::from($data['category']) : $data['category'];

        if (empty($data['inventory_number'])) {
            $year = ! empty($data['acquisition_date']) ? (int) date('Y', strtotime($data['acquisition_date'])) : null;
            $data['inventory_number'] = $this->generateInventoryNumber($cat, $year);
        }

        return InventoryItem::create($data);
    }

    /**
     * Update condition of an asset.
     */
    public function updateCondition(InventoryItem $item, InventoryCondition $condition, ?string $reason = null): InventoryItem
    {
        $updates = [
            'condition' => $condition,
        ];

        if ($condition === InventoryCondition::Reformed) {
            $updates['is_reformed'] = true;
            $updates['reformed_at'] = now(config('app.timezone', 'Africa/Algiers'));
            $updates['reform_reason'] = $reason ?? $item->reform_reason;
        }

        $item->update($updates);

        return $item;
    }

    /**
     * Generate room allocation reference.
     */
    public function generateAllocationRef(?int $year = null): string
    {
        $year = $year ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $count = InventoryAllocation::whereYear('created_at', $year)->count() + 1;
        $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "LOC/{$year}/{$seq}";
    }

    /**
     * Record room/lab allocation.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordAllocation(array $data): InventoryAllocation
    {
        if (empty($data['allocation_ref'])) {
            $data['allocation_ref'] = $this->generateAllocationRef();
        }

        // Auto count items in this room if not provided
        if (empty($data['items_count']) && ! empty($data['room_name'])) {
            $data['items_count'] = InventoryItem::active()
                ->where('location_name', $data['room_name'])
                ->count();
        }

        return InventoryAllocation::create($data);
    }

    /**
     * Record official asset decommissioning and reform minutes.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $itemIds
     */
    public function recordReform(array $data, array $itemIds = []): InventoryReform
    {
        $year = (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        if (empty($data['reform_number'])) {
            $count = InventoryReform::whereYear('created_at', $year)->count() + 1;
            $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            $data['reform_number'] = "REF/{$year}/{$seq}";
        }

        return DB::transaction(function () use ($data, $itemIds) {
            $items = InventoryItem::whereIn('id', $itemIds)->get();
            $totalCount = $items->count();
            $totalValue = $items->sum('acquisition_cost');

            $data['total_items_count'] = $totalCount;
            $data['total_estimated_value'] = $totalValue;

            $reform = InventoryReform::create($data);

            foreach ($items as $item) {
                $item->update([
                    'condition' => InventoryCondition::Reformed,
                    'is_reformed' => true,
                    'reformed_at' => $reform->reform_date,
                    'reform_reason' => $reform->commission_observations ?? 'إسقاط قانوني من الجرد',
                ]);
            }

            return $reform;
        });
    }

    /**
     * Assemble printable data for the official General and Specific Inventory Ledger (A4 Landscape).
     *
     * @return array<string, mixed>
     */
    public function assembleGeneralLedgerData(?string $category = null, ?string $locationType = null): array
    {
        $query = InventoryItem::query()
            ->with(['custodian', 'recordedBy'])
            ->orderBy('inventory_number');

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($locationType && $locationType !== 'all') {
            $query->where('location_type', $locationType);
        }

        $items = $query->get();
        $totalCost = $items->sum(fn ($i) => ($i->acquisition_cost ?? 0) * ($i->quantity ?? 1));

        return [
            'items' => $items,
            'category' => $category ? InventoryCategory::tryFrom($category) : null,
            'locationType' => $locationType,
            'totalItems' => $items->sum('quantity'),
            'totalCost' => $totalCost,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Room/Lab Allocation Card (A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assembleAllocationSheetData(InventoryAllocation $allocation): array
    {
        $allocation->loadMissing(['responsibleUser', 'recordedBy']);

        $items = InventoryItem::active()
            ->where('location_name', $allocation->room_name)
            ->orderBy('inventory_number')
            ->get();

        return [
            'allocation' => $allocation,
            'items' => $items,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Asset Reform and Decommissioning Minutes (A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assembleReformPvData(InventoryReform $reform): array
    {
        $reform->loadMissing(['committeePresident', 'recordedBy']);

        $items = InventoryItem::reformed()
            ->where('reformed_at', $reform->reform_date)
            ->get();

        return [
            'reform' => $reform,
            'items' => $items,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Generate official inventory verification / recolement reference (المنشور 143/97).
     * Format: REC/{Year}/{Sequential Number 3-digits} (e.g. REC/2026/001).
     */
    public function generateRecolementNumber(?int $year = null): string
    {
        $year = $year ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $count = InventoryRecolement::whereYear('created_at', $year)->count() + 1;
        $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "REC/{$year}/{$seq}";
    }

    /**
     * Record room/lab periodic inventory verification (Récolement et Conformité).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordRecolement(array $data): InventoryRecolement
    {
        if (empty($data['recolement_number'])) {
            $data['recolement_number'] = $this->generateRecolementNumber();
        }

        if (empty($data['recolement_date'])) {
            $data['recolement_date'] = now(config('app.timezone', 'Africa/Algiers'))->format('Y-m-d');
        }

        return DB::transaction(function () use ($data) {
            $recolement = InventoryRecolement::create($data);

            if ($recolement->allocation_id) {
                $isConformed = ($recolement->status === InventoryRecolementStatus::Conformed) || ($recolement->missing_count === 0 && $recolement->damaged_count === 0);
                InventoryAllocation::where('id', $recolement->allocation_id)->update([
                    'condition_verified' => $isConformed,
                ]);
            }

            return $recolement;
        });
    }

    /**
     * Assemble printable data for Custody Handover Voucher (سند تسليم واستلام العهدة المدرسية والمكتبية والمخبرية - A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assembleCustodyVoucherData(InventoryAllocation $allocation): array
    {
        $allocation->loadMissing(['responsibleUser', 'recordedBy']);

        $items = InventoryItem::active()
            ->where('location_name', $allocation->room_name)
            ->orderBy('category')
            ->orderBy('inventory_number')
            ->get();

        $itemsByCategory = $items->groupBy(fn ($item) => $item->category->label());

        return [
            'allocation' => $allocation,
            'items' => $items,
            'itemsByCategory' => $itemsByCategory,
            'totalQuantity' => $items->sum('quantity'),
            'totalValue' => $items->sum(fn ($i) => ($i->acquisition_cost ?? 0) * ($i->quantity ?? 1)),
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Periodic Inventory Conformity Card (بطاقة ومحضر المطابقة والمعاينة السنوية - A4 Landscape).
     *
     * @return array<string, mixed>
     */
    public function assembleRecolementData(InventoryRecolement $recolement): array
    {
        $recolement->loadMissing(['allocation', 'auditor', 'witness', 'recordedBy']);

        $items = InventoryItem::active()
            ->where('location_name', $recolement->room_name)
            ->orderBy('inventory_number')
            ->get();

        return [
            'recolement' => $recolement,
            'items' => $items,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Telemetry & KPI statistics for the Inventory Cockpit.
     *
     * @return array<string, mixed>
     */
    public function getInventoryKpis(): array
    {
        $activeItems = InventoryItem::active()->get();

        $totalItems = $activeItems->sum('quantity');
        $totalValue = $activeItems->sum(fn ($i) => ($i->acquisition_cost ?? 0) * ($i->quantity ?? 1));
        $scientificCount = $activeItems->where('category', InventoryCategory::ScientificLab)->sum('quantity');
        $furnitureCount = $activeItems->where('category', InventoryCategory::Furniture)->sum('quantity');
        $audiovisualCount = $activeItems->where('category', InventoryCategory::AudiovisualIt)->sum('quantity');
        $reformedCount = InventoryItem::reformed()->count();
        $damagedCount = $activeItems->where('condition', InventoryCondition::Damaged)->count();

        $recolementCount = InventoryRecolement::count();
        $conformedCount = InventoryRecolement::where('status', InventoryRecolementStatus::Conformed)->count();
        $discrepancyCount = InventoryRecolement::whereIn('status', [
            InventoryRecolementStatus::Discrepancy,
            InventoryRecolementStatus::UnderInvestigation,
        ])->count();

        return [
            'totalItems' => $totalItems,
            'totalValue' => round($totalValue, 2),
            'scientificCount' => $scientificCount,
            'furnitureCount' => $furnitureCount,
            'audiovisualCount' => $audiovisualCount,
            'reformedCount' => $reformedCount,
            'damagedCount' => $damagedCount,
            'recolementCount' => $recolementCount,
            'conformedCount' => $conformedCount,
            'discrepancyCount' => $discrepancyCount,
        ];
    }
}
