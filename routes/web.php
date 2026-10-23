<?php

use App\Http\Controllers\DownloadPedagogicalMemoPdfController;
use App\Http\Controllers\OfficialReportController;
use App\Http\Controllers\PrintAssessmentController;
use App\Http\Controllers\PrintDailyLogsController;
use App\Http\Controllers\PrintPedagogicalMemoController;
use App\Http\Middleware\SetFilamentLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/print/daily-logs', PrintDailyLogsController::class)
    ->middleware('auth')
    ->name('print.register');

Route::get('/{locale}/print/assessments/{assessment}', PrintAssessmentController::class)
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(['auth', SetFilamentLocale::class])
    ->name('print.assessment');

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
