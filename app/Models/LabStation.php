<?php

namespace App\Models;

use App\Enums\LabStatus;
use Database\Factories\LabStationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabStation extends Model
{
    /** @use HasFactory<LabStationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lab_id',
        'station_number',
        'device_type',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'station_number' => 'integer',
            'status' => LabStatus::class,
        ];
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }
}
