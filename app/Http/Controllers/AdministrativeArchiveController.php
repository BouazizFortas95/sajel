<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeHandover;
use App\Models\OfficialMail;
use App\Models\StaffInstallation;
use App\Services\AdministrativeArchiveService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AdministrativeArchiveController extends Controller
{
    public function __construct(
        private readonly AdministrativeArchiveService $service
    ) {}

    // ─── Staff Installation Print ───────────────────────────────

    public function printInstallationSheet(StaffInstallation $installation): Response
    {
        abort_unless(Auth::user()?->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleInstallationSheetData($installation);

        return response()->view('print.staff-installation-sheet', $data);
    }

    public function printInstallationSheetPdf(StaffInstallation $installation): Response
    {
        abort_unless(Auth::user()?->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleInstallationSheetData($installation);

        $pdf = app(\App\Services\BrowsershotFactory::class)
            ->fromView('print.staff-installation-sheet', $data)
            ->portrait()
            ->generate();

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"installation-{$installation->id}.pdf\"",
        ]);
    }

    // ─── Handover Print ──────────────────────────────────────────

    public function printHandoverSheet(AdministrativeHandover $handover): Response
    {
        abort_unless(Auth::user()?->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleHandoverSheetData($handover);

        return response()->view('print.administrative-handover-sheet', $data);
    }

    public function printHandoverSheetPdf(AdministrativeHandover $handover): Response
    {
        abort_unless(Auth::user()?->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleHandoverSheetData($handover);

        $pdf = app(\App\Services\BrowsershotFactory::class)
            ->fromView('print.administrative-handover-sheet', $data)
            ->portrait()
            ->generate();

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"handover-{$handover->id}.pdf\"",
        ]);
    }

    // ─── Official Mail Ledger Print ──────────────────────────────

    public function printMailLedger(string $type, string $year): Response
    {
        abort_unless(Auth::user()?->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleMailLedgerData($type, (int) $year);

        return response()->view('print.official-mail-ledger', $data);
    }

    public function printMailLedgerPdf(string $type, string $year): Response
    {
        abort_unless(Auth::user()?->canManageAdministrativeArchive(), 403);

        $data = $this->service->assembleMailLedgerData($type, (int) $year);

        $pdf = app(\App\Services\BrowsershotFactory::class)
            ->fromView('print.official-mail-ledger', $data)
            ->landscape()
            ->generate();

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"mail-ledger-{$type}-{$year}.pdf\"",
        ]);
    }
}
