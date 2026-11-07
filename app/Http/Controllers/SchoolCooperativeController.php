<?php

namespace App\Http\Controllers;

use App\Models\CooperativeProjectsInitiative;
use App\Models\EcoGreenClubAction;
use App\Models\PedagogicalGardenPlot;
use App\Models\User;
use App\Services\SchoolCooperativeService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolCooperativeController extends Controller
{
    public function __construct(
        private readonly SchoolCooperativeService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── 1. Cooperative Project Fiche (A4 Portrait) ───────────────────────

    public function printProjectFiche(
        Request $request,
        string $locale,
        CooperativeProjectsInitiative $project
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $data = $this->service->assembleCooperativeProjectFicheData($project);

        return view('print.cooperative-project-fiche', $data);
    }

    public function printProjectFichePdf(
        Request $request,
        string $locale,
        CooperativeProjectsInitiative $project
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $data = $this->service->assembleCooperativeProjectFicheData($project);
        $cleanCode = str_replace(['/', '\\'], '-', $project->project_code);
        $filename = "cooperative-project-{$cleanCode}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.cooperative-project-fiche', $data)
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
            return response()->view('print.cooperative-project-fiche', $data);
        }
    }

    // ─── 2. Pedagogical Garden Plot Fiche (A4 Portrait) ───────────────────

    public function printGardenPlotFiche(
        Request $request,
        string $locale,
        PedagogicalGardenPlot $plot
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $data = $this->service->assembleBotanicalGardenFicheData($plot);

        return view('print.cooperative-garden-plot-fiche', $data);
    }

    public function printGardenPlotFichePdf(
        Request $request,
        string $locale,
        PedagogicalGardenPlot $plot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $data = $this->service->assembleBotanicalGardenFicheData($plot);
        $cleanCode = str_replace(['/', '\\'], '-', $plot->plot_code);
        $filename = "garden-plot-fiche-{$cleanCode}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.cooperative-garden-plot-fiche', $data)
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
            return response()->view('print.cooperative-garden-plot-fiche', $data);
        }
    }

    // ─── 3. Eco-Action Green Club Report (A4 Portrait) ────────────────────

    public function printEcoActionReport(
        Request $request,
        string $locale,
        EcoGreenClubAction $action
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $data = $this->service->assembleEcoActionReportData($action);

        return view('print.cooperative-eco-action-report', $data);
    }

    public function printEcoActionReportPdf(
        Request $request,
        string $locale,
        EcoGreenClubAction $action
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $data = $this->service->assembleEcoActionReportData($action);
        $cleanCode = str_replace(['/', '\\'], '-', $action->action_code);
        $filename = "eco-action-report-{$cleanCode}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.cooperative-eco-action-report', $data)
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
            return response()->view('print.cooperative-eco-action-report', $data);
        }
    }

    // ─── 4. Annual Cooperative Bilan (A4 Landscape) ───────────────────────

    public function printAnnualBilan(
        Request $request,
        string $locale,
        string $academicYear
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $formattedYear = str_replace('-', '/', $academicYear);
        $data = $this->service->assembleCooperativeAnnualBilanData($formattedYear);

        return view('print.cooperative-annual-bilan', $data);
    }

    public function printAnnualBilanPdf(
        Request $request,
        string $locale,
        string $academicYear
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolCooperative(), 403);

        $formattedYear = str_replace('-', '/', $academicYear);
        $data = $this->service->assembleCooperativeAnnualBilanData($formattedYear);
        $cleanYear = str_replace(['/', '\\'], '-', $formattedYear);
        $filename = "cooperative-bilan-{$cleanYear}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.cooperative-annual-bilan', $data)
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
            return response()->view('print.cooperative-annual-bilan', $data);
        }
    }
}

