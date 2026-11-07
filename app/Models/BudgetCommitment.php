<?php

namespace App\Models;

use App\Enums\CommitmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCommitment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'order_date' => 'date',
        'estimated_amount' => 'float',
        'status' => CommitmentStatus::class,
    ];

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(SchoolBudgetLine::class, 'budget_line_id');
    }

    public function mandates(): HasMany
    {
        return $this->hasMany(PaymentMandate::class, 'commitment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLiquidated(): bool
    {
        return $this->status === CommitmentStatus::Liquidated;
    }
}
