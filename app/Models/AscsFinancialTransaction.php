<?php

namespace App\Models;

use App\Enums\AscsTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AscsFinancialTransaction extends Model
{
    use HasFactory;

    protected $table = 'ascs_financial_transactions';

    protected $fillable = [
        'transaction_reference',
        'academic_year',
        'transaction_type',
        'amount_dzd',
        'transaction_date',
        'payment_method',
        'invoice_or_receipt_ref',
        'description',
        'recorded_by_name',
        'treasurer_approved',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => AscsTransactionType::class,
            'amount_dzd' => 'decimal:2',
            'transaction_date' => 'date',
            'treasurer_approved' => 'boolean',
        ];
    }
}
