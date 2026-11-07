<?php

namespace App\Models;

use App\Enums\UtilityPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityConsumptionRecord extends Model
{
    use HasFactory;

    protected $table = 'utility_consumption_records';

    protected $fillable = [
        'bill_reference',
        'academic_year',
        'energy_utility_meter_id',
        'billing_period_quarter',
        'reading_date_start',
        'reading_date_end',
        'previous_index',
        'current_index',
        'consumption_units',
        'invoiced_amount_dzd',
        'invoice_number',
        'invoice_reception_date',
        'payment_status',
        'meter_auditor_name',
        'anomaly_detected',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'reading_date_start' => 'date',
            'reading_date_end' => 'date',
            'invoice_reception_date' => 'date',
            'previous_index' => 'integer',
            'current_index' => 'integer',
            'consumption_units' => 'integer',
            'invoiced_amount_dzd' => 'decimal:2',
            'payment_status' => UtilityPaymentStatus::class,
            'anomaly_detected' => 'boolean',
        ];
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(EnergyUtilityMeter::class, 'energy_utility_meter_id');
    }
}

