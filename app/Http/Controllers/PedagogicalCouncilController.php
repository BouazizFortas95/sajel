<?php

namespace App\Http\Controllers;

use App\Models\PedagogicalCouncil;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalCouncilService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PedagogicalCouncilController extends Controller
{
    /**
     * Preview official Minutes of Subject Council (محضر مجلس التعليم للمادة).
     */
    public function councilPv(
        Request $request,
        string $locale,
        PedagogicalCouncil $council,
        PedagogicalCouncilService $service
    ): View {
        $user = $request->user();
        abort_unless(
            $user instanceof User && (
                $user->isAdmin() ||
                $user->isEducationStaff() ||
                $user->id === $council->coordinator_id ||
                $council->attendances()->where('user_id', $user->id)->exists() ||
                ($user->isTeacher() && in_array($council->subject_id, array_filter([$user->primary_subject_id, $user->secondary_subject_id])))
            ),
            403
        );

        $data = $service->assembleCouncilPvData($council);

        return view('print.pedagogical-council-pv', $data);
    }

    /**
     * Stream download Council Minutes as PDF.
     */
    public function councilPvPdf(
        Request $request,
        string $locale,
        PedagogicalCouncil $council,
        PedagogicalCouncilService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && (
                $user->isAdmin() ||
                $user->isEducationStaff() ||
                $user->id === $council->coordinator_id ||
                $council->attendances()->where('user_id', $user->id)->exists() ||
                ($user->isTeacher() && in_array($council->subject_id, array_filter([$user->primary_subject_id, $user->secondary_subject_id])))
            ),
            403
        );

        try {
            $pdf = $service->generateCouncilPvPdf($council);
            $filename = sprintf('pv-conseil-pedagogique-%d.pdf', $council->id);

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
                function () use ($request, $locale, $council, $service): void {
                    echo $this->councilPv($request, $locale, $council, $service)->render();
                },
                sprintf('pv-conseil-pedagogique-%d.html', $council->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Preview Curriculum Pacing Audit and Progress Ledger.
     */
    public function curriculumPacing(
        Request $request,
        string $locale,
        PedagogicalCouncilService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isEducationStaff() || $user->isTeacher()), 403);

        $year = $request->query('year');
        $subjectId = $request->query('subject') ? (int) $request->query('subject') : null;

        $data = $service->assembleCurriculumPacingData($year, $subjectId);

        return view('print.curriculum-pacing-ledger', $data);
    }

    /**
     * Stream download Curriculum Pacing Ledger as PDF.
     */
    public function curriculumPacingPdf(
        Request $request,
        string $locale,
        PedagogicalCouncilService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isEducationStaff() || $user->isTeacher()), 403);

        $year = $request->query('year');
        $subjectId = $request->query('subject') ? (int) $request->query('subject') : null;

        try {
            $pdf = $service->generateCurriculumPacingPdf($year, $subjectId);
            $filename = 'curriculum-pacing-ledger.pdf';

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
                function () use ($request, $locale, $service): void {
                    echo $this->curriculumPacing($request, $locale, $service)->render();
                },
                'curriculum-pacing-ledger.html',
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Preview Pedagogical Seminars and Training Calendar.
     */
    public function seminarCalendar(
        Request $request,
        string $locale,
        PedagogicalCouncilService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isEducationStaff() || $user->isTeacher()), 403);

        $year = $request->query('year');
        $subjectId = $request->query('subject') ? (int) $request->query('subject') : null;

        $data = $service->assembleSeminarCalendarData($year, $subjectId);

        return view('print.pedagogical-seminars-calendar', $data);
    }

    /**
     * Stream download Seminars Calendar as PDF.
     */
    public function seminarCalendarPdf(
        Request $request,
        string $locale,
        PedagogicalCouncilService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isEducationStaff() || $user->isTeacher()), 403);

        $year = $request->query('year');
        $subjectId = $request->query('subject') ? (int) $request->query('subject') : null;

        try {
            $pdf = $service->generateSeminarCalendarPdf($year, $subjectId);
            $filename = 'pedagogical-seminars-calendar.pdf';

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
                function () use ($request, $locale, $service): void {
                    echo $this->seminarCalendar($request, $locale, $service)->render();
                },
                'pedagogical-seminars-calendar.html',
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}

