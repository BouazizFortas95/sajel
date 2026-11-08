<?php

namespace App\Http\Controllers;

use App\Services\PedagogicalPlanReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PedagogicalPlanReceiptController extends Controller
{
    /**
     * Display printable 2-page document.
     */
    public function print(Request $request, string $locale, PedagogicalPlanReceiptService $service): View|Response
    {
        $user = Auth::user();
        if (! $user || ! ($user->isLeadership() || $user->isAdmin() || $user->isPrincipal() || $user->isCensor() || $user->isDeveloper())) {
            abort(403, 'غير مصرح بالوصول إلى وثيقة استلام المخططات والتدرجات البيداغوجية.');
        }

        $subjectId = $request->query('subject_id') ? (int) $request->query('subject_id') : null;
        $academicYear = $request->query('academic_year');

        $data = $service->assemblePrintData($subjectId, $academicYear);

        return view('print.pedagogical-plan-receipts', $data);
    }

    /**
     * Download or stream official PDF.
     */
    public function pdf(Request $request, string $locale, PedagogicalPlanReceiptService $service): StreamedResponse|Response
    {
        $user = Auth::user();
        if (! $user || ! ($user->isLeadership() || $user->isAdmin() || $user->isPrincipal() || $user->isCensor() || $user->isDeveloper())) {
            abort(403, 'غير مصرح بتحميل وثيقة استلام المخططات والتدرجات البيداغوجية.');
        }

        $subjectId = $request->query('subject_id') ? (int) $request->query('subject_id') : null;
        $academicYear = $request->query('academic_year');

        try {
            $pdf = $service->generatePdf($subjectId, $academicYear);
            $filename = 'recepisse-plans-pedagogiques-'.now()->format('Ymd').'.pdf';

            return response()->streamDownload(
                fn () => print ($pdf),
                $filename,
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Throwable $e) {
            // Fallback to direct print view if headless browser is not available in environment
            $data = $service->assemblePrintData($subjectId, $academicYear);

            return view('print.pedagogical-plan-receipts', $data);
        }
    }
}
