<?php

namespace App\Models;

use App\Enums\GardenIrrigationType;
use App\Enums\GardenPlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedagogicalGardenPlot extends Model
{
    use HasFactory;

    protected $table = 'pedagogical_garden_plots';

    protected $fillable = [
        'plot_code',
        'plot_name',
        'surface_area_sqm',
        'soil_type',
        'irrigation_type',
        'location_in_school',
        'assigned_classroom_id',
        'supervising_teacher_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'irrigation_type' => GardenIrrigationType::class,
            'status' => GardenPlotStatus::class,
            'surface_area_sqm' => 'decimal:2',
        ];
    }

    public function assignedClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'assigned_classroom_id');
    }

    public function supervisingTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervising_teacher_id');
    }

    public function plants(): HasMany
    {
        return $this->hasMany(BotanicalPlantsInventory::class, 'pedagogical_garden_plot_id');
    }
}
