<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\TeacherAbsence;
use App\Models\User;
use App\Services\TeacherAbsenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TeacherAbsenceController extends Controller
{
    /**
     * Print single substitute mandate sheet.
     */
    public function printMandate(
        Request $request,
        string $locale,
        TeacherAbsence $absence,
        DailyLog $log
    ): View {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $absence->teacher_id === $user->id || $log->substitute_teacher_id === $user->id),
            403
        );

        $log->load(['schedule.subject', 'schedule.classroom', 'schedule.group', 'substituteTeacher']);
        $absence->load(['teacher.primarySubject', 'recordedBy']);

        $substitute = $log->substituteTeacher ?? $user;

        return view('print.substitute-mandate', [
            'absence' => $absence,
            'log' => $log,
            'schedule' => $log->schedule,
            'classroom' => $log->schedule?->resolvedClassroom(),
            'subject' => $log->schedule?->subject,
            'absentTeacher' => $absence->teacher,
            'substituteTeacher' => $substitute,
        ]);
    }

    /**
     * Stream download single substitute mandate as PDF.
     */
    public function pdfMandate(
        Request $request,
        string $locale,
        TeacherAbsence $absence,
        DailyLog $log,
        TeacherAbsenceService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $absence->teacher_id === $user->id || $log->substitute_teacher_id === $user->id),
            403
        );

        $log->load(['schedule.subject', 'schedule.classroom', 'schedule.group', 'substituteTeacher']);
        $absence->load(['teacher.primarySubject']);

        $substitute = $log->substituteTeacher ?? $user;

        try {
            $pdf = $service->generateMandatePdf($absence, $log, $substitute);
            $filename = sprintf('mandate-substitute-log-%d.pdf', $log->id);

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
        } catch (Throwable $e) {
            return response()->streamDownload(
                function () use ($request, $locale, $absence, $log): void {
                    echo $this->printMandate($request, $locale, $absence, $log)->render();
                },
                sprintf('mandate-substitute-log-%d.html', $log->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Print monthly absence and replacement ledger.
     */
    public function printMonthlyLedger(
        Request $request,
        string $locale,
        TeacherAbsenceService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $data = $service->assembleMonthlyLedger($year, $month);

        return view('print.monthly-absence-ledger', $data);
    }

    /**
     * Stream download monthly absence and replacement ledger as PDF.
     */
    public function pdfMonthlyLedger(
        Request $request,
        string $locale,
        TeacherAbsenceService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        try {
            $pdf = $service->generateMonthlyLedgerPdf($year, $month);
            $filename = sprintf('monthly-absence-ledger-%d-%02d.pdf', $year, $month);

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
        } catch (Throwable $e) {
            return response()->streamDownload(
                function () use ($request, $locale, $service): void {
                    echo $this->printMonthlyLedger($request, $locale, $service)->render();
                },
                sprintf('monthly-absence-ledger-%d-%02d.html', $year, $month),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}

