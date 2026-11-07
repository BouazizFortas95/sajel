<?php

namespace App\Models;

use App\Enums\InventoryReformStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReform extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'reform_date' => 'date',
        'domains_notification_date' => 'date',
        'total_items_count' => 'integer',
        'total_estimated_value' => 'float',
        'status' => InventoryReformStatus::class,
    ];

    public function committeePresident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committee_president_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
