<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TeacherReceiptsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherReceiptsPrintController extends Controller
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
     * Render printable individual receipt HTML page.
     */
    public function print(
        Request $request,
        string $locale,
        ?User $user = null,
        ?TeacherReceiptsService $service = null
    ): View {
        $service = $service ?? app(TeacherReceiptsService::class);
        $teacher = $this->resolveTeacher($request, $user);
        $academicYear = $request->query('year');

        $data = $service->viewData($teacher, $academicYear);
        $data['autoPrint'] = $request->boolean('autoprint', false);

        return view('print.teacher-receipts-individual', $data);
    }

    /**
     * Download official individual receipt voucher as PDF.
     */
    public function pdf(
        Request $request,
        string $locale,
        ?User $user = null,
        ?TeacherReceiptsService $service = null
    ): StreamedResponse {
        $service = $service ?? app(TeacherReceiptsService::class);
        $teacher = $this->resolveTeacher($request, $user);
        $academicYear = $request->query('year');

        $pdf = $service->generatePdf($teacher, $academicYear);
        $fileName = sprintf('recepisse-pedagogique-%s.pdf', str_replace(' ', '_', $teacher->name));

        return response()->streamDownload(
            fn () => print ($pdf),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Download official individual receipt voucher as Word document (.docx).
     */
    public function word(
        Request $request,
        string $locale,
        ?User $user = null,
        ?TeacherReceiptsService $service = null
    ): StreamedResponse {
        $service = $service ?? app(TeacherReceiptsService::class);
        $teacher = $this->resolveTeacher($request, $user);
        $academicYear = $request->query('year');

        $wordContent = $service->generateWord($teacher, $academicYear);
        $fileName = sprintf('recepisse-pedagogique-%s.docx', str_replace(' ', '_', $teacher->name));

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
