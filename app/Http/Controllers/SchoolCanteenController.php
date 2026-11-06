<?php

namespace App\Http\Controllers;

use App\Models\CanteenDailySheet;
use App\Models\CanteenWeeklyMenu;
use App\Models\User;
use App\Services\SchoolCanteenService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolCanteenController extends Controller
{
    /**
     * Preview official Daily Consumption Sheet (ورقة الاستهلاك اليومي للمطعم - FJC).
     */
    public function dailySheet(
        Request $request,
        string $locale,
        CanteenDailySheet $sheet,
        SchoolCanteenService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->canManageCanteen()), 403);

        $data = $service->assembleDailySheetData($sheet);

        return view('print.canteen-daily-sheet', $data);
    }

    /**
     * Stream download Daily Consumption Sheet as PDF.
     */
    public function dailySheetPdf(
        Request $request,
        string $locale,
        CanteenDailySheet $sheet,
        SchoolCanteenService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->canManageCanteen()), 403);

        try {
            $pdf = $service->generateDailyConsumptionSheetPdf($sheet);
            $filename = sprintf('feuille-journaliere-consommation-%s.pdf', $sheet->sheet_date->format('Y-m-d'));

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
            return response()->streamDownload(
                function () use ($request, $locale, $sheet, $service): void {
                    echo $this->dailySheet($request, $locale, $sheet, $service)->render();
                },
                sprintf('feuille-journaliere-consommation-%s.html', $sheet->sheet_date->format('Y-m-d')),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Preview official Weekly Canteen Menu Poster.
     */
    public function weeklyMenu(
        Request $request,
        string $locale,
        CanteenWeeklyMenu $menu,
        SchoolCanteenService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->canManageCanteen() || $user->isTeacher()), 403);

        $data = $service->assembleWeeklyMenuData($menu);

        return view('print.canteen-weekly-menu', $data);
    }

    /**
     * Stream download Weekly Canteen Menu as PDF.
     */
    public function weeklyMenuPdf(
        Request $request,
        string $locale,
        CanteenWeeklyMenu $menu,
        SchoolCanteenService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->canManageCanteen() || $user->isTeacher()), 403);

        try {
            $pdf = $service->generateWeeklyMenuPdf($menu);
            $filename = sprintf('menu-semaine-sem-%d.pdf', $menu->week_number);

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
            return response()->streamDownload(
                function () use ($request, $locale, $menu, $service): void {
                    echo $this->weeklyMenu($request, $locale, $menu, $service)->render();
                },
                sprintf('menu-semaine-sem-%d.html', $menu->week_number),
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    /**
     * Preview periodic catering report for Directorate of Education.
     */
    public function periodicBilan(
        Request $request,
        string $locale,
        SchoolCanteenService $service
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->canManageCanteen()), 403);

        $year = $request->query('year');
        $month = $request->query('month') ? (int) $request->query('month') : null;

        $data = $service->assemblePeriodicBilanData($year, $month);

        return view('print.canteen-periodic-bilan', $data);
    }

    /**
     * Stream download periodic catering report as PDF.
     */
    public function periodicBilanPdf(
        Request $request,
        string $locale,
        SchoolCanteenService $service
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->canManageCanteen()), 403);

        $year = $request->query('year');
        $month = $request->query('month') ? (int) $request->query('month') : null;

        try {
            $pdf = $service->generatePeriodicBilanPdf($year, $month);
            $filename = 'bilan-restauration-scolaire.pdf';

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
            return response()->streamDownload(
                function () use ($request, $locale, $service): void {
                    echo $this->periodicBilan($request, $locale, $service)->render();
                },
                'bilan-restauration-scolaire.html',
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
