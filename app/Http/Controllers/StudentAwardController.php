<?php

namespace App\Http\Controllers;

use App\Enums\StudentAwardType;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\StudentAwardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentAwardController extends Controller
{
    /**
     * Print a single student award certificate.
     */
    public function printSingle(
        Request $request,
        string $locale,
        Student $student,
        string $awardType,
        StudentAwardService $service
    ): View {
        Gate::authorize('view', $student);

        $trimester = $request->filled('trimester') ? (int) $request->query('trimester') : 1;
        $awardTypeEnum = StudentAwardType::from($awardType);

        $data = $service->prepareSingleCertificateData($student, $awardTypeEnum, $trimester);
        $data['certificates'] = [$data];

        return view('print.student-award-certificate', $data);
    }

    /**
     * Download a single student award certificate as PDF.
     */
    public function pdfSingle(
        Request $request,
        string $locale,
        Student $student,
        string $awardType,
        StudentAwardService $service
    ): StreamedResponse {
        Gate::authorize('view', $student);

        $trimester = $request->filled('trimester') ? (int) $request->query('trimester') : 1;

        try {
            $pdf = $service->generateSingleCertificatePdf($student, $awardType, $trimester);

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                'certificate-student-'.$student->id.'-'.$awardType.'.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (Throwable $e) {
            report($e);
            abort(500, __('تعذر إنشاء ملف PDF للشهادة.'));
        }
    }

    /**
     * Batch print award certificates for a classroom.
     */
    public function printBatch(
        Request $request,
        string $locale,
        Classroom $classroom,
        StudentAwardService $service
    ): View {
        Gate::authorize('view', $classroom);

        $trimester = $request->filled('trimester') ? (int) $request->query('trimester') : 1;
        $groupId = $request->filled('group_id') ? (int) $request->query('group_id') : null;
        $awardFilter = $request->filled('award_type') ? StudentAwardType::tryFrom($request->query('award_type')) : null;

        $classroomData = $service->computeClassroomAwards($classroom, $trimester, $groupId, $awardFilter);

        $certificates = [];
        foreach ($classroomData['students'] as $row) {
            $student = $row['student'];
            $targetAward = $awardFilter ?? $row['primary_award'];

            if ($targetAward !== null) {
                $certificates[] = $service->prepareSingleCertificateData($student, $targetAward, $trimester);
            }
        }

        return view('print.student-award-certificate', [
            'classroom' => $classroom,
            'certificates' => $certificates,
            'schoolSetting' => $classroomData['schoolSetting'],
            'academicYear' => $classroomData['academicYear'],
            'period' => $classroomData['period'],
        ]);
    }

    /**
     * Download batch classroom certificates as a combined PDF.
     */
    public function pdfBatch(
        Request $request,
        string $locale,
        Classroom $classroom,
        StudentAwardService $service
    ): StreamedResponse {
        Gate::authorize('view', $classroom);

        $trimester = $request->filled('trimester') ? (int) $request->query('trimester') : 1;
        $groupId = $request->filled('group_id') ? (int) $request->query('group_id') : null;
        $awardFilter = $request->filled('award_type') ? StudentAwardType::tryFrom($request->query('award_type')) : null;

        try {
            $pdf = $service->generateClassroomCertificatesPdf($classroom, $trimester, $groupId, $awardFilter);

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                'certificates-class-'.$classroom->id.'-trimester-'.$trimester.'.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (Throwable $e) {
            report($e);
            abort(500, __('تعذر إنشاء ملف PDF للشهادات.'));
        }
    }

    /**
     * Print official classroom honor roll roll-call sheet for school bulletin board.
     */
    public function printHonorRollSheet(
        Request $request,
        string $locale,
        Classroom $classroom,
        StudentAwardService $service
    ): View {
        Gate::authorize('view', $classroom);

        $trimester = $request->filled('trimester') ? (int) $request->query('trimester') : 1;
        $groupId = $request->filled('group_id') ? (int) $request->query('group_id') : null;

        $data = $service->computeClassroomAwards($classroom, $trimester, $groupId);

        return view('print.classroom-honor-roll', $data);
    }

    /**
     * Download official classroom honor roll sheet as PDF.
     */
    public function pdfHonorRollSheet(
        Request $request,
        string $locale,
        Classroom $classroom,
        StudentAwardService $service
    ): StreamedResponse {
        Gate::authorize('view', $classroom);

        $trimester = $request->filled('trimester') ? (int) $request->query('trimester') : 1;
        $groupId = $request->filled('group_id') ? (int) $request->query('group_id') : null;

        try {
            $pdf = $service->generateHonorRollSheetPdf($classroom, $trimester, $groupId);

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                'honor-roll-class-'.$classroom->id.'-trimester-'.$trimester.'.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (Throwable $e) {
            report($e);
            abort(500, __('تعذر إنشاء ملف PDF للوحة الشرف.'));
        }
    }
}
