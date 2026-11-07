<?php

use App\Http\Controllers\AdministrativeArchiveController;
use App\Http\Controllers\ClassCouncilController;
use App\Http\Controllers\CounselingController;
use App\Http\Controllers\DisciplinaryCouncilController;
use App\Http\Controllers\DownloadPedagogicalMemoPdfController;
use App\Http\Controllers\ExamTimetableController;
use App\Http\Controllers\InspectionReportController;
use App\Http\Controllers\InstitutionalBilanController;
use App\Http\Controllers\OfficialReportController;
use App\Http\Controllers\OrientationController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\PedagogicalCouncilController;
use App\Http\Controllers\PrintAssessmentController;
use App\Http\Controllers\PrintDailyLogsController;
use App\Http\Controllers\PrintPedagogicalMemoController;
use App\Http\Controllers\RakmanaSyncController;
use App\Http\Controllers\SchoolArchiveController;
use App\Http\Controllers\SchoolCanteenController;
use App\Http\Controllers\SchoolCensusController;
use App\Http\Controllers\SchoolHealthController;
use App\Http\Controllers\SchoolLifeController;
use App\Http\Controllers\StaffCareerController;
use App\Http\Controllers\StudentAwardController;
use App\Http\Controllers\StudentMobilityController;
use App\Http\Controllers\StudentReportCardController;
use App\Http\Controllers\StudentStationController;
use App\Http\Controllers\TeacherAbsenceController;
use App\Http\Controllers\TeacherPortfolioController;
use App\Http\Controllers\TextbookInventoryController;
use App\Http\Middleware\SetFilamentLocale;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\StudentMotivationService;
use App\Support\BrowsershotFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/manifest.webmanifest', fn () => response(
    file_get_contents(public_path('manifest.webmanifest')),
    200,
    [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache',
    ],
))->name('pwa.manifest');

Route::get('/sw.js', fn () => response(
    file_get_contents(public_path('sw.js')),
    200,
    [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache',
    ],
))->name('pwa.service-worker');

foreach ([192, 512] as $size) {
    Route::get("/pwa-icon-{$size}.png", fn () => response(
        file_get_contents(public_path("pwa-icon-{$size}.png")),
        200,
        [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ],
    ))->name("pwa.icon-{$size}");
}

Route::get('/pwa-icon.svg', fn () => response(
    file_get_contents(public_path('pwa-icon.svg')),
    200, [
        'Content-Type' => 'image/svg+xml',
        'Cache-Control' => 'public, max-age=86400',
    ],
))->name('pwa.icon');

Route::get('/favicon.ico', fn () => response(
    file_get_contents(public_path('favicon.ico')),
    200,
    ['Content-Type' => 'image/x-icon'],
))->name('pwa.favicon');

Route::get('/print/daily-logs', PrintDailyLogsController::class)
    ->middleware('auth')
    ->name('print.register');

Route::get('/{locale}/print/assessments/{assessment}', PrintAssessmentController::class)
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.assessment');

Route::get('/{locale}/print/students/{student}/report-card', [StudentReportCardController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-report-card');

Route::get('/{locale}/print/students/{student}/report-card/pdf', [StudentReportCardController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-report-card.pdf');

Route::get('/{locale}/print/students/{student}/certificate', function (string $locale, Student $student) {
    abort_unless(auth()->user()?->isAdmin() || auth()->user()?->schedules()->whereHas('group', fn ($q) => $q->where('classroom_id', $student->group?->classroom_id))->exists(), 403);
    Gate::forUser(auth()->user())->authorize('view', $student);

    return view('print.student-certificate', [
        'student' => $student,
        'service' => app(StudentMotivationService::class),
        'badges' => app(StudentMotivationService::class)->badgesForStudent($student),
        'schoolSetting' => SchoolSetting::first(),
    ]);
})
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-certificate');

Route::get('/{locale}/print/students/{student}/certificate/pdf', function (string $locale, Student $student) {
    Gate::forUser(auth()->user())->authorize('view', $student);
    $service = app(StudentMotivationService::class);
    $badges = $service->badgesForStudent($student);
    $view = view('print.student-certificate', [
        'student' => $student,
        'service' => $service,
        'badges' => $badges,
        'schoolSetting' => SchoolSetting::first(),
    ]);

    return BrowsershotFactory::html((string) $view)
        ->landscape()
        ->download('certificat-'.$student->full_name.'.pdf');
})
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-certificate.pdf');

Route::get('/{locale}/print/classrooms/{classroom}/class-council', [ClassCouncilController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.class-council');

Route::get('/{locale}/print/classrooms/{classroom}/class-council/pdf', [ClassCouncilController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.class-council.pdf');

Route::get('/{locale}/print/classrooms/{classroom}/exam-timetable', [ExamTimetableController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.exam-timetable');

Route::get('/{locale}/print/classrooms/{classroom}/exam-timetable/pdf', [ExamTimetableController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.exam-timetable.pdf');

Route::get('/{locale}/print/students/{student}/awards/{awardType}', [StudentAwardController::class, 'printSingle'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-award.single');

Route::get('/{locale}/print/students/{student}/awards/{awardType}/pdf', [StudentAwardController::class, 'pdfSingle'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-award.single.pdf');

Route::get('/{locale}/print/classrooms/{classroom}/awards', [StudentAwardController::class, 'printBatch'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-awards.batch');

Route::get('/{locale}/print/classrooms/{classroom}/awards/pdf', [StudentAwardController::class, 'pdfBatch'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-awards.batch.pdf');

Route::get('/{locale}/print/classrooms/{classroom}/honor-roll', [StudentAwardController::class, 'printHonorRollSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.classroom-honor-roll');

Route::get('/{locale}/print/classrooms/{classroom}/honor-roll/pdf', [StudentAwardController::class, 'pdfHonorRollSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.classroom-honor-roll.pdf');

Route::get('/{locale}/print/classrooms/{classroom}/annual-register-book', [SchoolArchiveController::class, 'printBook'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-archive.book');

Route::get('/{locale}/print/classrooms/{classroom}/annual-register-book/pdf', [SchoolArchiveController::class, 'pdfBook'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-archive.book.pdf');

Route::get('/{locale}/print/teachers/{user}/portfolio', [TeacherPortfolioController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-portfolio');

Route::get('/{locale}/print/inspection-reports/{inspectionReport}', [InspectionReportController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.inspection-report');

Route::get('/{locale}/print/inspection-reports/{inspectionReport}/pdf', [InspectionReportController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.inspection-report.pdf');

Route::get('/{locale}/parent/students/{student}', [ParentPortalController::class, 'portal'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['signed', SetFilamentLocale::class])
    ->name('parent.student-portal');

Route::get('/{locale}/lab/station/{labStation}', [StudentStationController::class, 'show'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(SetFilamentLocale::class)
    ->name('lab.station-client');

Route::get('/{locale}/lab/station/{labStation}/ping', [StudentStationController::class, 'ping'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->name('lab.station-ping');

Route::post('/{locale}/lab/station/{labStation}/acknowledge-broadcast', [StudentStationController::class, 'acknowledgeBroadcast'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->name('lab.station-acknowledge');

Route::get('/{locale}/print/teachers/{user}/portfolio/pdf', [TeacherPortfolioController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-portfolio.pdf');

Route::get('/{locale}/print/timetables', [OfficialReportController::class, 'timetable'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.timetable');

Route::get('/{locale}/print/timetables/pdf', [OfficialReportController::class, 'timetablePdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.timetable.pdf');

Route::get('/{locale}/print/attendance-summary', [OfficialReportController::class, 'attendanceSummary'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.attendance-summary');

Route::get('/{locale}/print/attendance-summary/pdf', [OfficialReportController::class, 'attendanceSummaryPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.attendance-summary.pdf');

Route::get('/{locale}/print/grade-transcript', [OfficialReportController::class, 'gradeTranscript'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.grade-transcript');

Route::get('/{locale}/print/grade-transcript/pdf', [OfficialReportController::class, 'gradeTranscriptPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.grade-transcript.pdf');

Route::get('/{locale}/print/pedagogical-memos/{memo}/pdf', DownloadPedagogicalMemoPdfController::class)
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-memo.pdf');

Route::get('/{locale}/print/pedagogical-memos/{memo}', PrintPedagogicalMemoController::class)
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-memo');

Route::get('/{locale}/print/teacher-absences/{absence}/mandates/{log}', [TeacherAbsenceController::class, 'printMandate'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.mandate');

Route::get('/{locale}/print/teacher-absences/{absence}/mandates/{log}/pdf', [TeacherAbsenceController::class, 'pdfMandate'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.mandate.pdf');

Route::get('/{locale}/print/teacher-absences/monthly-ledger', [TeacherAbsenceController::class, 'printMonthlyLedger'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.monthly-ledger');

Route::get('/{locale}/print/teacher-absences/monthly-ledger/pdf', [TeacherAbsenceController::class, 'pdfMonthlyLedger'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.monthly-ledger.pdf');

Route::get('/{locale}/print/teacher-absences/{absence}/resumption-pv', [TeacherAbsenceController::class, 'printResumptionPv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.resumption-pv');

Route::get('/{locale}/print/teacher-absences/{absence}/resumption-pv/pdf', [TeacherAbsenceController::class, 'pdfResumptionPv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.resumption-pv.pdf');

Route::get('/{locale}/print/teacher-absences/{absence}/deduction-notice', [TeacherAbsenceController::class, 'printDeductionNotice'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.deduction-notice');

Route::get('/{locale}/print/teacher-absences/{absence}/deduction-notice/pdf', [TeacherAbsenceController::class, 'pdfDeductionNotice'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.deduction-notice.pdf');

Route::get('/{locale}/print/teacher-absences/{absence}/requisitions/{log}', [TeacherAbsenceController::class, 'printRequisitionOrder'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.requisition-order');

Route::get('/{locale}/print/teacher-absences/{absence}/requisitions/{log}/pdf', [TeacherAbsenceController::class, 'pdfRequisitionOrder'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-absence.requisition-order.pdf');

Route::get('/{locale}/print/students/{student}/textbook-clearance', [TextbookInventoryController::class, 'clearance'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.textbook.clearance');

Route::get('/{locale}/print/students/{student}/textbook-clearance/pdf', [TextbookInventoryController::class, 'clearancePdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.textbook.clearance.pdf');

Route::get('/{locale}/print/classrooms/{classroom}/textbooks', [TextbookInventoryController::class, 'classroomSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.textbook.classroom');

Route::get('/{locale}/print/classrooms/{classroom}/textbooks/pdf', [TextbookInventoryController::class, 'classroomSheetPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.textbook.classroom.pdf');

Route::get('/{locale}/print/textbooks/annual-inventory', [TextbookInventoryController::class, 'annualInventory'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.textbook.annual-inventory');

Route::get('/{locale}/print/textbooks/annual-inventory/pdf', [TextbookInventoryController::class, 'annualInventoryPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.textbook.annual-inventory.pdf');

Route::get('/{locale}/print/student-transfers/{transfer}/certificate', [StudentMobilityController::class, 'certificate'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-transfer.certificate');

Route::get('/{locale}/print/student-transfers/{transfer}/certificate/pdf', [StudentMobilityController::class, 'certificatePdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-transfer.certificate.pdf');

Route::get('/{locale}/print/student-transfers/ledger', [StudentMobilityController::class, 'ledger'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-transfer.ledger');

Route::get('/{locale}/print/student-transfers/ledger/pdf', [StudentMobilityController::class, 'ledgerPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-transfer.ledger.pdf');

Route::get('/{locale}/print/student-transfers/{transfer}/forwarding-slip', [StudentMobilityController::class, 'forwardingSlip'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-transfer.forwarding-slip');

Route::get('/{locale}/print/student-transfers/{transfer}/forwarding-slip/pdf', [StudentMobilityController::class, 'forwardingSlipPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.student-transfer.forwarding-slip.pdf');

Route::get('/{locale}/print/disciplinary/{record}/summons', [DisciplinaryCouncilController::class, 'summons'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.summons');

Route::get('/{locale}/print/disciplinary/{record}/summons/pdf', [DisciplinaryCouncilController::class, 'summonsPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.summons.pdf');

Route::get('/{locale}/print/disciplinary/{record}/minutes', [DisciplinaryCouncilController::class, 'minutes'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.minutes');

Route::get('/{locale}/print/disciplinary/{record}/minutes/pdf', [DisciplinaryCouncilController::class, 'minutesPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.minutes.pdf');

Route::get('/{locale}/print/disciplinary/{record}/notification', [DisciplinaryCouncilController::class, 'notification'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.notification');

Route::get('/{locale}/print/disciplinary/{record}/notification/pdf', [DisciplinaryCouncilController::class, 'notificationPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.notification.pdf');

Route::get('/{locale}/print/disciplinary/annual-ledger', [DisciplinaryCouncilController::class, 'ledger'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.ledger');

Route::get('/{locale}/print/disciplinary/annual-ledger/pdf', [DisciplinaryCouncilController::class, 'ledgerPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.disciplinary.ledger.pdf');

Route::get('/{locale}/rakmana/grades/{classroom}/{subject}/export-csv', [RakmanaSyncController::class, 'exportGradeCsv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('rakmana.grades.export-csv');

Route::get('/{locale}/rakmana/grades/{classroom}/{subject}/template-csv', [RakmanaSyncController::class, 'downloadGradeTemplate'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('rakmana.grades.template-csv');

Route::get('/{locale}/rakmana/rosters/export-csv', [RakmanaSyncController::class, 'exportRosterCsv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('rakmana.rosters.export-csv');

Route::get('/{locale}/rakmana/sample-roster', [RakmanaSyncController::class, 'downloadSampleRoster'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('rakmana.sample-roster');

Route::get('/{locale}/print/rakmana/grades/{classroom}/{subject}', [RakmanaSyncController::class, 'printGradeSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.rakmana.grade-sheet');

Route::get('/{locale}/print/rakmana/grades/{classroom}/{subject}/pdf', [RakmanaSyncController::class, 'downloadGradeSheetPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.rakmana.grade-sheet.pdf');

Route::get('/{locale}/rakmana/deliberations/{classroom}/export-csv', [RakmanaSyncController::class, 'exportDeliberationCsv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('rakmana.deliberations.export-csv');

Route::get('/{locale}/print/rakmana/deliberations/{classroom}', [RakmanaSyncController::class, 'printDeliberation'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.rakmana.deliberation-sheet');

Route::get('/{locale}/print/rakmana/deliberations/{classroom}/pdf', [RakmanaSyncController::class, 'downloadDeliberationPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.rakmana.deliberation-sheet.pdf');

Route::get('/{locale}/print/school-census', [SchoolCensusController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-census');

Route::get('/{locale}/print/school-census/pdf', [SchoolCensusController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-census.pdf');

Route::get('/{locale}/print/orientation/wishes/{student}', [OrientationController::class, 'wishes'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.orientation.wishes');

Route::get('/{locale}/print/orientation/wishes/{student}/pdf', [OrientationController::class, 'wishesPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.orientation.wishes.pdf');

Route::get('/{locale}/print/orientation/notification/{student}', [OrientationController::class, 'notification'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.orientation.notification');

Route::get('/{locale}/print/orientation/notification/{student}/pdf', [OrientationController::class, 'notificationPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.orientation.notification.pdf');

Route::get('/{locale}/print/orientation/pv', [OrientationController::class, 'pv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.orientation.pv');

Route::get('/{locale}/print/orientation/pv/pdf', [OrientationController::class, 'pvPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.orientation.pv.pdf');

Route::get('/{locale}/print/counseling/dossier/{student}', [CounselingController::class, 'printDossier'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.counseling.dossier');

Route::get('/{locale}/print/counseling/dossier/{student}/pdf', [CounselingController::class, 'downloadDossierPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.counseling.dossier.pdf');

Route::get('/{locale}/print/counseling/session/{session}', [CounselingController::class, 'printSession'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.counseling.session');

Route::get('/{locale}/print/counseling/session/{session}/pdf', [CounselingController::class, 'downloadSessionPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.counseling.session.pdf');

Route::get('/{locale}/print/counseling/ledger', [CounselingController::class, 'printLedger'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.counseling.ledger');

Route::get('/{locale}/print/counseling/ledger/pdf', [CounselingController::class, 'downloadLedgerPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.counseling.ledger.pdf');

Route::get('/{locale}/print/institutional-bilans/{report?}', [InstitutionalBilanController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.institutional-bilan');

Route::get('/{locale}/print/institutional-bilans/{report?}/pdf', [InstitutionalBilanController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.institutional-bilan.pdf');

Route::get('/{locale}/print/school-clubs/{club}/technical-sheet', [SchoolLifeController::class, 'printClubSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-life.club-sheet');

Route::get('/{locale}/print/school-clubs/{club}/technical-sheet/pdf', [SchoolLifeController::class, 'pdfClubSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-life.club-sheet.pdf');

Route::get('/{locale}/print/club-memberships/{membership}/certificate', [SchoolLifeController::class, 'printMemberCertificate'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-life.member-certificate');

Route::get('/{locale}/print/club-memberships/{membership}/certificate/pdf', [SchoolLifeController::class, 'pdfMemberCertificate'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-life.member-certificate.pdf');

Route::get('/{locale}/print/school-life/annual-bilan', [SchoolLifeController::class, 'printAnnualBilan'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-life.annual-bilan');

Route::get('/{locale}/print/school-life/annual-bilan/pdf', [SchoolLifeController::class, 'pdfAnnualBilan'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-life.annual-bilan.pdf');

Route::get('/{locale}/print/school-health/dossier/{record}', [SchoolHealthController::class, 'studentDossier'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-health.student-dossier');

Route::get('/{locale}/print/school-health/dossier/{record}/pdf', [SchoolHealthController::class, 'studentDossierPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-health.student-dossier.pdf');

Route::get('/{locale}/print/school-health/eps-dispensation/{record}', [SchoolHealthController::class, 'epsDispensation'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-health.eps-dispensation');

Route::get('/{locale}/print/school-health/eps-dispensation/{record}/pdf', [SchoolHealthController::class, 'epsDispensationPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-health.eps-dispensation.pdf');

Route::get('/{locale}/print/school-health/annual-uds-bilan', [SchoolHealthController::class, 'annualUdsBilan'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-health.annual-uds-bilan');

Route::get('/{locale}/print/school-health/annual-uds-bilan/pdf', [SchoolHealthController::class, 'annualUdsBilanPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.school-health.annual-uds-bilan.pdf');

Route::get('/{locale}/print/pedagogical-council/{council}/pv', [PedagogicalCouncilController::class, 'councilPv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-council.pv');

Route::get('/{locale}/print/pedagogical-council/{council}/pv/pdf', [PedagogicalCouncilController::class, 'councilPvPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-council.pv.pdf');

Route::get('/{locale}/print/pedagogical-council/curriculum-pacing', [PedagogicalCouncilController::class, 'curriculumPacing'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-council.curriculum-pacing');

Route::get('/{locale}/print/pedagogical-council/curriculum-pacing/pdf', [PedagogicalCouncilController::class, 'curriculumPacingPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-council.curriculum-pacing.pdf');

Route::get('/{locale}/print/pedagogical-council/seminar-calendar', [PedagogicalCouncilController::class, 'seminarCalendar'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-council.seminar-calendar');

Route::get('/{locale}/print/pedagogical-council/seminar-calendar/pdf', [PedagogicalCouncilController::class, 'seminarCalendarPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.pedagogical-council.seminar-calendar.pdf');

Route::get('/{locale}/print/canteen/daily-sheet/{sheet}', [SchoolCanteenController::class, 'dailySheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.canteen.daily-sheet');

Route::get('/{locale}/print/canteen/daily-sheet/{sheet}/pdf', [SchoolCanteenController::class, 'dailySheetPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.canteen.daily-sheet.pdf');

Route::get('/{locale}/print/canteen/weekly-menu/{menu}', [SchoolCanteenController::class, 'weeklyMenu'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.canteen.weekly-menu');

Route::get('/{locale}/print/canteen/weekly-menu/{menu}/pdf', [SchoolCanteenController::class, 'weeklyMenuPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.canteen.weekly-menu.pdf');

Route::get('/{locale}/print/canteen/periodic-bilan', [SchoolCanteenController::class, 'periodicBilan'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.canteen.periodic-bilan');

Route::get('/{locale}/print/canteen/periodic-bilan/pdf', [SchoolCanteenController::class, 'periodicBilanPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.canteen.periodic-bilan.pdf');

Route::get('/{locale}/print/staff-career/evaluation/{evaluation}', [StaffCareerController::class, 'evaluationSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.staff-career.evaluation-sheet');

Route::get('/{locale}/print/staff-career/evaluation/{evaluation}/pdf', [StaffCareerController::class, 'evaluationSheetPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.staff-career.evaluation-sheet.pdf');

Route::get('/{locale}/print/staff-career/advancements/ledger', [StaffCareerController::class, 'advancementLedger'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.staff-career.advancement-ledger');

Route::get('/{locale}/print/staff-career/advancements/ledger/pdf', [StaffCareerController::class, 'advancementLedgerPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.staff-career.advancement-ledger.pdf');

Route::get('/{locale}/staff-career/advancements/export-csv', [StaffCareerController::class, 'exportAdvancementCsv'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('staff-career.advancements.export-csv');

// ─── Administrative Archive: Installation Minutes ───────────────────────────────
Route::get('/{locale}/print/admin-archive/installation/{installation}', [AdministrativeArchiveController::class, 'printInstallationSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.admin-archive.installation-sheet');

Route::get('/{locale}/print/admin-archive/installation/{installation}/pdf', [AdministrativeArchiveController::class, 'printInstallationSheetPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.admin-archive.installation-sheet.pdf');

// ─── Administrative Archive: Handover Minutes ────────────────────────────────────
Route::get('/{locale}/print/admin-archive/handover/{handover}', [AdministrativeArchiveController::class, 'printHandoverSheet'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.admin-archive.handover-sheet');

Route::get('/{locale}/print/admin-archive/handover/{handover}/pdf', [AdministrativeArchiveController::class, 'printHandoverSheetPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.admin-archive.handover-sheet.pdf');

// ─── Administrative Archive: Official Mail Ledger ────────────────────────────────
Route::get('/{locale}/print/admin-archive/mail-ledger/{type}/{year}', [AdministrativeArchiveController::class, 'printMailLedger'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.admin-archive.mail-ledger');

Route::get('/{locale}/print/admin-archive/mail-ledger/{type}/{year}/pdf', [AdministrativeArchiveController::class, 'printMailLedgerPdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.admin-archive.mail-ledger.pdf');

/*
 * Filament's panel routes are localized ({locale}/auth/...). If an old link
 * or a bookmark omits the locale, send the visitor to the same panel path
 * using their persisted locale instead of returning a 404.
 */
Route::get('/auth/{path?}', function (?string $path = null) {
    $path = trim((string) $path, '/');
    $target = '/'.app()->getLocale().'/auth'.($path !== '' ? '/'.$path : '');

    if ($query = request()->getQueryString()) {
        $target .= '?'.$query;
    }

    return redirect()->to($target);
})
    ->where('path', '.*')
    ->name('filament.auth.locale-fallback');

Route::get('/{locale}', function () {
    return view('welcome');
})
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(SetFilamentLocale::class);
