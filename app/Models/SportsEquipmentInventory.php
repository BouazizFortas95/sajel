<?php

namespace App\Models;

use App\Enums\SportsEquipmentCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsEquipmentInventory extends Model
{
    use HasFactory;

    protected $table = 'sports_equipment_inventories';

    protected $fillable = [
        'equipment_code',
        'item_name',
        'discipline_id',
        'quantity_total',
        'quantity_operational',
        'quantity_damaged',
        'storage_location',
        'condition_rating',
        'last_inventory_date',
        'purchased_academic_year',
        'acquisition_source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'condition_rating' => SportsEquipmentCondition::class,
            'quantity_total' => 'integer',
            'quantity_operational' => 'integer',
            'quantity_damaged' => 'integer',
            'last_inventory_date' => 'date',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(SportsCulturalDiscipline::class, 'discipline_id');
    }
}
