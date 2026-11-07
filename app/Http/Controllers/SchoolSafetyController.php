<?php

namespace App\Http\Controllers;

use App\Models\SafetyEvacuationDrill;
use App\Models\SchoolSafetyIncident;
use App\Models\User;
use App\Services\SchoolSafetyService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolSafetyController extends Controller
{
    public function __construct(
        private readonly SchoolSafetyService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── 1. School Accident Official Declaration (A4 Portrait) ──────────

    public function printAccidentDeclaration(
        Request $request,
        string $locale,
        SchoolSafetyIncident $incident
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSafety(), 403);

        $data = $this->service->assembleAccidentDeclarationData($incident);

        return view('print.safety-accident-declaration', $data);
    }

    public function printAccidentDeclarationPdf(
        Request $request,
        string $locale,
        SchoolSafetyIncident $incident
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSafety(), 403);

        $data = $this->service->assembleAccidentDeclarationData($incident);
        $safeNumber = str_replace('/', '-', $incident->incident_number);
        $filename = "accident-declaration-{$safeNumber}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.safety-accident-declaration', $data)
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
            return response()->view('print.safety-accident-declaration', $data);
        }
    }

    // ─── 2. Evacuation Drill Official PV (A4 Landscape) ─────────────────

    public function printDrillPv(
        Request $request,
        string $locale,
        SafetyEvacuationDrill $drill
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSafety(), 403);

        $data = $this->service->assembleEvacuationDrillPvData($drill);

        return view('print.safety-evacuation-drill-pv', $data);
    }

    public function printDrillPvPdf(
        Request $request,
        string $locale,
        SafetyEvacuationDrill $drill
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSafety(), 403);

        $data = $this->service->assembleEvacuationDrillPvData($drill);
        $filename = "evacuation-drill-pv-{$drill->drill_code}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.safety-evacuation-drill-pv', $data)
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
            return response()->view('print.safety-evacuation-drill-pv', $data);
        }
    }

    // ─── 3. Fire Safety & Gas Equipment Register (A4 Landscape) ─────────

    public function printEquipmentRegister(
        Request $request,
        string $locale
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSafety(), 403);

        $filter = $request->query('filter');
        $data = $this->service->assembleSafetyEquipmentRegisterData($filter);

        return view('print.safety-equipment-register', $data);
    }

    public function printEquipmentRegisterPdf(
        Request $request,
        string $locale
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSafety(), 403);

        $filter = $request->query('filter');
        $data = $this->service->assembleSafetyEquipmentRegisterData($filter);
        $filename = 'safety-equipment-register.pdf';

        try {
            $pdf = $this->browsershot
                ->fromView('print.safety-equipment-register', $data)
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
            return response()->view('print.safety-equipment-register', $data);
        }
    }
}
