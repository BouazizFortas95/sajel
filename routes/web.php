<?php

use App\Http\Controllers\ClassCouncilController;
use App\Http\Controllers\DownloadPedagogicalMemoPdfController;
use App\Http\Controllers\OfficialReportController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\PrintAssessmentController;
use App\Http\Controllers\PrintDailyLogsController;
use App\Http\Controllers\PrintPedagogicalMemoController;
use App\Http\Controllers\StudentReportCardController;
use App\Http\Controllers\TeacherPortfolioController;
use App\Http\Middleware\SetFilamentLocale;
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

Route::get('/{locale}/print/classrooms/{classroom}/class-council', [ClassCouncilController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.class-council');

Route::get('/{locale}/print/classrooms/{classroom}/class-council/pdf', [ClassCouncilController::class, 'pdf'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.class-council.pdf');

Route::get('/{locale}/print/teachers/{user}/portfolio', [TeacherPortfolioController::class, 'print'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.teacher-portfolio');

Route::get('/{locale}/parent/students/{student}', [ParentPortalController::class, 'portal'])
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['signed', SetFilamentLocale::class])
    ->name('parent.student-portal');

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
