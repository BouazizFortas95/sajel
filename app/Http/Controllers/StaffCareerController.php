<?php

namespace App\Http\Controllers;

use App\Models\StaffEvaluation;
use App\Models\User;
use App\Services\StaffCareerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StaffCareerController extends Controller
{
    /**
     * Preview official Annual Staff Evaluation Bulletin (استمارة التنقيط والتقييم الإداري السنوي - A4 Portrait).
     */
    public function evaluationSheet(
        Request $request,
        string $locale,
        StaffEvaluation $evaluation,
        StaffCareerService $service
    ): View {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->canManageStaffCareer() || $user->id === $evaluation->user_id),
            403
        );

        $data = $service->assembleEvaluationSheetData($evaluation);

        return view('print.staff-evaluation-sheet', $data);
    }

    /**
     * Stream download Annual Staff Evaluation Bulletin as PDF.
     */
    public function evaluationSheetPdf(
        Request $request,
        string $locale,
        StaffEvaluation $evaluation,
        StaffCareerService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->canManageStaffCareer() || $user->id === $evaluation->user_id),
            403
        );

        $filename = sprintf('bulletin-notation-%d-%s.pdf', $evaluation->user_id, $evaluation->evaluation_year);

        try {
            $pdf = $service->generateEvaluationSheetPdf($evaluation);
            if ($pdf === null) {
                throw new \RuntimeException('Browsershot PDF generation failed');
            }

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                $filename,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "inline; filename=\"{$filename}\"",
                ]
            );
        } catch (Throwable) {
            return response()->streamDownload(
                function () use ($request, $locale, $evaluation, $service): void {
                    echo $this->evaluationSheet($request, $locale, $evaluation, $service)->render();
                },
                sprintf('bulletin-notation-%d-%s.html', $evaluation->user_id, $evaluation->evaluation_year),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Preview official Joint Administrative Committee (CAP) Advancement Grid (جدول الترقية في الدرجات السنوي - A4 Landscape).
     */
    public function advancementLedger(
        Request $request,
        string $locale,
        StaffCareerService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageStaffCareer(), 403);

        $year = (int) $request->query('year', (int) now()->year);
        $data = $service->assembleAdvancementLedgerData($year);

        return view('print.career-advancement-ledger', $data);
    }

    /**
     * Stream download Joint Administrative Committee (CAP) Advancement Grid as PDF.
     */
    public function advancementLedgerPdf(
        Request $request,
        string $locale,
        StaffCareerService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageStaffCareer(), 403);

        $year = (int) $request->query('year', (int) now()->year);
        $filename = sprintf('tableau-avancement-echelon-%d.pdf', $year);

        try {
            $pdf = $service->generateAdvancementLedgerPdf($year);
            if ($pdf === null) {
                throw new \RuntimeException('Browsershot PDF generation failed');
            }

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                $filename,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "inline; filename=\"{$filename}\"",
                ]
            );
        } catch (Throwable) {
            return response()->streamDownload(
                function () use ($request, $locale, $service): void {
                    echo $this->advancementLedger($request, $locale, $service)->render();
                },
                sprintf('tableau-avancement-echelon-%d.html', $year),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Export Joint Administrative Committee (CAP) Advancement Grid as CSV.
     */
    public function exportAdvancementCsv(
        Request $request,
        string $locale,
        StaffCareerService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageStaffCareer(), 403);

        $year = (int) $request->query('year', (int) now()->year);
        $filename = sprintf('tableau-avancements-echelons-%d.csv', $year);
        $csv = $service->generateAdvancementLedgerCsv($year);

        return response()->streamDownload(
            function () use ($csv): void {
                echo $csv;
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }
}

