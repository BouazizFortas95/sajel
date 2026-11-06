<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Models\StudentMedicalRecord;
use App\Models\User;
use App\Services\SchoolHealthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolHealthController extends Controller
{
    protected function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $user->isEducationStaff()),
            403,
            __('غير مصرح لك بالاطلاع على السجلات والتقارير الصحية المدرسية.')
        );
    }

    /**
     * Preview Student Medical Dossier (UDS Card) in browser.
     */
    public function studentDossier(
        Request $request,
        string $locale,
        StudentMedicalRecord $record,
        SchoolHealthService $service
    ): View {
        $this->authorizeAccess($request);

        return view('print.student-medical-dossier', [
            'record' => $record->loadMissing('student.group.classroom.level'),
            'student' => $record->student,
            'schoolSetting' => SchoolSetting::current(),
            'inlineCss' => false,
        ]);
    }

    /**
     * Download or stream Student Medical Dossier as PDF.
     */
    public function studentDossierPdf(
        Request $request,
        string $locale,
        StudentMedicalRecord $record,
        SchoolHealthService $service
    ): StreamedResponse {
        $this->authorizeAccess($request);

        $pdf = $service->generateStudentMedicalDossierPdf($record->loadMissing('student.group.classroom.level'));
        $filename = 'dossier-medical-'.$record->student->id.'-'.str_replace('/', '-', $record->academic_year).'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Preview Physical Education Exemption Certificate in browser.
     */
    public function epsDispensation(
        Request $request,
        string $locale,
        StudentMedicalRecord $record,
        SchoolHealthService $service
    ): View {
        $this->authorizeAccess($request);

        return view('print.eps-medical-dispensation', [
            'record' => $record->loadMissing('student.group.classroom.level'),
            'student' => $record->student,
            'schoolSetting' => SchoolSetting::current(),
            'inlineCss' => false,
        ]);
    }

    /**
     * Download or stream Physical Education Exemption Certificate as PDF.
     */
    public function epsDispensationPdf(
        Request $request,
        string $locale,
        StudentMedicalRecord $record,
        SchoolHealthService $service
    ): StreamedResponse {
        $this->authorizeAccess($request);

        $pdf = $service->generateEpsDispensationCertificatePdf($record->loadMissing('student.group.classroom.level'));
        $filename = 'dispense-eps-'.$record->student->id.'-'.str_replace('/', '-', $record->academic_year).'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Preview Annual UDS School Health Activity & Screening Report in browser.
     */
    public function annualUdsBilan(
        Request $request,
        string $locale,
        SchoolHealthService $service
    ): View {
        $this->authorizeAccess($request);

        $year = $request->query('academic_year');
        $overview = $service->getHealthOverview($year);

        return view('print.annual-uds-bilan', [
            'overview' => $overview,
            'schoolSetting' => SchoolSetting::current(),
            'inlineCss' => false,
        ]);
    }

    /**
     * Download or stream Annual UDS Report as PDF.
     */
    public function annualUdsBilanPdf(
        Request $request,
        string $locale,
        SchoolHealthService $service
    ): StreamedResponse {
        $this->authorizeAccess($request);

        $year = $request->query('academic_year');
        $pdf = $service->generateAnnualUdsBilanPdf($year);
        $cleanYear = str_replace(['/', '\\'], '-', $service->resolveAcademicYear($year));
        $filename = 'bilan-annuel-uds-'.$cleanYear.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}

