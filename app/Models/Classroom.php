<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'level_id',
        'name',
        'academic_year',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * A readable label combining level, class name and academic year.
     */
    public function display_name(): string
    {
        $label = "{$this->level?->name} — {$this->name}";

        return filled($this->academic_year) ? "{$label} ({$this->academic_year})" : $label;
    }
}
