<?php

namespace App\Http\Controllers;

use App\Models\OfficialExamCenter;
use App\Models\OfficialExamIncident;
use App\Models\User;
use App\Services\OfficialExamCenterService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OfficialExamCenterController extends Controller
{
    public function __construct(
        private readonly OfficialExamCenterService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── Center Roster & Surveillance Plan (A4 Landscape) ───────

    public function printCenterRoster(
        Request $request,
        string $locale,
        OfficialExamCenter $center
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageOfficialExams(), 403);

        $data = $this->service->assembleCenterRosterData($center);

        return view('print.official-exam-center-roster', $data);
    }

    public function printCenterRosterPdf(
        Request $request,
        string $locale,
        OfficialExamCenter $center
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageOfficialExams(), 403);

        $data = $this->service->assembleCenterRosterData($center);
        $cleanCode = str_replace(['/', '\\'], '-', $center->center_code);
        $filename = "exam-center-roster-{$cleanCode}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.official-exam-center-roster', $data)
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
                function () use ($request, $locale, $center): void {
                    echo $this->printCenterRoster($request, $locale, $center)->render();
                },
                "exam-center-roster-{$cleanCode}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Envelope Opening PV (A4 Portrait) ──────────────────────

    public function printEnvelopePv(
        Request $request,
        string $locale,
        OfficialExamIncident $incident
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageOfficialExams(), 403);

        $data = $this->service->assembleEnvelopeOpeningPvData($incident);

        return view('print.official-exam-envelope-pv', $data);
    }

    public function printEnvelopePvPdf(
        Request $request,
        string $locale,
        OfficialExamIncident $incident
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageOfficialExams(), 403);

        $data = $this->service->assembleEnvelopeOpeningPvData($incident);
        $cleanRef = str_replace(['/', '\\'], '-', $incident->incident_number);
        $filename = "envelope-opening-pv-{$cleanRef}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.official-exam-envelope-pv', $data)
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
                function () use ($request, $locale, $incident): void {
                    echo $this->printEnvelopePv($request, $locale, $incident)->render();
                },
                "envelope-opening-pv-{$cleanRef}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Exam Incident / Cheating PV (A4 Portrait) ──────────────

    public function printIncidentPv(
        Request $request,
        string $locale,
        OfficialExamIncident $incident
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageOfficialExams(), 403);

        $data = $this->service->assembleIncidentPvData($incident);

        return view('print.official-exam-incident-pv', $data);
    }

    public function printIncidentPvPdf(
        Request $request,
        string $locale,
        OfficialExamIncident $incident
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageOfficialExams(), 403);

        $data = $this->service->assembleIncidentPvData($incident);
        $cleanRef = str_replace(['/', '\\'], '-', $incident->incident_number);
        $filename = "exam-incident-pv-{$cleanRef}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.official-exam-incident-pv', $data)
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
                function () use ($request, $locale, $incident): void {
                    echo $this->printIncidentPv($request, $locale, $incident)->render();
                },
                "exam-incident-pv-{$cleanRef}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
