<?php

namespace App\Http\Controllers;

use App\Services\EvaluationWorkReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationWorkReceiptController extends Controller
{
    /**
     * Display printable evaluation work receipts document.
     */
    public function print(Request $request, string $locale, EvaluationWorkReceiptService $service): View|Response
    {
        $user = Auth::user();
        if (! $user || ! ($user->isLeadership() || $user->isAdmin() || $user->isPrincipal() || $user->isCensor() || $user->isDeveloper())) {
            abort(403, 'غير مصرح بالوصول إلى وثيقة تسليم الأعمال التقويمية.');
        }

        $subjectId = $request->query('subject_id') ? (int) $request->query('subject_id') : null;
        $academicYear = $request->query('academic_year');
        $layout = $request->query('layout', 'separate');

        $data = $service->assemblePrintData($subjectId, $academicYear, $layout);

        return view('print.evaluation-work-receipts', $data);
    }

    /**
     * Download or stream official PDF.
     */
    public function pdf(Request $request, string $locale, EvaluationWorkReceiptService $service): StreamedResponse|Response
    {
        $user = Auth::user();
        if (! $user || ! ($user->isLeadership() || $user->isAdmin() || $user->isPrincipal() || $user->isCensor() || $user->isDeveloper())) {
            abort(403, 'غير مصرح بتحميل وثيقة تسليم الأعمال التقويمية.');
        }

        $subjectId = $request->query('subject_id') ? (int) $request->query('subject_id') : null;
        $academicYear = $request->query('academic_year');
        $layout = $request->query('layout', 'separate');

        try {
            $pdf = $service->generatePdf($subjectId, $academicYear, $layout);
            $subjectSuffix = $subjectId ? '-subject-'.$subjectId : '-all-subjects';
            $filename = 'recepisse-travaux-evaluatifs'.$subjectSuffix.'-'.now()->format('Ymd').'.pdf';

            return response()->streamDownload(
                fn () => print ($pdf),
                $filename,
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Throwable $e) {
            // Fallback to direct print view if headless browser is not available
            $data = $service->assemblePrintData($subjectId, $academicYear, $layout);

            return view('print.evaluation-work-receipts', $data);
        }
    }

    /**
     * Download official Microsoft Word (.docx) document.
     */
    public function word(Request $request, string $locale, EvaluationWorkReceiptService $service): Response
    {
        $user = Auth::user();
        if (! $user || ! ($user->isLeadership() || $user->isAdmin() || $user->isPrincipal() || $user->isCensor() || $user->isDeveloper())) {
            abort(403, 'غير مصرح بتحميل وثيقة تسليم الأعمال التقويمية.');
        }

        $subjectId = $request->query('subject_id') ? (int) $request->query('subject_id') : null;
        $academicYear = $request->query('academic_year');
        $layout = $request->query('layout', 'separate');

        $docxFile = $service->generateWordDocx($subjectId, $academicYear, $layout);
        $subjectSuffix = $subjectId ? '-subject-'.$subjectId : '-all-subjects';
        $filename = 'recepisse-travaux-evaluatifs'.$subjectSuffix.'-'.now()->format('Ymd').'.docx';

        return response()->download($docxFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}

