<?php

namespace App\Http\Controllers;

use App\Models\ParentsAssociationMeeting;
use App\Models\SchoolBoardSession;
use App\Models\SchoolProject;
use App\Models\User;
use App\Services\SchoolBoardGovernanceService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolBoardGovernanceController extends Controller
{
    public function __construct(
        private readonly SchoolBoardGovernanceService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── 1. Board Session Official PV Print (A4 Portrait) ───────────────

    public function printBoardSessionPv(
        Request $request,
        string $locale,
        SchoolBoardSession $session
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolGovernance(), 403);

        $data = $this->service->assembleBoardSessionPvData($session);

        return view('print.governance-board-session-pv', $data);
    }

    public function printBoardSessionPvPdf(
        Request $request,
        string $locale,
        SchoolBoardSession $session
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolGovernance(), 403);

        $data = $this->service->assembleBoardSessionPvData($session);
        $filename = "board-session-pv-{$session->session_code}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.governance-board-session-pv', $data)
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
            return response()->view('print.governance-board-session-pv', $data);
        }
    }

    // ─── 2. School Project Summary Print (A4 Landscape) ─────────────────

    public function printSchoolProjectSummary(
        Request $request,
        string $locale,
        SchoolProject $project
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolGovernance(), 403);

        $data = $this->service->assembleSchoolProjectSummaryData($project);

        return view('print.governance-school-project-summary', $data);
    }

    public function printSchoolProjectSummaryPdf(
        Request $request,
        string $locale,
        SchoolProject $project
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolGovernance(), 403);

        $data = $this->service->assembleSchoolProjectSummaryData($project);
        $filename = "school-project-{$project->project_code}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.governance-school-project-summary', $data)
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
            return response()->view('print.governance-school-project-summary', $data);
        }
    }

    // ─── 3. Parents Association Assembly PV Print (A4 Portrait) ─────────

    public function printParentsAssemblyPv(
        Request $request,
        string $locale,
        ParentsAssociationMeeting $meeting
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolGovernance(), 403);

        $data = $this->service->assembleParentsGeneralAssemblyPvData($meeting);

        return view('print.governance-parents-assembly-pv', $data);
    }

    public function printParentsAssemblyPvPdf(
        Request $request,
        string $locale,
        ParentsAssociationMeeting $meeting
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolGovernance(), 403);

        $data = $this->service->assembleParentsGeneralAssemblyPvData($meeting);
        $filename = "parents-meeting-pv-{$meeting->meeting_code}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.governance-parents-assembly-pv', $data)
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
            return response()->view('print.governance-parents-assembly-pv', $data);
        }
    }
}

