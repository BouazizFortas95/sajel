<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use App\Services\RakmanaSyncService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RakmanaSyncController extends Controller
{
    /**
     * Download ministerial grade matrix CSV for a classroom and subject.
     */
    public function exportGradeCsv(
        Request $request,
        string $locale,
        Classroom $classroom,
        Subject $subject,
        RakmanaSyncService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $classroom->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        $trimester = (int) $request->query('trimester', 1);
        $academicYear = $request->query('academic_year');

        $csv = $service->generateRakmanaGradeCsv($classroom, $subject, $trimester, $academicYear);
        $filename = 'releve-notes-rakmana-'.$classroom->name.'-'.$subject->name.'-T'.$trimester.'.csv';

        return response()->streamDownload(
            fn () => print ($csv),
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    /**
     * Download a sample Rakmana CSV roster template.
     */
    public function downloadSampleRoster(Request $request, string $locale): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isTeacher()), 403);

        $headers = [
            'رقم التعريف الوطني',
            'رقم التسجيل',
            'اللقب',
            'الاسم',
            'الجنس',
            'تاريخ الميلاد',
            'مكان الميلاد',
            'القسم',
            'صفة التمدرس',
            'اسم الولي',
            'هاتف الولي',
        ];

        $sampleRows = [
            ['201116001234567890', '2026-0001', 'بن علي', 'أمين', 'ذكر', '15/05/2011', 'الجزائر الوسطى', '1م1', 'نصف داخلي', 'محمد بن علي', '0555123456'],
            ['201116009876543210', '2026-0002', 'براهيمي', 'مريم', 'أنثى', '22/09/2011', 'المدنية', '1م1', 'خارجي', 'سعيد براهيمي', '0661987654'],
            ['201116005544332211', '2026-0003', 'قدور', 'ياسين', 'ذكر', '03/01/2011', 'الحراش', '1م2', 'خارجي', 'أحمد قدور', '0770112233'],
        ];

        return response()->streamDownload(
            function () use ($headers, $sampleRows) {
                $out = fopen('php://temp', 'r+');
                fputcsv($out, $headers);
                foreach ($sampleRows as $row) {
                    fputcsv($out, $row);
                }
                rewind($out);
                // Prepend UTF-8 BOM
                echo "\xEF\xBB\xBF".stream_get_contents($out);
                fclose($out);
            },
            'modele-import-eleves-rakmana.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /**
     * Download official ministerial student roster CSV according to Algerian Ministry format.
     */
    public function exportRosterCsv(
        Request $request,
        string $locale,
        RakmanaSyncService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $user->isEducationStaff()),
            403
        );

        $classroomId = $request->filled('classroom_id') ? (int) $request->query('classroom_id') : null;
        $levelId = $request->filled('level_id') ? (int) $request->query('level_id') : null;
        $academicYear = $request->query('academic_year');

        $csv = $service->generateRakmanaStudentRosterCsv($classroomId, $levelId, $academicYear);

        $suffix = $classroomId ? '-classe-'.$classroomId : ($levelId ? '-niveau-'.$levelId : '-complet');
        $filename = 'liste-eleves-rakmana'.$suffix.'-'.date('Ymd').'.csv';

        return response()->streamDownload(
            fn () => print ($csv),
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    /**
     * Download blank ministerial grade booking template CSV for a classroom and subject.
     */
    public function downloadGradeTemplate(
        Request $request,
        string $locale,
        Classroom $classroom,
        Subject $subject,
        RakmanaSyncService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless(
            $user instanceof User && ($user->isAdmin() || $classroom->schedules()->where('user_id', $user->id)->exists()),
            403
        );

        $trimester = (int) $request->query('trimester', 1);
        $academicYear = $request->query('academic_year');

        $csv = $service->generateBlankGradeTemplateCsv($classroom, $subject, $trimester, $academicYear);
        $filename = 'modele-notes-rakmana-'.$classroom->name.'-'.$subject->name.'-T'.$trimester.'.csv';

        return response()->streamDownload(
            fn () => print ($csv),
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }
}
