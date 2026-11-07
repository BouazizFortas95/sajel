<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeHandover;
use App\Models\StaffInstallation;
use App\Models\User;
use App\Services\AdministrativeArchiveService;
use App\Services\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdministrativeArchiveController extends Controller
{
    public function __construct(
        private readonly AdministrativeArchiveService $service
    ) {}

    // ─── Staff Installation Print ───────────────────────────────

    public function printInstallationSheet(
        Request $request,
        string $locale,
        StaffInstallation $installation
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleInstallationSheetData($installation);

        return view('print.staff-installation-sheet', $data);
    }

    public function printInstallationSheetPdf(
        Request $request,
        string $locale,
        StaffInstallation $installation,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageAdministrativeArchive(), 403);

        $filename = "installation-{$installation->id}.pdf";
        $data = $this->service->assembleInstallationSheetData($installation);

        try {
            $pdf = $browsershot
                ->fromView('print.staff-installation-sheet', $data)
                ->portrait()
                ->generate();

            if (! $pdf) {
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
                function () use ($request, $locale, $installation): void {
                    echo $this->printInstallationSheet($request, $locale, $installation)->render();
                },
                "installation-{$installation->id}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Handover Print ──────────────────────────────────────────

    public function printHandoverSheet(
        Request $request,
        string $locale,
        AdministrativeHandover $handover
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleHandoverSheetData($handover);

        return view('print.administrative-handover-sheet', $data);
    }

    public function printHandoverSheetPdf(
        Request $request,
        string $locale,
        AdministrativeHandover $handover,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageAdministrativeArchive(), 403);

        $filename = "handover-{$handover->id}.pdf";
        $data = $this->service->assembleHandoverSheetData($handover);

        try {
            $pdf = $browsershot
                ->fromView('print.administrative-handover-sheet', $data)
                ->portrait()
                ->generate();

            if (! $pdf) {
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
                function () use ($request, $locale, $handover): void {
                    echo $this->printHandoverSheet($request, $locale, $handover)->render();
                },
                "handover-{$handover->id}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Official Mail Ledger Print ──────────────────────────────

    public function printMailLedger(
        Request $request,
        string $locale,
        string $type,
        string $year
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleMailLedgerData($type, (int) $year);

        return view('print.official-mail-ledger', $data);
    }

    public function printMailLedgerPdf(
        Request $request,
        string $locale,
        string $type,
        string $year,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageAdministrativeArchive(), 403);

        $filename = "mail-ledger-{$type}-{$year}.pdf";
        $data = $this->service->assembleMailLedgerData($type, (int) $year);

        try {
            $pdf = $browsershot
                ->fromView('print.official-mail-ledger', $data)
                ->landscape()
                ->generate();

            if (! $pdf) {
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
                function () use ($request, $locale, $type, $year): void {
                    echo $this->printMailLedger($request, $locale, $type, $year)->render();
                },
                "mail-ledger-{$type}-{$year}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
