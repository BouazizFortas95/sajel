<?php

namespace App\Models;

use App\Enums\FoodItemCategory;
use Database\Factories\CanteenFoodItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenFoodItem extends Model
{
    /** @use HasFactory<CanteenFoodItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'unit_price',
        'current_stock',
        'safety_threshold',
        'is_active',
    ];

    protected $casts = [
        'category' => FoodItemCategory::class,
        'unit_price' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'safety_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function consumptionEntries(): HasMany
    {
        return $this->hasMany(CanteenConsumptionEntry::class);
    }

    public function isStockLow(): bool
    {
        return (float) $this->current_stock <= (float) $this->safety_threshold;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'safety_threshold');
    }
}

