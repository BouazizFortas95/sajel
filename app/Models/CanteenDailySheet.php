<?php

namespace App\Models;

use App\Enums\CanteenSheetStatus;
use Carbon\Carbon;
use Database\Factories\CanteenDailySheetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenDailySheet extends Model
{
    /** @use HasFactory<CanteenDailySheetFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'sheet_date',
        'reference_number',
        'half_board_students_present',
        'boarder_students_present',
        'staff_beneficiaries_count',
        'service_workers_count',
        'total_beneficiaries_count',
        'menu_description',
        'total_cost',
        'cost_per_meal',
        'storekeeper_name',
        'kitchen_hygiene_status',
        'dietary_allergy_alerts',
        'notes',
        'status',
        'certified_at',
        'certified_by_id',
    ];

    protected $casts = [
        'sheet_date' => 'date',
        'certified_at' => 'datetime',
        'total_cost' => 'decimal:2',
        'cost_per_meal' => 'decimal:2',
        'status' => CanteenSheetStatus::class,
    ];

    public function setSheetDateAttribute($value): void
    {
        $this->attributes['sheet_date'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CanteenConsumptionEntry::class);
    }

    public function certifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'certified_by_id');
    }

    public function calculateTotalBeneficiaries(): int
    {
        return (int) $this->half_board_students_present
            + (int) $this->boarder_students_present
            + (int) $this->staff_beneficiaries_count
            + (int) $this->service_workers_count;
    }

    public function recalculateTotals(): void
    {
        $this->total_beneficiaries_count = $this->calculateTotalBeneficiaries();
        $this->total_cost = (float) $this->entries()->sum('total_item_cost');

        $this->cost_per_meal = $this->total_beneficiaries_count > 0
            ? round($this->total_cost / $this->total_beneficiaries_count, 2)
            : 0.00;

        $this->save();
    }

    public function isCertified(): bool
    {
        return $this->status === CanteenSheetStatus::Certified;
    }

    public function scopeForYear(Builder $query, string $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForMonth(Builder $query, int $month, ?int $year = null): Builder
    {
        return $query->whereMonth('sheet_date', $month)
            ->when($year, fn ($q) => $q->whereYear('sheet_date', $year));
    }
}
