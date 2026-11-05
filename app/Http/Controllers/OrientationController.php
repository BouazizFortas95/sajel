<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentOrientationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrientationController extends Controller
{
    /**
     * Authorize access to a student's orientation dossier.
     */
    protected function authorizeStudentAccess(User $user, Student $student): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $teachesStudent = $student->group?->classroom?->schedules()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($teachesStudent, 403);
    }

    /**
     * Preview Student Wishes Card (بطاقة الرغبات الرسمية A4).
     */
    public function wishes(
        Request $request,
        string $locale,
        Student $student,
        StudentOrientationService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeStudentAccess($user, $student);

        $academicYear = $request->query('academic_year');
        $orientation = $service->getOrCreateOrientation($student, $academicYear);
        $profile = $service->computeStudentProfile($student, $academicYear);

        return view('print.orientation-wishes', [
            'student' => $student,
            'orientation' => $orientation,
            'profile' => $profile,
            'school' => SchoolSetting::current(),
            'academic_year' => $orientation->academic_year,
        ]);
    }

    /**
     * Download or stream Student Wishes Card as PDF.
     */
    public function wishesPdf(
        Request $request,
        string $locale,
        Student $student,
        StudentOrientationService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeStudentAccess($user, $student);

        $pdf = $service->generateWishesPdf($student);
        $cleanName = str_replace(' ', '-', $student->full_name);
        $filename = 'fiche-voeux-orientation-'.$cleanName.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Preview Individual Orientation Notification for Guardian (إشعار التوجيه الرسمي A4).
     */
    public function notification(
        Request $request,
        string $locale,
        Student $student,
        StudentOrientationService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeStudentAccess($user, $student);

        $academicYear = $request->query('academic_year');
        $orientation = $service->getOrCreateOrientation($student, $academicYear);

        return view('print.orientation-notification', [
            'student' => $student,
            'orientation' => $orientation,
            'school' => SchoolSetting::current(),
            'academic_year' => $orientation->academic_year,
        ]);
    }

    /**
     * Download or stream Orientation Notification as PDF.
     */
    public function notificationPdf(
        Request $request,
        string $locale,
        Student $student,
        StudentOrientationService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeStudentAccess($user, $student);

        $pdf = $service->generateNotificationPdf($student);
        $cleanName = str_replace(' ', '-', $student->full_name);
        $filename = 'notification-orientation-'.$cleanName.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Preview Official Council Deliberations Minutes (محضر مجلس التوجيه A4 landscape).
     */
    public function pv(
        Request $request,
        string $locale,
        StudentOrientationService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $classroomId = $request->query('classroom_id') ? (int) $request->query('classroom_id') : null;
        $academicYear = $request->query('academic_year');
        $data = $service->assembleCouncilLedger($classroomId, $academicYear);

        return view('print.orientation-pv', $data);
    }

    /**
     * Download or stream Council Minutes as PDF.
     */
    public function pvPdf(
        Request $request,
        string $locale,
        StudentOrientationService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $classroomId = $request->query('classroom_id') ? (int) $request->query('classroom_id') : null;
        $academicYear = $request->query('academic_year');
        $pdf = $service->generateMinutesPdf($classroomId);
        $cleanYear = str_replace(['/', '\\'], '-', $academicYear ?? SchoolSetting::currentAcademicYear());
        $filename = 'proces-verbal-orientation-4am-'.$cleanYear.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
