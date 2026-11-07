<?php

namespace App\Models;

use App\Enums\BudgetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolBudgetLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'fiscal_year' => 'integer',
        'budget_type' => BudgetType::class,
        'allocated_amount' => 'float',
        'committed_amount' => 'float',
        'liquidated_amount' => 'float',
    ];

    public function commitments(): HasMany
    {
        return $this->hasMany(BudgetCommitment::class, 'budget_line_id');
    }

    public function availableBalance(): float
    {
        return max(0.0, (float) ($this->allocated_amount - $this->committed_amount));
    }

    public function utilizationRate(): float
    {
        if ($this->allocated_amount <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->committed_amount / $this->allocated_amount) * 100, 1));
    }
}
