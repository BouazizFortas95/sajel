<?php

namespace App\Services;

use App\Enums\OfficialExamDutyRole;
use App\Models\OfficialExamCenter;
use App\Models\OfficialExamIncident;
use App\Models\OfficialExamRoom;
use App\Models\OfficialExamSupervisor;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;

class OfficialExamCenterService
{
    /**
     * Create an official examination center.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCenter(array $data): OfficialExamCenter
    {
        if (empty($data['center_code'])) {
            $year = $data['session_year'] ?? (int) now()->year;
            $type = strtoupper((string) ($data['exam_type'] ?? 'bem'));
            $count = OfficialExamCenter::where('session_year', $year)->count() + 1;
            $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            $data['center_code'] = "CEN-{$type}-{$year}-{$seq}";
        }

        return OfficialExamCenter::create($data);
    }

    /**
     * Generate standard exam rooms with candidate numbering distribution.
     */
    public function generateRooms(
        OfficialExamCenter $center,
        int $numberOfRooms,
        int $candidatesPerRoom = 20,
        ?int $startRegistrationNumber = null
    ): void {
        DB::transaction(function () use ($center, $numberOfRooms, $candidatesPerRoom, $startRegistrationNumber) {
            $currentBaseNumber = $startRegistrationNumber ?? ((int) $center->session_year * 1000000 + 1001);

            for ($i = 1; $i <= $numberOfRooms; $i++) {
                $startNum = (string) $currentBaseNumber;
                $endNum = (string) ($currentBaseNumber + $candidatesPerRoom - 1);
                $currentBaseNumber += $candidatesPerRoom;

                OfficialExamRoom::updateOrCreate(
                    [
                        'center_id' => $center->id,
                        'room_number' => $i,
                    ],
                    [
                        'room_label' => 'القاعة رقم '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                        'candidate_start_number' => $startNum,
                        'candidate_end_number' => $endNum,
                        'candidate_count' => $candidatesPerRoom,
                        'supervisors_required' => 3,
                    ]
                );
            }

            $center->update([
                'room_count' => $center->rooms()->count(),
                'candidate_count' => $center->rooms()->sum('candidate_count'),
            ]);
        });
    }

    /**
     * Assign a supervisor to center / room.
     *
     * @param  array<string, mixed>  $data
     */
    public function assignSupervisor(array $data): OfficialExamSupervisor
    {
        return OfficialExamSupervisor::create($data);
    }

    /**
     * Generate sequential incident reference.
     */
    public function generateIncidentNumber(OfficialExamCenter $center): string
    {
        $type = strtoupper((string) $center->exam_type->value);
        $year = $center->session_year;
        $count = OfficialExamIncident::where('center_id', $center->id)->count() + 1;
        $seq = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "INC/{$type}/{$year}/{$seq}";
    }

    /**
     * Record an examination incident or official minutes.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordIncident(array $data): OfficialExamIncident
    {
        if (empty($data['incident_number'])) {
            $center = OfficialExamCenter::findOrFail($data['center_id']);
            $data['incident_number'] = $this->generateIncidentNumber($center);
        }

        return OfficialExamIncident::create($data);
    }

    /**
     * Calculate telemetry KPIs for center cockpit.
     *
     * @return array<string, mixed>
     */
    public function getCenterTelemetry(OfficialExamCenter $center): array
    {
        $center->loadMissing(['rooms', 'supervisors', 'incidents']);

        $totalRooms = $center->rooms->count();
        $totalCandidates = $center->rooms->sum('candidate_count');
        $totalSupervisors = $center->supervisors->count();

        $roomChiefsCount = $center->supervisors
            ->where('duty_role', OfficialExamDutyRole::RoomChief)
            ->count();

        $roomSupervisorsCount = $center->supervisors
            ->where('duty_role', OfficialExamDutyRole::RoomSupervisor)
            ->count();

        $corridorGuardsCount = $center->supervisors
            ->where('duty_role', OfficialExamDutyRole::CorridorGuard)
            ->count();

        $secretariatCount = $center->supervisors
            ->where('duty_role', OfficialExamDutyRole::Secretariat)
            ->count();

        $incidentsCount = $center->incidents->count();

        $requiredSupervisors = $totalRooms * 3;
        $coveragePercentage = $requiredSupervisors > 0
            ? min(100.0, round(($roomSupervisorsCount + $roomChiefsCount) / $requiredSupervisors * 100, 1))
            : 0.0;

        return [
            'totalRooms' => $totalRooms,
            'totalCandidates' => $totalCandidates,
            'totalSupervisors' => $totalSupervisors,
            'roomChiefsCount' => $roomChiefsCount,
            'roomSupervisorsCount' => $roomSupervisorsCount,
            'corridorGuardsCount' => $corridorGuardsCount,
            'secretariatCount' => $secretariatCount,
            'incidentsCount' => $incidentsCount,
            'coveragePercentage' => $coveragePercentage,
        ];
    }

    /**
     * Assemble printable data for Center General Roster (A4 Landscape).
     *
     * @return array<string, mixed>
     */
    public function assembleCenterRosterData(OfficialExamCenter $center): array
    {
        $center->loadMissing(['rooms.supervisors', 'supervisors.room', 'headUser']);

        $rooms = $center->rooms;
        $supervisors = $center->supervisors;

        return [
            'center' => $center,
            'rooms' => $rooms,
            'supervisors' => $supervisors,
            'school' => SchoolSetting::current(),
            'telemetry' => $this->getCenterTelemetry($center),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Envelope Opening PV (A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assembleEnvelopeOpeningPvData(OfficialExamIncident $incident): array
    {
        $incident->loadMissing(['center.headUser', 'room.supervisors', 'reporter']);

        return [
            'incident' => $incident,
            'center' => $incident->center,
            'room' => $incident->room,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }

    /**
     * Assemble printable data for Exam Incident / Cheating PV (A4 Portrait).
     *
     * @return array<string, mixed>
     */
    public function assembleIncidentPvData(OfficialExamIncident $incident): array
    {
        $incident->loadMissing(['center.headUser', 'room', 'reporter']);

        return [
            'incident' => $incident,
            'center' => $incident->center,
            'room' => $incident->room,
            'school' => SchoolSetting::current(),
            'generatedAt' => now(config('app.timezone', 'Africa/Algiers')),
        ];
    }
}
