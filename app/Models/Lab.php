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

    /**
     * Whether this lab is a computer / informatics laboratory with PCs.
     */
    public function isInformatics(): bool
    {
        $name = mb_strtolower($this->name);

        return str_contains($name, 'إعلام')
            || str_contains($name, 'حاسوب')
            || str_contains($name, 'حاسب')
            || str_contains($name, 'computer')
            || str_contains($name, 'informatique');
    }

    /**
     * Whether this lab is a physics or chemistry laboratory.
     */
    public function isPhysics(): bool
    {
        $name = mb_strtolower($this->name);

        return str_contains($name, 'فيزياء')
            || str_contains($name, 'فيزيائ')
            || str_contains($name, 'كيمياء')
            || str_contains($name, 'physique')
            || str_contains($name, 'chimie');
    }

    /**
     * Whether this lab is a natural sciences / biology laboratory.
     */
    public function isNaturalScience(): bool
    {
        $name = mb_strtolower($this->name);

        return str_contains($name, 'طبيع')
            || str_contains($name, 'حياة')
            || str_contains($name, 'svt')
            || str_contains($name, 'science')
            || str_contains($name, 'biolog');
    }

    /**
     * Determine the discipline key: 'informatics', 'physics', 'natural_sciences', or 'science'.
     */
    public function labType(): string
    {
        if ($this->isInformatics()) {
            return 'informatics';
        }

        if ($this->isPhysics()) {
            return 'physics';
        }

        if ($this->isNaturalScience()) {
            return 'natural_sciences';
        }

        return 'science';
    }

    /**
     * Localized discipline title.
     */
    public function labTypeLabel(): string
    {
        return match ($this->labType()) {
            'informatics' => __('مخبر الإعلام الآلي والبرمجة'),
            'physics' => __('مخبر العلوم الفيزيائية والكيمياء'),
            'natural_sciences' => __('مخبر علوم الطبيعة والحياة'),
            default => __('مخبر التجارب العلمية والتطبيقية'),
        };
    }

    /**
     * Localized label for an individual station (PC vs Experiment Bench).
     */
    public function stationLabel(): string
    {
        return $this->isInformatics() ? __('محطة حاسوب') : __('طاولة تجارب');
    }
}
