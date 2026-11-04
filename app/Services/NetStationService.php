<?php

namespace App\Services;

use App\Models\Lab;
use App\Models\LabStation;

class NetStationService
{
    /**
     * Lock all working stations in the given lab.
     */
    public function lockAll(int $labId, ?string $message = null): int
    {
        $defaultMsg = $message ?: __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.');

        return LabStation::query()
            ->where('lab_id', $labId)
            ->update([
                'is_locked' => true,
                'lock_message' => $defaultMsg,
            ]);
    }

    /**
     * Unlock all stations in the given lab.
     */
    public function unlockAll(int $labId): int
    {
        return LabStation::query()
            ->where('lab_id', $labId)
            ->update([
                'is_locked' => false,
                'lock_message' => null,
            ]);
    }

    /**
     * Lock a single station with an optional message.
     */
    public function lockStation(int $stationId, ?string $message = null): void
    {
        $station = LabStation::findOrFail($stationId);
        $station->lock($message);
    }

    /**
     * Unlock a single station.
     */
    public function unlockStation(int $stationId): void
    {
        $station = LabStation::findOrFail($stationId);
        $station->unlock();
    }

    /**
     * Broadcast an announcement/message to lab stations.
     */
    public function broadcastMessage(int $labId, string $message, ?int $stationId = null): int
    {
        $query = LabStation::query()->where('lab_id', $labId);

        if ($stationId) {
            $query->where('id', $stationId);
        }

        return $query->update([
            'active_broadcast' => $message,
        ]);
    }

    /**
     * Clear active broadcast from stations.
     */
    public function clearBroadcast(int $labId, ?int $stationId = null): int
    {
        $query = LabStation::query()->where('lab_id', $labId);

        if ($stationId) {
            $query->where('id', $stationId);
        }

        return $query->update([
            'active_broadcast' => null,
        ]);
    }

    /**
     * Toggle internet access state for stations.
     */
    public function toggleInternet(int $labId, bool $enable, ?int $stationId = null): int
    {
        $query = LabStation::query()->where('lab_id', $labId);

        if ($stationId) {
            $query->where('id', $stationId);
        }

        return $query->update([
            'internet_enabled' => $enable,
        ]);
    }

    /**
     * Record a heartbeat from a student station and return current state.
     *
     * @return array<string, mixed>
     */
    public function pingStation(LabStation $station): array
    {
        $station->update(['last_ping_at' => now()]);

        return [
            'id' => $station->id,
            'station_number' => $station->station_number,
            'is_locked' => (bool) $station->is_locked,
            'lock_message' => $station->lock_message,
            'active_broadcast' => $station->active_broadcast,
            'internet_enabled' => (bool) $station->internet_enabled,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Compute live NetStation metrics for the leader cockpit.
     *
     * @return array<string, mixed>
     */
    public function getLabMetrics(int $labId): array
    {
        $lab = Lab::with('workingStations')->find($labId);

        if (! $lab) {
            return [
                'total_stations' => 0,
                'online_stations' => 0,
                'locked_stations' => 0,
                'internet_disabled_stations' => 0,
                'active_broadcast' => null,
            ];
        }

        $stations = $lab->workingStations;
        $total = $stations->count();
        $online = $stations->filter->isOnline()->count();
        $locked = $stations->where('is_locked', true)->count();
        $internetDisabled = $stations->where('internet_enabled', false)->count();
        $broadcast = $stations->whereNotNull('active_broadcast')->first()?->active_broadcast;

        return [
            'total_stations' => $total,
            'online_stations' => $online,
            'locked_stations' => $locked,
            'internet_disabled_stations' => $internetDisabled,
            'active_broadcast' => $broadcast,
        ];
    }
}
