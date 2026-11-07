<?php

namespace App\Models;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'category' => InventoryCategory::class,
        'condition' => InventoryCondition::class,
        'acquisition_date' => 'date',
        'acquisition_cost' => 'float',
        'quantity' => 'integer',
        'is_reformed' => 'boolean',
        'reformed_at' => 'date',
    ];

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_reformed', false);
    }

    public function scopeReformed(Builder $query): Builder
    {
        return $query->where('is_reformed', true);
    }

    public function scopeByCategory(Builder $query, InventoryCategory|string $category): Builder
    {
        $val = $category instanceof InventoryCategory ? $category->value : $category;

        return $query->where('category', $val);
    }
}
