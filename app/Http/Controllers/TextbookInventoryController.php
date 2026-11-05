<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\TextbookInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TextbookInventoryController extends Controller
{
    /**
     * Print single student textbook clearance certificate (Quitus).
     */
    public function clearance(
        Request $request,
        string $locale,
        Student $student,
        TextbookInventoryService $service
    ): View {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $student->group?->classroom?->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        $year = $request->query('year');
        $data = $service->assembleStudentClearanceCertificate($student, $year);

        return view('print.textbook-clearance', $data);
    }

    /**
     * Stream download student clearance certificate as PDF.
     */
    public function clearancePdf(
        Request $request,
        string $locale,
        Student $student,
        TextbookInventoryService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $student->group?->classroom?->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        $year = $request->query('year');

        try {
            $pdf = $service->generateClearancePdf($student, $year);
            $filename = sprintf('quitus-textbooks-student-%d.pdf', $student->id);

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
                function () use ($request, $locale, $student, $service): void {
                    echo $this->clearance($request, $locale, $student, $service)->render();
                },
                sprintf('quitus-textbooks-student-%d.html', $student->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Print classroom textbook distribution and return tracking sheet.
     */
    public function classroomSheet(
        Request $request,
        string $locale,
        Classroom $classroom,
        TextbookInventoryService $service
    ): View {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $classroom->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        $year = $request->query('year');
        $data = $service->assembleClassroomDistributionSheet($classroom, $year);

        return view('print.classroom-textbook-sheet', $data);
    }

    /**
     * Stream download classroom distribution sheet as PDF.
     */
    public function classroomSheetPdf(
        Request $request,
        string $locale,
        Classroom $classroom,
        TextbookInventoryService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $classroom->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        $year = $request->query('year');

        try {
            $pdf = $service->generateClassroomDistributionPdf($classroom, $year);
            $filename = sprintf('textbooks-classroom-%d.pdf', $classroom->id);

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
                function () use ($request, $locale, $classroom, $service): void {
                    echo $this->classroomSheet($request, $locale, $classroom, $service)->render();
                },
                sprintf('textbooks-classroom-%d.html', $classroom->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Print annual ONPS / CRDDP textbook inventory and balance sheet.
     */
    public function annualInventory(
        Request $request,
        string $locale,
        TextbookInventoryService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $year = $request->query('year');
        $data = $service->assembleAnnualOnpsInventory($year);

        return view('print.annual-textbook-inventory', $data);
    }

    /**
     * Stream download annual ONPS inventory as PDF.
     */
    public function annualInventoryPdf(
        Request $request,
        string $locale,
        TextbookInventoryService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $year = $request->query('year');

        try {
            $pdf = $service->generateAnnualOnpsInventoryPdf($year);
            $filename = 'annual-textbook-inventory.pdf';

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
                    echo $this->annualInventory($request, $locale, $service)->render();
                },
                'annual-textbook-inventory.html',
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
