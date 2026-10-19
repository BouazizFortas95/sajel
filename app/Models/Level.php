<?php

namespace App\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'education_phase_id',
        'name',
    ];

    public function educationPhase(): BelongsTo
    {
        return $this->belongsTo(EducationPhase::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    /**
     * A readable label combining phase and level names.
     */
    public function display_name(): string
    {
        return "{$this->educationPhase?->name} — {$this->name}";
    }
}
