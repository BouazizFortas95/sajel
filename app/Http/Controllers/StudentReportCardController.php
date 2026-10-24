<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentReportCardController extends Controller
{
    public function print(
        Request $request,
        string $locale,
        Student $student,
        StudentReportCardService $reportCards,
    ): View {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        return view('print.student-report-card', $reportCards->viewData($student, $viewer));
    }

    public function pdf(
        Request $request,
        string $locale,
        Student $student,
        StudentReportCardService $reportCards,
    ): StreamedResponse {
        Gate::authorize('view', $student);
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        try {
            $pdf = $reportCards->generatePdf($student, $viewer);

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                'بطاقة-متابعة-'.$student->id.'-'.now()->format('Y-m-d').'.pdf',
                ['Content-Type' => 'application/pdf'],
            );
        } catch (Throwable $exception) {
            report($exception);

            abort(500, __('تعذر إنشاء ملف PDF.'));
        }
    }
}
