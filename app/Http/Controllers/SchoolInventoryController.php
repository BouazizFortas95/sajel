<?php

namespace App\Http\Controllers;

use App\Models\InventoryAllocation;
use App\Models\InventoryRecolement;
use App\Models\InventoryReform;
use App\Models\User;
use App\Services\SchoolInventoryService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolInventoryController extends Controller
{
    public function __construct(
        private readonly SchoolInventoryService $service
    ) {}

    // ─── General and Specific Inventory Ledger Print ────────────

    public function printGeneralLedger(
        Request $request,
        string $locale,
        ?string $category = null
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $locationType = $request->query('location_type');
        $data = $this->service->assembleGeneralLedgerData($category, $locationType);

        return view('print.inventory-general-ledger', $data);
    }

    public function printGeneralLedgerPdf(
        Request $request,
        string $locale,
        ?string $category,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $locationType = $request->query('location_type');
        $data = $this->service->assembleGeneralLedgerData($category, $locationType);
        $filename = 'inventory-ledger-'.($category ?? 'all').'.pdf';

        try {
            $pdf = $browsershot
                ->fromView('print.inventory-general-ledger', $data)
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
                function () use ($request, $locale, $category): void {
                    echo $this->printGeneralLedger($request, $locale, $category)->render();
                },
                'inventory-ledger-'.($category ?? 'all').'.html',
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Room Allocation Card Print ─────────────────────────────

    public function printAllocationSheet(
        Request $request,
        string $locale,
        InventoryAllocation $allocation
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $data = $this->service->assembleAllocationSheetData($allocation);

        return view('print.inventory-allocation-sheet', $data);
    }

    public function printAllocationSheetPdf(
        Request $request,
        string $locale,
        InventoryAllocation $allocation,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $filename = "inventory-allocation-{$allocation->id}.pdf";
        $data = $this->service->assembleAllocationSheetData($allocation);

        try {
            $pdf = $browsershot
                ->fromView('print.inventory-allocation-sheet', $data)
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
                function () use ($request, $locale, $allocation): void {
                    echo $this->printAllocationSheet($request, $locale, $allocation)->render();
                },
                "inventory-allocation-{$allocation->id}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Reform Minutes Print ───────────────────────────────────

    public function printReformPv(
        Request $request,
        string $locale,
        InventoryReform $reform
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $data = $this->service->assembleReformPvData($reform);

        return view('print.inventory-reform-pv', $data);
    }

    public function printReformPvPdf(
        Request $request,
        string $locale,
        InventoryReform $reform,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $filename = "inventory-reform-{$reform->id}.pdf";
        $data = $this->service->assembleReformPvData($reform);

        try {
            $pdf = $browsershot
                ->fromView('print.inventory-reform-pv', $data)
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
                function () use ($request, $locale, $reform): void {
                    echo $this->printReformPv($request, $locale, $reform)->render();
                },
                "inventory-reform-{$reform->id}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Custody Handover Voucher Print ─────────────────────────

    public function printCustodyVoucher(
        Request $request,
        string $locale,
        InventoryAllocation $allocation
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $data = $this->service->assembleCustodyVoucherData($allocation);

        return view('print.inventory-custody-voucher', $data);
    }

    public function printCustodyVoucherPdf(
        Request $request,
        string $locale,
        InventoryAllocation $allocation,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $filename = "inventory-custody-{$allocation->id}.pdf";
        $data = $this->service->assembleCustodyVoucherData($allocation);

        try {
            $pdf = $browsershot
                ->fromView('print.inventory-custody-voucher', $data)
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
                function () use ($request, $locale, $allocation): void {
                    echo $this->printCustodyVoucher($request, $locale, $allocation)->render();
                },
                "inventory-custody-{$allocation->id}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Periodic Recolement & Verification Print ───────────────

    public function printRecolement(
        Request $request,
        string $locale,
        InventoryRecolement $recolement
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $data = $this->service->assembleRecolementData($recolement);

        return view('print.inventory-recolement-sheet', $data);
    }

    public function printRecolementPdf(
        Request $request,
        string $locale,
        InventoryRecolement $recolement,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolInventory(), 403);

        $filename = "inventory-recolement-{$recolement->id}.pdf";
        $data = $this->service->assembleRecolementData($recolement);

        try {
            $pdf = $browsershot
                ->fromView('print.inventory-recolement-sheet', $data)
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
                function () use ($request, $locale, $recolement): void {
                    echo $this->printRecolement($request, $locale, $recolement)->render();
                },
                "inventory-recolement-{$recolement->id}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
