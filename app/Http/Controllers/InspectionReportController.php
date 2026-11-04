<?php

namespace App\Http\Controllers;

use App\Models\InspectionReport;
use App\Models\SchoolSetting;
use App\Services\PedagogicalInspectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InspectionReportController extends Controller
{
    public function print(
        Request $request,
        string $locale,
        InspectionReport $inspectionReport,
        PedagogicalInspectionService $service,
    ): View {
        Gate::authorize('view', $inspectionReport);

        return view('print.inspection-report', [
            'report' => $inspectionReport->load(['teacher', 'subject', 'classroom', 'schedule', 'dailyLog', 'curriculumResource']),
            'schoolSetting' => SchoolSetting::current(),
            'generatedAt' => now(),
            'autoPrint' => true,
        ]);
    }

    public function pdf(
        Request $request,
        string $locale,
        InspectionReport $inspectionReport,
        PedagogicalInspectionService $service,
    ): StreamedResponse {
        Gate::authorize('view', $inspectionReport);

        $pdf = $service->generatePdf($inspectionReport);
        $filename = 'inspection-report-'.$inspectionReport->id.'-'.$inspectionReport->teacher->name.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
