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
        'ip_address',
        'status',
        'is_locked',
        'lock_message',
        'active_broadcast',
        'internet_enabled',
        'last_ping_at',
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
            'is_locked' => 'boolean',
            'internet_enabled' => 'boolean',
            'last_ping_at' => 'datetime',
        ];
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function isOnline(): bool
    {
        return $this->last_ping_at !== null && $this->last_ping_at->isAfter(now()->subMinutes(2));
    }

    public function isComputer(): bool
    {
        return $this->device_type === 'computer' || ($this->lab?->isInformatics() ?? false);
    }

    public function lock(?string $message = null): void
    {
        $this->update([
            'is_locked' => true,
            'lock_message' => $message ?? __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.'),
        ]);
    }

    public function unlock(): void
    {
        $this->update([
            'is_locked' => false,
            'lock_message' => null,
        ]);
    }

    public function broadcast(?string $message): void
    {
        $this->update([
            'active_broadcast' => $message,
        ]);
    }
}
