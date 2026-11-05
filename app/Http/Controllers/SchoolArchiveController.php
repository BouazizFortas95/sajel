<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use App\Services\SchoolArchiveService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolArchiveController extends Controller
{
    /**
     * View and print the complete annual register book in A4 format.
     */
    public function printBook(
        Request $request,
        string $locale,
        Classroom $classroom,
        SchoolArchiveService $service
    ): View {
        Gate::authorize('view', $classroom);

        $user = $request->user();
        $subjectId = $request->filled('subject_id') ? (int) $request->query('subject_id') : null;
        $trimester = $request->filled('trimester') && $request->query('trimester') !== '' ? (int) $request->query('trimester') : null;
        $teacherId = $user instanceof User && $user->isTeacher()
            ? $user->id
            : ($request->filled('teacher_id') ? (int) $request->query('teacher_id') : null);

        $data = $service->assembleAnnualRegisterBook($classroom, $subjectId, $trimester, $teacherId);

        return view('print.annual-register-book', $data);
    }

    /**
     * Generate and stream download the complete annual register book as PDF.
     */
    public function pdfBook(
        Request $request,
        string $locale,
        Classroom $classroom,
        SchoolArchiveService $service
    ): StreamedResponse {
        Gate::authorize('view', $classroom);

        $user = $request->user();
        $subjectId = $request->filled('subject_id') ? (int) $request->query('subject_id') : null;
        $trimester = $request->filled('trimester') && $request->query('trimester') !== '' ? (int) $request->query('trimester') : null;
        $teacherId = $user instanceof User && $user->isTeacher()
            ? $user->id
            : ($request->filled('teacher_id') ? (int) $request->query('teacher_id') : null);

        try {
            $pdf = $service->generateAnnualBookPdf($classroom, $subjectId, $trimester, $teacherId);

            $filename = sprintf(
                'annual-register-class-%d-%s.pdf',
                $classroom->id,
                $subjectId ? 'subj-'.$subjectId : 'all'
            );

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                $filename,
                ['Content-Type' => 'application/pdf']
            );
        } catch (Throwable $e) {
            report($e);
            abort(500, __('تعذر إنشاء ملف PDF لدفتر النصوص السنوي.'));
        }
    }
}
