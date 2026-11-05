<?php

namespace App\Models;

use Database\Factories\SchoolCensusRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolCensusRecord extends Model
{
    /** @use HasFactory<SchoolCensusRecordFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'census_date' => 'date',
        'certified_at' => 'datetime',
        'snapshot_data' => 'array',
        'ordinary_classrooms_count' => 'integer',
        'specialized_rooms_count' => 'integer',
        'science_labs_count' => 'integer',
        'computer_labs_count' => 'integer',
        'library_capacity' => 'integer',
        'canteen_capacity' => 'integer',
        'administrative_staff_count' => 'integer',
        'education_supervisors_count' => 'integer',
        'service_workers_count' => 'integer',
    ];

    public function certifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'certified_by_id');
    }

    public function isCertified(): bool
    {
        return $this->status === 'certified';
    }

    public function scopeForAcademicYear(Builder $query, ?string $year = null): Builder
    {
        $targetYear = $year ?? SchoolSetting::current()?->academic_year ?? '2026/2027';
        $altYear = str_contains($targetYear, '/') ? str_replace('/', '-', $targetYear) : str_replace('-', '/', $targetYear);

        return $query->where(function (Builder $q) use ($targetYear, $altYear): void {
            $q->where('academic_year', $targetYear)
                ->orWhere('academic_year', $altYear);
        });
    }
}
