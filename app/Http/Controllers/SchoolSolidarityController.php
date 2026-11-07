<?php

namespace App\Http\Controllers;

use App\Models\SchoolSolidarityAidDistribution;
use App\Models\SchoolSolidarityCampaign;
use App\Models\User;
use App\Services\SchoolSolidarityService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolSolidarityController extends Controller
{
    public function __construct(
        private readonly SchoolSolidarityService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── 1. Nominative CCP List (A4 Landscape) ──────────────────────────

    public function printNominativeList(
        Request $request,
        string $locale,
        SchoolSolidarityCampaign $campaign
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSolidarity(), 403);

        $data = $this->service->assembleNominativeListData($campaign);

        return view('print.solidarity-nominative-list', $data);
    }

    public function printNominativeListPdf(
        Request $request,
        string $locale,
        SchoolSolidarityCampaign $campaign
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSolidarity(), 403);

        $data = $this->service->assembleNominativeListData($campaign);
        $filename = "solidarity-nominative-list-{$campaign->campaign_year}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.solidarity-nominative-list', $data)
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
            return response()
                ->view('print.solidarity-nominative-list', $data)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    // ─── 2. Institutional Committee Deliberation PV (A4 Portrait) ───────

    public function printCommitteePv(
        Request $request,
        string $locale,
        SchoolSolidarityCampaign $campaign
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSolidarity(), 403);

        $data = $this->service->assembleCommitteePvData($campaign);

        return view('print.solidarity-committee-pv', $data);
    }

    public function printCommitteePvPdf(
        Request $request,
        string $locale,
        SchoolSolidarityCampaign $campaign
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSolidarity(), 403);

        $data = $this->service->assembleCommitteePvData($campaign);
        $filename = "solidarity-committee-pv-{$campaign->campaign_year}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.solidarity-committee-pv', $data)
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
            return response()
                ->view('print.solidarity-committee-pv', $data)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    // ─── 3. In-Kind Aid Delivery Voucher (A4 Portrait) ───────────────────

    public function printAidVoucher(
        Request $request,
        string $locale,
        SchoolSolidarityAidDistribution $distribution
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSolidarity(), 403);

        $data = $this->service->assembleAidVoucherData($distribution);

        return view('print.solidarity-aid-voucher', $data);
    }

    public function printAidVoucherPdf(
        Request $request,
        string $locale,
        SchoolSolidarityAidDistribution $distribution
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSolidarity(), 403);

        $data = $this->service->assembleAidVoucherData($distribution);
        $safeNumber = str_replace('/', '-', $distribution->voucher_number);
        $filename = "solidarity-aid-voucher-{$safeNumber}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.solidarity-aid-voucher', $data)
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
            return response()
                ->view('print.solidarity-aid-voucher', $data)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }
    }
}
