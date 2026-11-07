<?php

namespace App\Http\Controllers;

use App\Models\ClassCouncilSession;
use App\Models\SubjectCoordinationProtocol;
use App\Models\User;
use App\Services\BrowsershotFactory;
use App\Services\ClassCouncilDeliberationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ClassCouncilDeliberationController extends Controller
{
    public function __construct(
        private readonly ClassCouncilDeliberationService $service
    ) {}

    // ─── Class Council PV Print ─────────────────────────────────

    public function printSessionPv(
        Request $request,
        string $locale,
        ClassCouncilSession $session
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageClassCouncils(), 403);

        $data = $this->service->assembleClassCouncilPvData($session);

        return view('print.class-council-pv', $data);
    }

    public function printSessionPvPdf(
        Request $request,
        string $locale,
        ClassCouncilSession $session,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageClassCouncils(), 403);

        $filename = "class-council-pv-{$session->classroom_id}-t{$session->trimester}.pdf";
        $data = $this->service->assembleClassCouncilPvData($session);

        try {
            $pdf = $browsershot
                ->fromView('print.class-council-pv', $data)
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
                function () use ($request, $locale, $session): void {
                    echo $this->printSessionPv($request, $locale, $session)->render();
                },
                "class-council-pv-{$session->classroom_id}-t{$session->trimester}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Subject Coordination PV Print ──────────────────────────

    public function printCoordinationPv(
        Request $request,
        string $locale,
        SubjectCoordinationProtocol $protocol
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManagePedagogicalCouncils(), 403);

        $data = $this->service->assembleCoordinationPvData($protocol);

        return view('print.subject-coordination-pv', $data);
    }

    public function printCoordinationPvPdf(
        Request $request,
        string $locale,
        SubjectCoordinationProtocol $protocol,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManagePedagogicalCouncils(), 403);

        $filename = "coordination-pv-{$protocol->subject_id}-t{$protocol->trimester}.pdf";
        $data = $this->service->assembleCoordinationPvData($protocol);

        try {
            $pdf = $browsershot
                ->fromView('print.subject-coordination-pv', $data)
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
                function () use ($request, $locale, $protocol): void {
                    echo $this->printCoordinationPv($request, $locale, $protocol)->render();
                },
                "coordination-pv-{$protocol->subject_id}-t{$protocol->trimester}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Curriculum Pacing Audit Print ──────────────────────────

    public function printPacingAudit(
        Request $request,
        string $locale,
        string $year,
        string $trimester
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManagePedagogicalCouncils(), 403);

        $levelId = $request->query('level_id') ? (int) $request->query('level_id') : null;
        $decodedYear = str_replace('-', '/', $year);
        $data = $this->service->assemblePacingAuditData($decodedYear, (int) $trimester, $levelId);

        return view('print.curriculum-pacing-audit-sheet', $data);
    }

    public function printPacingAuditPdf(
        Request $request,
        string $locale,
        string $year,
        string $trimester,
        BrowsershotFactory $browsershot
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManagePedagogicalCouncils(), 403);

        $levelId = $request->query('level_id') ? (int) $request->query('level_id') : null;
        $decodedYear = str_replace('-', '/', $year);
        $data = $this->service->assemblePacingAuditData($decodedYear, (int) $trimester, $levelId);
        $filename = "curriculum-pacing-audit-{$year}-t{$trimester}.pdf";

        try {
            $pdf = $browsershot
                ->fromView('print.curriculum-pacing-audit-sheet', $data)
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
                function () use ($request, $locale, $year, $trimester): void {
                    echo $this->printPacingAudit($request, $locale, $year, $trimester)->render();
                },
                "curriculum-pacing-audit-{$year}-t{$trimester}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
