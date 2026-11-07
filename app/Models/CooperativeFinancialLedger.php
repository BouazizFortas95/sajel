<?php

namespace App\Models;

use App\Enums\CooperativeTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeFinancialLedger extends Model
{
    use HasFactory;

    protected $table = 'cooperative_financial_ledger';

    protected $fillable = [
        'transaction_reference',
        'academic_year',
        'school_cooperative_id',
        'transaction_date',
        'transaction_type',
        'amount_dzd',
        'beneficiary_or_source',
        'payment_method',
        'receipt_voucher_number',
        'recorded_by_user_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => CooperativeTransactionType::class,
            'transaction_date' => 'date',
            'amount_dzd' => 'decimal:2',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(SchoolCooperative::class, 'school_cooperative_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
