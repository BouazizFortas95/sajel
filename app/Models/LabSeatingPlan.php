<?php

namespace App\Models;

use Database\Factories\LabSeatingPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabSeatingPlan extends Model
{
    /** @use HasFactory<LabSeatingPlanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lab_id',
        'group_id',
        'name',
        'notes',
    ];

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function group(): ?BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function stations(): HasMany
    {
        return $this->hasMany(LabSeatingPlanStation::class, 'seating_plan_id')
            ->orderBy('position_x')
            ->orderBy('position_y');
    }

    public function assignedStudents(): HasMany
    {
        return $this->hasManyThrough(
            Student::class,
            LabSeatingPlanStation::class,
            'seating_plan_id',
            'id',
            'id',
            'student_id',
        );
    }

    /**
     * A readable label combining plan name and lab name.
     */
    public function display_name(): string
    {
        return "{$this->name} — {$this->lab->name}";
    }
}
