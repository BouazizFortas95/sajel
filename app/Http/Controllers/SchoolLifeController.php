<?php

namespace App\Http\Controllers;

use App\Models\ClubMembership;
use App\Models\SchoolClub;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\SchoolLifeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolLifeController extends Controller
{
    /**
     * Print Club Technical Sheet (البطاقة الفنية والتنظيمية للنادي المدرسي).
     */
    public function printClubSheet(
        Request $request,
        string $locale,
        SchoolClub $club
    ): View {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $club->lead_teacher_id === $user->id || $club->assistant_teacher_id === $user->id),
            403
        );

        $club->load([
            'leadTeacher.primarySubject',
            'assistantTeacher.primarySubject',
            'memberships.student.group.classroom.level',
            'activities',
        ]);

        return view('print.club-technical-sheet', [
            'club' => $club,
            'bureau' => $club->bureauMembers(),
            'school' => SchoolSetting::current(),
        ]);
    }

    /**
     * Download Club Technical Sheet as PDF.
     */
    public function pdfClubSheet(
        Request $request,
        string $locale,
        SchoolClub $club,
        SchoolLifeService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $club->lead_teacher_id === $user->id || $club->assistant_teacher_id === $user->id),
            403
        );

        try {
            $pdf = $service->generateClubTechnicalSheetPdf($club);
            $filename = sprintf('fiche-technique-club-%d.pdf', $club->id);

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
                function () use ($request, $locale, $club): void {
                    echo $this->printClubSheet($request, $locale, $club)->render();
                },
                sprintf('fiche-technique-club-%d.html', $club->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Print Club Membership & Achievement Certificate (شهادة التقدير والمشاركة).
     */
    public function printMemberCertificate(
        Request $request,
        string $locale,
        ClubMembership $membership
    ): View {
        $user = $request->user();
        $club = $membership->club;

        abort_unless(
            $user instanceof User && ($user->isAdmin() || $club?->lead_teacher_id === $user->id || $club?->assistant_teacher_id === $user->id),
            403
        );

        $membership->load([
            'club.leadTeacher',
            'student.group.classroom.level',
        ]);

        return view('print.club-member-certificate', [
            'membership' => $membership,
            'club' => $membership->club,
            'student' => $membership->student,
            'school' => SchoolSetting::current(),
        ]);
    }

    /**
     * Download Club Membership Certificate as PDF.
     */
    public function pdfMemberCertificate(
        Request $request,
        string $locale,
        ClubMembership $membership,
        SchoolLifeService $service
    ): StreamedResponse {
        $user = $request->user();
        $club = $membership->club;

        abort_unless(
            $user instanceof User && ($user->isAdmin() || $club?->lead_teacher_id === $user->id || $club?->assistant_teacher_id === $user->id),
            403
        );

        try {
            $pdf = $service->generateMembershipCertificatePdf($membership);
            $filename = sprintf('certificat-club-%d.pdf', $membership->id);

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
                function () use ($request, $locale, $membership): void {
                    echo $this->printMemberCertificate($request, $locale, $membership)->render();
                },
                sprintf('certificat-club-%d.html', $membership->id),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Print Annual School Life & Clubs Bilan (الحصيلة السنوية للحياة المدرسية).
     */
    public function printAnnualBilan(
        Request $request,
        string $locale,
        SchoolLifeService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $academicYear = (string) $request->query('year', '2026-2027');
        $data = $service->getClubsOverview($academicYear);
        $data['school'] = SchoolSetting::current();

        return view('print.annual-school-life-bilan', $data);
    }

    /**
     * Download Annual School Life Bilan as PDF.
     */
    public function pdfAnnualBilan(
        Request $request,
        string $locale,
        SchoolLifeService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $academicYear = (string) $request->query('year', '2026-2027');

        try {
            $pdf = $service->generateAnnualLifeBilanPdf($academicYear);
            $filename = sprintf('bilan-annuel-vie-scolaire-%s.pdf', str_replace('-', '', $academicYear));

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
                    echo $this->printAnnualBilan($request, $locale, $service)->render();
                },
                sprintf('bilan-annuel-vie-scolaire-%s.html', str_replace('-', '', $academicYear)),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}

