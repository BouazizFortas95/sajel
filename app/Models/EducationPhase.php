<?php

namespace App\Models;

use Database\Factories\EducationPhaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class EducationPhase extends Model
{
    /** @use HasFactory<EducationPhaseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function classrooms(): HasManyThrough
    {
        return $this->hasManyThrough(Classroom::class, Level::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $query) => $query->where(fn (Builder $nested) => $nested
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%")));
    }
}
