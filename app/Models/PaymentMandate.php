<?php

namespace App\Models;

use App\Enums\MandateStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMandate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'mandate_date' => 'date',
        'invoice_date' => 'date',
        'liquidated_amount' => 'float',
        'payment_method' => PaymentMethod::class,
        'status' => MandateStatus::class,
    ];

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(BudgetCommitment::class, 'commitment_id');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
