<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PedagogicalInspectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherInspectionDossierController extends Controller
{
    /**
     * Resolve target teacher based on viewer permissions.
     */
    protected function resolveTeacher(Request $request, ?User $user): User
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        if ($viewer->isTeacher()) {
            return $viewer;
        }

        if ($viewer->isAdmin() || $viewer->isPrincipal() || $viewer->isCensor() || $viewer->isLeadership() || $viewer->isDeveloper()) {
            return $user ?? $viewer;
        }

        abort(403);
    }

    /**
     * Render printable Teacher Inspection Dossier HTML page.
     */
    public function print(
        Request $request,
        string $locale,
        ?User $user = null,
        ?PedagogicalInspectionService $service = null
    ): View {
        $service = $service ?? app(PedagogicalInspectionService::class);
        $teacher = $this->resolveTeacher($request, $user);
        $academicYear = $request->query('year');

        $data = $service->getTeacherInspectionDossierData($teacher, $academicYear);
        $data['autoPrint'] = $request->boolean('autoprint', false);

        return view('print.teacher-inspection-dossier', $data);
    }

    /**
     * Download official Teacher Inspection Dossier as PDF.
     */
    public function pdf(
        Request $request,
        string $locale,
        ?User $user = null,
        ?PedagogicalInspectionService $service = null
    ): StreamedResponse {
        $service = $service ?? app(PedagogicalInspectionService::class);
        $teacher = $this->resolveTeacher($request, $user);
        $academicYear = $request->query('year');

        $pdf = $service->generateDossierPdf($teacher, $academicYear);
        $fileName = sprintf('dossier-inspection-%s.pdf', str_replace(' ', '_', $teacher->name));

        return response()->streamDownload(
            fn () => print ($pdf),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Download official Teacher Inspection Dossier as Word document (.docx).
     */
    public function word(
        Request $request,
        string $locale,
        ?User $user = null,
        ?PedagogicalInspectionService $service = null
    ): StreamedResponse {
        $service = $service ?? app(PedagogicalInspectionService::class);
        $teacher = $this->resolveTeacher($request, $user);
        $academicYear = $request->query('year');

        $wordContent = $service->generateDossierWord($teacher, $academicYear);
        $fileName = sprintf('dossier-inspection-%s.docx', str_replace(' ', '_', $teacher->name));

        return response()->streamDownload(
            fn () => print ($wordContent),
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]
        );
    }
}
