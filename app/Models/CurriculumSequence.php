<?php

namespace App\Models;

use Database\Factories\CurriculumSequenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumSequence extends Model
{
    /** @use HasFactory<CurriculumSequenceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'curriculum_domain_id',
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

    public function domain(): BelongsTo
    {
        return $this->belongsTo(CurriculumDomain::class, 'curriculum_domain_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(CurriculumResource::class, 'curriculum_sequence_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * A readable label for admin selects: domain — sequence.
     */
    public function display_name(): string
    {
        return implode(' — ', array_filter([
            $this->domain?->name,
            $this->name,
        ]));
    }
}
