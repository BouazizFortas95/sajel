<?php

namespace App\Models;

use Database\Factories\EducationalInstitutionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalInstitution extends Model
{
    /** @use HasFactory<EducationalInstitutionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'stage',
        'directorate_name',
        'wilaya',
        'municipality',
        'principal_name',
        'counselor_name',
        'phone',
        'email',
        'address',
        'capacity',
        'regime',
        'status',
        'is_current',
        'academic_year',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get the active/current educational institution.
     */
    public static function getCurrent(): ?self
    {
        return static::where('is_current', true)->first() ?? static::first();
    }

    /**
     * Mark this institution as the active system institution and sync with SchoolSetting.
     */
    public function makeCurrent(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_current' => false]);
        $this->update(['is_current' => true]);

        // Sync with SchoolSetting singleton
        $setting = SchoolSetting::query()->first();
        if (! $setting) {
            $setting = new SchoolSetting;
        }

        $setting->fill([
            'school_name' => $this->name,
            'directorate_name' => $this->directorate_name,
            'municipality_name' => $this->municipality,
            'principal_name' => $this->principal_name,
            'counselor_name' => $this->counselor_name,
            'school_code' => $this->code,
            'academic_year' => $this->academic_year ?? '2026-2027',
        ]);
        $setting->save();

        SchoolSetting::flushCache();
    }

    /**
     * Scope for active institutions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for current institution.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
