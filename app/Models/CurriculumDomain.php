<?php

namespace App\Models;

use Database\Factories\CurriculumDomainFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CurriculumDomain extends Model
{
    /** @use HasFactory<CurriculumDomainFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subject_id',
        'level_id',
        'name',
        'code',
        'description',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(CurriculumSequence::class);
    }

    public function resources(): HasManyThrough
    {
        return $this->hasManyThrough(CurriculumResource::class, CurriculumSequence::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * A readable label for admin selects: subject — level — domain.
     */
    public function display_name(): string
    {
        return implode(' — ', array_filter([
            $this->subject?->name,
            $this->level?->name,
            $this->name,
        ]));
    }
}
