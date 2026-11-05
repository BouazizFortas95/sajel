<?php

namespace App\Http\Controllers;

use App\Enums\TransferType;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\StudentMobilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentMobilityController extends Controller
{
    /**
     * Print official student transfer certificate (A4).
     */
    public function certificate(
        Request $request,
        string $locale,
        StudentTransfer $transfer,
        StudentMobilityService $service
    ): View {
        $user = $request->user();
        $student = $transfer->student;

        abort_unless(
            $user instanceof User && ($user->isAdmin() || $student?->group?->classroom?->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        $data = $service->assembleTransferCertificate($transfer);

        return view('print.student-transfer-certificate', $data);
    }

    /**
     * Stream download student transfer certificate as PDF.
     */
    public function certificatePdf(
        Request $request,
        string $locale,
        StudentTransfer $transfer,
        StudentMobilityService $service
    ): StreamedResponse {
        $user = $request->user();
        $student = $transfer->student;

        abort_unless(
            $user instanceof User && ($user->isAdmin() || $student?->group?->classroom?->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        try {
            $pdf = $service->generateTransferCertificatePdf($transfer);
            $filename = sprintf('certificat-transfert-student-%d.pdf', $student?->id ?? $transfer->id);

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
                function () use ($request, $locale, $transfer, $service): void {
                    echo $this->certificate($request, $locale, $transfer, $service)->render();
                },
                sprintf('certificat-transfert-student-%d.html', $student?->id ?? $transfer->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Print official student mobility and movement ledger (A4 Landscape).
     */
    public function ledger(
        Request $request,
        string $locale,
        StudentMobilityService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $year = $request->query('year');
        $typeParam = $request->query('type');
        $type = $typeParam ? TransferType::tryFrom($typeParam) : null;

        $data = $service->assembleMobilityLedger($year, $type);

        return view('print.student-mobility-ledger', $data);
    }

    /**
     * Stream download mobility ledger as PDF.
     */
    public function ledgerPdf(
        Request $request,
        string $locale,
        StudentMobilityService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $year = $request->query('year');
        $typeParam = $request->query('type');
        $type = $typeParam ? TransferType::tryFrom($typeParam) : null;

        try {
            $pdf = $service->generateMobilityLedgerPdf($year, $type);
            $filename = 'registre-mouvements-eleves.pdf';

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
                    echo $this->ledger($request, $locale, $service)->render();
                },
                'registre-mouvements-eleves.html',
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Print school file forwarding slip (A4 Portrait).
     */
    public function forwardingSlip(
        Request $request,
        string $locale,
        StudentTransfer $transfer,
        StudentMobilityService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $data = $service->assembleForwardingSlip($transfer);

        return view('print.school-file-forwarding-slip', $data);
    }

    /**
     * Stream download school file forwarding slip as PDF.
     */
    public function forwardingSlipPdf(
        Request $request,
        string $locale,
        StudentTransfer $transfer,
        StudentMobilityService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        try {
            $pdf = $service->generateForwardingSlipPdf($transfer);
            $filename = sprintf('bordereau-envoi-dossier-%d.pdf', $transfer->id);

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
                function () use ($request, $locale, $transfer, $service): void {
                    echo $this->forwardingSlip($request, $locale, $transfer, $service)->render();
                },
                sprintf('bordereau-envoi-dossier-%d.html', $transfer->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}

