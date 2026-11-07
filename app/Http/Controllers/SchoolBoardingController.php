<?php

namespace App\Http\Controllers;

use App\Models\BoardingExitPass;
use App\Models\DormitoryRoom;
use App\Models\StudentBusSubscription;
use App\Models\User;
use App\Services\SchoolBoardingService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolBoardingController extends Controller
{
    public function __construct(
        private readonly SchoolBoardingService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── 1. Dormitory Room Roster Print (A4 Landscape) ───────────────────

    public function printRoomRoster(
        Request $request,
        string $locale,
        DormitoryRoom $room
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageBoarding(), 403);

        $data = $this->service->assembleDormitoryRosterData($room);

        return view('print.dormitory-room-roster', $data);
    }

    public function printRoomRosterPdf(
        Request $request,
        string $locale,
        DormitoryRoom $room
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageBoarding(), 403);

        $data = $this->service->assembleDormitoryRosterData($room);
        $filename = "dormitory-roster-{$room->room_code}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.dormitory-room-roster', $data)
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
                ->view('print.dormitory-room-roster', $data)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    // ─── 2. Boarding Exit Pass Print (A4 Portrait) ──────────────────────

    public function printExitPass(
        Request $request,
        string $locale,
        BoardingExitPass $pass
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageBoarding(), 403);

        $data = $this->service->assembleExitPassData($pass);

        return view('print.boarding-exit-pass', $data);
    }

    public function printExitPassPdf(
        Request $request,
        string $locale,
        BoardingExitPass $pass
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageBoarding(), 403);

        $data = $this->service->assembleExitPassData($pass);
        $safeNumber = str_replace('/', '-', $pass->pass_number);
        $filename = "exit-pass-{$safeNumber}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.boarding-exit-pass', $data)
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
                ->view('print.boarding-exit-pass', $data)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    // ─── 3. Student Bus Subscription Card Print ─────────────────────────

    public function printBusCard(
        Request $request,
        string $locale,
        StudentBusSubscription $subscription
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageBoarding(), 403);

        $data = $this->service->assembleBusCardData($subscription);

        return view('print.student-bus-card', $data);
    }

    public function printBusCardPdf(
        Request $request,
        string $locale,
        StudentBusSubscription $subscription
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageBoarding(), 403);

        $data = $this->service->assembleBusCardData($subscription);
        $safeNumber = str_replace('/', '-', $subscription->card_number);
        $filename = "bus-card-{$safeNumber}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.student-bus-card', $data)
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
                ->view('print.student-bus-card', $data)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }
    }
}
