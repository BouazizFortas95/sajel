<?php

namespace App\Http\Controllers;

use App\Models\LabSeatingPlanStation;
use App\Models\LabStation;
use App\Models\SchoolSetting;
use App\Services\NetStationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentStationController extends Controller
{
    public function show(Request $request, string $locale, LabStation $labStation, NetStationService $service): View
    {
        $labStation->load(['lab']);

        // Find assigned student from the most recent active seating plan station
        $planStation = LabSeatingPlanStation::with('student')
            ->where('lab_station_id', $labStation->id)
            ->latest('id')
            ->first();

        $student = $planStation?->student;

        // Record initial ping
        $service->pingStation($labStation);

        return view('lab.student-station', [
            'station' => $labStation,
            'lab' => $labStation->lab,
            'student' => $student,
            'schoolSetting' => SchoolSetting::current(),
            'pingUrl' => route('lab.station-ping', ['locale' => $locale, 'labStation' => $labStation]),
        ]);
    }

    public function ping(Request $request, string $locale, LabStation $labStation, NetStationService $service): JsonResponse
    {
        $state = $service->pingStation($labStation);

        return response()->json([
            'success' => true,
            'data' => $state,
        ]);
    }

    public function acknowledgeBroadcast(Request $request, string $locale, LabStation $labStation, NetStationService $service): JsonResponse
    {
        $service->clearBroadcast($labStation->lab_id, $labStation->id);

        return response()->json([
            'success' => true,
        ]);
    }
}
