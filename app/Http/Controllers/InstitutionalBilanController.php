<?php

namespace App\Http\Controllers;

use App\Models\QuarterlyInstitutionalReport;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\InstitutionalBilanService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstitutionalBilanController extends Controller
{
    /**
     * Preview Quarterly Institutional Assessment (Bilan Trimestriel) in browser.
     */
    public function print(
        Request $request,
        string $locale,
        InstitutionalBilanService $service,
        ?QuarterlyInstitutionalReport $report = null
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        if (! $report || ! $report->exists) {
            $year = $request->query('academic_year');
            $trimester = (int) $request->query('trimester', 1);
            $report = $service->getOrCreateReport($year, $trimester);
        }

        return view('print.annual-institutional-bilan', [
            'report' => $report,
            'schoolSetting' => SchoolSetting::current(),
            'inlineCss' => false,
        ]);
    }

    /**
     * Download or stream Quarterly Institutional Assessment as PDF.
     */
    public function pdf(
        Request $request,
        string $locale,
        InstitutionalBilanService $service,
        ?QuarterlyInstitutionalReport $report = null
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        if (! $report || ! $report->exists) {
            $year = $request->query('academic_year');
            $trimester = (int) $request->query('trimester', 1);
            $report = $service->getOrCreateReport($year, $trimester);
        }

        $pdf = $service->generateBilanPdf($report);
        $cleanYear = str_replace(['/', '\\'], '-', $report->academic_year);
        $filename = 'bilan-trimestriel-t'.$report->trimester.'-'.$cleanYear.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
