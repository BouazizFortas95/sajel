<?php

namespace App\Http\Controllers;

use App\Models\BudgetCommitment;
use App\Models\PaymentMandate;
use App\Models\User;
use App\Services\SchoolBudgetService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolBudgetController extends Controller
{
    public function __construct(
        private readonly SchoolBudgetService $service,
        private readonly BrowsershotFactory $browsershot
    ) {}

    // ─── Budget Execution Ledger Print (A4 Landscape) ───────────

    public function printBudgetLedger(
        Request $request,
        string $locale,
        ?int $fiscalYear = null
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolBudget(), 403);

        $year = $fiscalYear ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $budgetType = $request->query('budget_type');
        $data = $this->service->assembleBudgetLedgerData($year, $budgetType);

        return view('print.school-budget-execution-ledger', $data);
    }

    public function printBudgetLedgerPdf(
        Request $request,
        string $locale,
        ?int $fiscalYear = null
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolBudget(), 403);

        $year = $fiscalYear ?? (int) now(config('app.timezone', 'Africa/Algiers'))->year;
        $budgetType = $request->query('budget_type');
        $data = $this->service->assembleBudgetLedgerData($year, $budgetType);
        $filename = "budget-execution-ledger-{$year}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.school-budget-execution-ledger', $data)
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
            return response()->streamDownload(
                function () use ($request, $locale, $year): void {
                    echo $this->printBudgetLedger($request, $locale, $year)->render();
                },
                "budget-execution-ledger-{$year}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Purchase Order Print (Bon de Commande - A4 Portrait) ───

    public function printPurchaseOrder(
        Request $request,
        string $locale,
        BudgetCommitment $commitment
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolBudget(), 403);

        $data = $this->service->assemblePurchaseOrderData($commitment);

        return view('print.school-purchase-order', $data);
    }

    public function printPurchaseOrderPdf(
        Request $request,
        string $locale,
        BudgetCommitment $commitment
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolBudget(), 403);

        $data = $this->service->assemblePurchaseOrderData($commitment);
        $cleanRef = str_replace(['/', '\\'], '-', $commitment->order_reference);
        $filename = "purchase-order-{$cleanRef}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.school-purchase-order', $data)
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
            return response()->streamDownload(
                function () use ($request, $locale, $commitment): void {
                    echo $this->printPurchaseOrder($request, $locale, $commitment)->render();
                },
                "purchase-order-{$cleanRef}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }

    // ─── Payment Mandate Print (Mandat de Paiement - A4 Portrait)

    public function printMandate(
        Request $request,
        string $locale,
        PaymentMandate $mandate
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolBudget(), 403);

        $data = $this->service->assemblePaymentMandateData($mandate);

        return view('print.school-payment-mandate', $data);
    }

    public function printMandatePdf(
        Request $request,
        string $locale,
        PaymentMandate $mandate
    ): StreamedResponse|Response {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageSchoolBudget(), 403);

        $data = $this->service->assemblePaymentMandateData($mandate);
        $cleanRef = str_replace(['/', '\\'], '-', $mandate->mandate_number);
        $filename = "payment-mandate-{$cleanRef}.pdf";

        try {
            $pdf = $this->browsershot
                ->fromView('print.school-payment-mandate', $data)
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
            return response()->streamDownload(
                function () use ($request, $locale, $mandate): void {
                    echo $this->printMandate($request, $locale, $mandate)->render();
                },
                "payment-mandate-{$cleanRef}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
