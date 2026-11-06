<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenConsumptionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'canteen_daily_sheet_id',
        'canteen_food_item_id',
        'quantity_consumed',
        'unit_price',
        'total_item_cost',
    ];

    protected $casts = [
        'quantity_consumed' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_item_cost' => 'decimal:2',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(CanteenDailySheet::class, 'canteen_daily_sheet_id');
    }

    public function foodItem(): BelongsTo
    {
        return $this->belongsTo(CanteenFoodItem::class, 'canteen_food_item_id');
    }
}
