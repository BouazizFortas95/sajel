<?php

namespace App\Models;

use App\Enums\InventoryRecolementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRecolement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'recolement_date' => 'date',
        'theoretical_count' => 'integer',
        'verified_count' => 'integer',
        'conform_count' => 'integer',
        'missing_count' => 'integer',
        'surplus_count' => 'integer',
        'damaged_count' => 'integer',
        'status' => InventoryRecolementStatus::class,
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(InventoryAllocation::class, 'allocation_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function witness(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witness_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

