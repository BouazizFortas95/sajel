<?php

namespace App\Models;

use App\Enums\LabStatus;
use Database\Factories\LabFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lab extends Model
{
    /** @use HasFactory<LabFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'level_id',
        'name',
        'academic_year',
        'capacity',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function stations(): HasMany
    {
        return $this->hasMany(LabStation::class)->orderBy('station_number');
    }

    public function workingStations(): HasMany
    {
        return $this->hasMany(LabStation::class)->where('status', LabStatus::Working->value);
    }

    public function brokenStations(): HasMany
    {
        return $this->hasMany(LabStation::class)->where('status', LabStatus::Broken->value);
    }

    public function underMaintenanceStations(): HasMany
    {
        return $this->hasMany(LabStation::class)->where('status', LabStatus::UnderMaintenance->value);
    }

    /**
     * A readable label combining name, level, and academic year.
     */
    public function display_name(): string
    {
        $label = $this->name;

        if ($this->level) {
            $label .= ' — '.$this->level->name;
        }

        return filled($this->academic_year) ? "{$label} ({$this->academic_year})" : $label;
    }
}
