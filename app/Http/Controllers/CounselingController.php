<?php

namespace App\Http\Controllers;

use App\Models\CounselingSession;
use App\Models\Student;
use App\Services\StudentCounselingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CounselingController extends Controller
{
    public function __construct(
        protected StudentCounselingService $counselingService,
    ) {}

    public function printDossier(string $locale, Student $student): Response
    {
        $this->authorizeAccess($student);

        $html = $this->counselingService->renderDossierHtml($student);

        return response($html);
    }

    public function downloadDossierPdf(string $locale, Student $student): Response
    {
        $this->authorizeAccess($student);

        $pdf = $this->counselingService->generateDossierPdf($student);
        $filename = 'dossier-psycho-pedagogique-'.$student->id.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function printSession(string $locale, CounselingSession $session): Response
    {
        $this->authorizeAccess($session->student);

        $html = $this->counselingService->renderSessionHtml($session);

        return response($html);
    }

    public function downloadSessionPdf(string $locale, CounselingSession $session): Response
    {
        $this->authorizeAccess($session->student);

        $pdf = $this->counselingService->generateSessionPdf($session);
        $filename = 'rapport-entretien-conseil-'.$session->id.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function printLedger(Request $request, string $locale): Response
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $academicYear = $request->query('academic_year');
        $html = $this->counselingService->renderAnnualLedgerHtml($academicYear);

        return response($html);
    }

    public function downloadLedgerPdf(Request $request, string $locale): Response
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $academicYear = $request->query('academic_year');
        $pdf = $this->counselingService->generateAnnualLedgerPdf($academicYear);
        $filename = 'registre-annuel-suivis-counseling.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    protected function authorizeAccess(Student $student): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return;
        }

        // Teachers can view counseling records only for students in their assigned classrooms/groups
        $hasStudent = $user->schedules()
            ->where(function ($query) use ($student) {
                $query->where('group_id', $student->group_id)
                    ->orWhere('classroom_id', $student->group?->classroom_id);
            })
            ->exists();

        abort_unless($hasStudent, 403);
    }
}
