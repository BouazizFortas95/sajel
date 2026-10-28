<?php

namespace App\Models;

use Database\Factories\LabSeatingPlanStationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabSeatingPlanStation extends Model
{
    /** @use HasFactory<LabSeatingPlanStationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'seating_plan_id',
        'lab_station_id',
        'student_id',
        'position_x',
        'position_y',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position_x' => 'integer',
            'position_y' => 'integer',
        ];
    }

    public function seatingPlan(): BelongsTo
    {
        return $this->belongsTo(LabSeatingPlan::class, 'seating_plan_id');
    }

    public function labStation(): BelongsTo
    {
        return $this->belongsTo(LabStation::class);
    }

    public function student(): ?BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Whether a student is assigned to this station.
     */
    public function isAssigned(): bool
    {
        return $this->student_id !== null;
    }
}
