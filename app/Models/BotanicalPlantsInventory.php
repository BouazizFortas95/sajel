<?php

namespace App\Models;

use App\Enums\BotanicalPlantCategory;
use App\Enums\PlantHealthStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotanicalPlantsInventory extends Model
{
    use HasFactory;

    protected $table = 'botanical_plants_inventory';

    protected $fillable = [
        'plant_code',
        'pedagogical_garden_plot_id',
        'botanical_name',
        'common_name_ar',
        'common_name_fr',
        'plant_category',
        'quantity_planted',
        'planting_date',
        'expected_harvest_date',
        'health_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'plant_category' => BotanicalPlantCategory::class,
            'health_status' => PlantHealthStatus::class,
            'planting_date' => 'date',
            'expected_harvest_date' => 'date',
            'quantity_planted' => 'integer',
        ];
    }

    public function gardenPlot(): BelongsTo
    {
        return $this->belongsTo(PedagogicalGardenPlot::class, 'pedagogical_garden_plot_id');
    }
}
