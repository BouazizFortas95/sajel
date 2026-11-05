<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Textbook extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_received' => 'integer',
        'is_active' => 'boolean',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(StudentTextbookLoan::class);
    }

    public function distributedCount(?string $academicYear = null): int
    {
        $year = $academicYear ?? SchoolSetting::current()?->academic_year ?? '2026/2027';

        return $this->loans()->where('academic_year', $year)->count();
    }

    public function availableStock(?string $academicYear = null): int
    {
        return max(0, $this->total_received - $this->distributedCount($academicYear));
    }

    public function returnedCount(?string $academicYear = null): int
    {
        $year = $academicYear ?? SchoolSetting::current()?->academic_year ?? '2026/2027';

        return $this->loans()
            ->where('academic_year', $year)
            ->whereNotNull('returned_at')
            ->count();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel(Builder $query, Level|int $level): Builder
    {
        $levelId = $level instanceof Level ? $level->id : $level;

        return $query->where('level_id', $levelId);
    }
}

