<?php

namespace App\Models;

use Database\Factories\CurriculumResourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumResource extends Model
{
    /** @use HasFactory<CurriculumResourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'curriculum_sequence_id',
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

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(CurriculumSequence::class, 'curriculum_sequence_id');
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class, 'curriculum_resource_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * A readable path used by the daily-log selector and register summary:
     * domain — sequence — resource.
     */
    public function label(): string
    {
        return implode(' — ', array_filter([
            $this->sequence?->domain?->name,
            $this->sequence?->name,
            $this->name,
        ]));
    }
}
