<?php

namespace App\Http\Controllers;

use App\Models\DisciplinaryRecord;
use App\Models\User;
use App\Services\DisciplinaryCouncilService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisciplinaryCouncilController extends Controller
{
    /**
     * Determine whether the current user is authorized to view a disciplinary record.
     */
    protected function authorizeRecordAccess(User $user, DisciplinaryRecord $record): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $isTeacher = $record->reported_by === $user->id
            || ($record->student?->group?->classroom?->schedules()->where('user_id', $user->id)->exists());

        abort_unless($isTeacher, 403);
    }

    /**
     * Preview Disciplinary Hearing Summons for Student & Guardian.
     */
    public function summons(
        Request $request,
        string $locale,
        DisciplinaryRecord $record,
        DisciplinaryCouncilService $service,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeRecordAccess($user, $record);

        $data = $service->assembleSummonsLetter($record);

        return view('print.disciplinary-summons', $data);
    }

    /**
     * Download or stream Hearing Summons as PDF.
     */
    public function summonsPdf(
        Request $request,
        string $locale,
        DisciplinaryRecord $record,
        DisciplinaryCouncilService $service,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeRecordAccess($user, $record);

        $pdf = $service->generateSummonsPdf($record);
        $filename = 'convocation-conseil-discipline-'.$record->id.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Preview Official Disciplinary Council Deliberation Minutes (PV).
     */
    public function minutes(
        Request $request,
        string $locale,
        DisciplinaryRecord $record,
        DisciplinaryCouncilService $service,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeRecordAccess($user, $record);

        $data = $service->assembleCouncilMinutes($record);

        return view('print.disciplinary-council-minutes', $data);
    }

    /**
     * Download or stream Council Deliberation Minutes as PDF.
     */
    public function minutesPdf(
        Request $request,
        string $locale,
        DisciplinaryRecord $record,
        DisciplinaryCouncilService $service,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeRecordAccess($user, $record);

        $pdf = $service->generateCouncilMinutesPdf($record);
        $filename = 'pv-conseil-discipline-'.$record->id.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Preview Formal Sanction Notification with Legal Appeal Notice.
     */
    public function notification(
        Request $request,
        string $locale,
        DisciplinaryRecord $record,
        DisciplinaryCouncilService $service,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeRecordAccess($user, $record);

        $data = $service->assembleSanctionNotification($record);

        return view('print.disciplinary-sanction-notification', $data);
    }

    /**
     * Download or stream Sanction Notification as PDF.
     */
    public function notificationPdf(
        Request $request,
        string $locale,
        DisciplinaryRecord $record,
        DisciplinaryCouncilService $service,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeRecordAccess($user, $record);

        $pdf = $service->generateSanctionNotificationPdf($record);
        $filename = 'notification-sanction-discipline-'.$record->id.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Preview Annual Disciplinary Register Ledger.
     */
    public function ledger(
        Request $request,
        string $locale,
        DisciplinaryCouncilService $service,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $academicYear = $request->query('academic_year');
        $data = $service->assembleAnnualDisciplinaryLedger($academicYear);

        return view('print.annual-disciplinary-ledger', $data);
    }

    /**
     * Download or stream Annual Disciplinary Ledger as PDF.
     */
    public function ledgerPdf(
        Request $request,
        string $locale,
        DisciplinaryCouncilService $service,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $academicYear = $request->query('academic_year');
        $resolvedYear = $service->resolveAcademicYear($academicYear);
        $pdf = $service->generateAnnualDisciplinaryLedgerPdf($resolvedYear);
        $filename = 'registre-conseils-discipline-'.str_replace('/', '-', $resolvedYear).'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
