<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SchoolCensusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolCensusController extends Controller
{
    /**
     * Preview Algerian School Year Opening Comprehensive Statistical Statement (October 15th Survey).
     */
    public function print(
        Request $request,
        string $locale,
        SchoolCensusService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $academicYear = $request->query('academic_year');
        $data = $service->assembleCensusReport($academicYear);

        return view('print.annual-school-census', $data);
    }

    /**
     * Download or stream School Year Opening Comprehensive Statistical Statement as PDF.
     */
    public function pdf(
        Request $request,
        string $locale,
        SchoolCensusService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $academicYear = $request->query('academic_year');
        $pdf = $service->generateCensusPdf($academicYear);
        $cleanYear = str_replace(['/', '\\'], '-', $service->resolveAcademicYear($academicYear));
        $filename = 'statistiques-rentree-scolaire-15-octobre-'.$cleanYear.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
