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
        'class_number',
        'name',
        'academic_year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'class_number' => 'integer',
        ];
    }

    /**
     * Build the standardized classroom name right before saving:
     * "{grade number} {phase abbreviation} {class number}".
     *
     * The name is left untouched when any part of the convention is
     * unavailable, so legacy/manual names keep working.
     */
    protected static function booted(): void
    {
        static::saving(function (Classroom $classroom): void {
            $level = $classroom->level()->with('educationPhase')->first();

            if (! $level) {
                return;
            }

            $gradeNumber = $level->grade_number;
            $classNumber = $classroom->class_number;
            $abbreviation = trim((string) ($level->educationPhase?->phase_abbreviation ?? ''));

            if (
                ! is_numeric($gradeNumber) || (int) $gradeNumber < 1
                || ! is_numeric($classNumber) || (int) $classNumber < 1
                || $abbreviation === ''
            ) {
                return;
            }

            $classroom->name = "{$gradeNumber} {$abbreviation} {$classNumber}";
        });
    }

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
