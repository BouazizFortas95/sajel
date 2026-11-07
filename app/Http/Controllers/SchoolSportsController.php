<?php

namespace App\Http\Controllers;

use App\Models\SportsCompetitionEvent;
use App\Models\StudentSportsLicense;
use App\Models\User;
use App\Services\SchoolSportsService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolSportsController extends Controller
{
    public function __construct(
        private readonly SchoolSportsService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── 1. Student License Badge (A4 Portrait Badge / Card) ────────────

    public function printStudentLicense(
        Request $request,
        string $locale,
        StudentSportsLicense $license
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $data = $this->service->assembleStudentLicenseData($license);

        return view('print.sports-student-license', $data);
    }

    public function printStudentLicensePdf(
        Request $request,
        string $locale,
        StudentSportsLicense $license
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $data = $this->service->assembleStudentLicenseData($license);
        $cleanNumber = str_replace(['/', '\\'], '-', $license->license_number);
        $filename = "student-ascs-license-{$cleanNumber}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.sports-student-license', $data)
                ->portrait()
                ->generate();

            if (! $pdf) {
                throw new \RuntimeException('Browsershot PDF generation failed');
            }

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
        } catch (Throwable) {
            return response()->view('print.sports-student-license', $data);
        }
    }

    // ─── 2. Parental Consent & Medical Aptitude Fiche (A4 Portrait) ─────

    public function printParentalMedicalFiche(
        Request $request,
        string $locale,
        StudentSportsLicense $license
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $data = $this->service->assembleParentalConsentAndMedicalData($license);

        return view('print.sports-parental-medical-fiche', $data);
    }

    public function printParentalMedicalFichePdf(
        Request $request,
        string $locale,
        StudentSportsLicense $license
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $data = $this->service->assembleParentalConsentAndMedicalData($license);
        $cleanNumber = str_replace(['/', '\\'], '-', $license->license_number);
        $filename = "sports-medical-fiche-{$cleanNumber}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.sports-parental-medical-fiche', $data)
                ->portrait()
                ->generate();

            if (! $pdf) {
                throw new \RuntimeException('Browsershot PDF generation failed');
            }

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
        } catch (Throwable) {
            return response()->view('print.sports-parental-medical-fiche', $data);
        }
    }

    // ─── 3. Competition Match Roster & Engagement PV (A4 Portrait) ──────

    public function printCompetitionRosterPv(
        Request $request,
        string $locale,
        SportsCompetitionEvent $event
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $data = $this->service->assembleCompetitionRosterPvData($event);

        return view('print.sports-competition-roster-pv', $data);
    }

    public function printCompetitionRosterPvPdf(
        Request $request,
        string $locale,
        SportsCompetitionEvent $event
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $data = $this->service->assembleCompetitionRosterPvData($event);
        $cleanCode = str_replace(['/', '\\'], '-', $event->event_code);
        $filename = "competition-roster-pv-{$cleanCode}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.sports-competition-roster-pv', $data)
                ->portrait()
                ->generate();

            if (! $pdf) {
                throw new \RuntimeException('Browsershot PDF generation failed');
            }

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
        } catch (Throwable) {
            return response()->view('print.sports-competition-roster-pv', $data);
        }
    }

    // ─── 4. ASCS Annual Moral & Financial Bilan (A4 Landscape) ──────────

    public function printAscsBilan(
        Request $request,
        string $locale,
        string $academicYear
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $year = str_replace('-', '/', $academicYear);
        $data = $this->service->assembleAscsAnnualBilanData($year);

        return view('print.sports-ascs-bilan', $data);
    }

    public function printAscsBilanPdf(
        Request $request,
        string $locale,
        string $academicYear
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolSports(), 403);

        $year = str_replace('-', '/', $academicYear);
        $data = $this->service->assembleAscsAnnualBilanData($year);
        $filename = "ascs-bilan-{$academicYear}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.sports-ascs-bilan', $data)
                ->landscape()
                ->generate();

            if (! $pdf) {
                throw new \RuntimeException('Browsershot PDF generation failed');
            }

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
        } catch (Throwable) {
            return response()->view('print.sports-ascs-bilan', $data);
        }
    }
}

