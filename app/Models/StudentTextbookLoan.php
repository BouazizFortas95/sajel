<?php

namespace App\Models;

use App\Enums\TextbookDistributionType;
use App\Enums\TextbookReturnCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTextbookLoan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'distributed_at' => 'date',
        'returned_at' => 'date',
        'distribution_type' => TextbookDistributionType::class,
        'return_condition' => TextbookReturnCondition::class,
        'clearance_granted' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function textbook(): BelongsTo
    {
        return $this->belongsTo(Textbook::class);
    }

    public function distributedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by_id');
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isSolidarity(): bool
    {
        return $this->distribution_type === TextbookDistributionType::Solidarity;
    }

    public function scopeForAcademicYear(Builder $query, ?string $year = null): Builder
    {
        $targetYear = $year ?? SchoolSetting::current()?->academic_year ?? '2026/2027';

        return $query->where('academic_year', $targetYear);
    }

    public function scopePendingReturn(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeSolidarity(Builder $query): Builder
    {
        return $query->where('distribution_type', TextbookDistributionType::Solidarity);
    }

    public function scopeCleared(Builder $query): Builder
    {
        return $query->where('clearance_granted', true);
    }
}
