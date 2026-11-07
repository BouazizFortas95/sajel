<?php

namespace App\Http\Controllers;

use App\Models\CentralHeatingLog;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\WaterTankHygieneInspection;
use App\Services\SchoolMaintenanceService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolMaintenanceController extends Controller
{
    public function __construct(
        private readonly SchoolMaintenanceService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── 1. Work Order Print (A4 Portrait) ───────────────────────────────

    public function printWorkOrder(
        Request $request,
        string $locale,
        MaintenanceWorkOrder $workOrder
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $data = $this->service->assembleWorkOrderData($workOrder);

        return view('print.maintenance-work-order', $data);
    }

    public function printWorkOrderPdf(
        Request $request,
        string $locale,
        MaintenanceWorkOrder $workOrder
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $data = $this->service->assembleWorkOrderData($workOrder);
        $cleanNumber = str_replace(['/', '\\'], '-', $workOrder->work_order_number);
        $filename = "work-order-{$cleanNumber}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.maintenance-work-order', $data)
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
            return response()->view('print.maintenance-work-order', $data);
        }
    }

    // ─── 2. Heating Safety PV (A4 Landscape) ─────────────────────────────

    public function printHeatingSafetyPv(
        Request $request,
        string $locale,
        CentralHeatingLog $log
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $data = $this->service->assembleHeatingSafetyPvData($log);

        return view('print.maintenance-heating-safety-pv', $data);
    }

    public function printHeatingSafetyPvPdf(
        Request $request,
        string $locale,
        CentralHeatingLog $log
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $data = $this->service->assembleHeatingSafetyPvData($log);
        $cleanRef = str_replace(['/', '\\'], '-', $log->log_reference);
        $filename = "heating-safety-pv-{$cleanRef}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.maintenance-heating-safety-pv', $data)
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
            return response()->view('print.maintenance-heating-safety-pv', $data);
        }
    }

    // ─── 3. Water Hygiene Certificate / Register (A4 Landscape) ──────────

    public function printWaterHygieneRegister(
        Request $request,
        string $locale,
        WaterTankHygieneInspection $inspection
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $data = $this->service->assembleWaterHygieneCertificateData($inspection);

        return view('print.maintenance-water-hygiene-register', $data);
    }

    public function printWaterHygieneRegisterPdf(
        Request $request,
        string $locale,
        WaterTankHygieneInspection $inspection
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $data = $this->service->assembleWaterHygieneCertificateData($inspection);
        $cleanRef = str_replace(['/', '\\'], '-', $inspection->inspection_reference);
        $filename = "water-hygiene-{$cleanRef}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.maintenance-water-hygiene-register', $data)
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
            return response()->view('print.maintenance-water-hygiene-register', $data);
        }
    }

    // ─── 4. Energy Consumption Bilan (A4 Landscape) ──────────────────────

    public function printEnergyConsumptionBilan(
        Request $request,
        string $locale,
        string $academicYear
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $year = str_replace('-', '/', $academicYear);
        $data = $this->service->assembleEnergyConsumptionBilanData($year);

        return view('print.maintenance-energy-consumption-bilan', $data);
    }

    public function printEnergyConsumptionBilanPdf(
        Request $request,
        string $locale,
        string $academicYear
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolMaintenance(), 403);

        $year = str_replace('-', '/', $academicYear);
        $data = $this->service->assembleEnergyConsumptionBilanData($year);
        $filename = "energy-consumption-bilan-{$academicYear}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.maintenance-energy-consumption-bilan', $data)
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
            return response()->view('print.maintenance-energy-consumption-bilan', $data);
        }
    }
}
