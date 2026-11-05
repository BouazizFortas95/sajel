<?php

namespace App\Models;

use App\Enums\TransferReason;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransfer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'transfer_date' => 'date',
        'reference_date' => 'date',
        'school_file_forwarded_at' => 'date',
        'transfer_type' => TransferType::class,
        'reason' => TransferReason::class,
        'status' => TransferStatus::class,
        'school_file_forwarded' => 'boolean',
        'clearance_attached' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function isInward(): bool
    {
        return $this->transfer_type === TransferType::Inward;
    }

    public function isOutward(): bool
    {
        return $this->transfer_type === TransferType::Outward;
    }

    public function isApproved(): bool
    {
        return $this->status === TransferStatus::Approved;
    }

    public function scopeInward(Builder $query): Builder
    {
        return $query->where('transfer_type', TransferType::Inward);
    }

    public function scopeOutward(Builder $query): Builder
    {
        return $query->where('transfer_type', TransferType::Outward);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', TransferStatus::Approved);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TransferStatus::Pending);
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

