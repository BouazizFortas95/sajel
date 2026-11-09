<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EvaluationWorkReceiptsHub;
use App\Filament\Pages\PedagogicalPlanReceiptsHub;
use App\Services\EvaluationWorkReceiptService;
use App\Services\PedagogicalPlanReceiptService;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class PedagogicalReceiptsComplianceWidget extends Widget
{
    protected static string $view = 'filament.widgets.pedagogical-receipts-compliance-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && ($user->isAdmin() || $user->isPrincipal() || $user->isCensor() || $user->isLeadership() || $user->isDeveloper());
    }

    public function render(): View
    {
        $planService = app(PedagogicalPlanReceiptService::class);
        $evalService = app(EvaluationWorkReceiptService::class);

        $planStats = $planService->getSummaryStats();
        $evalStats = $evalService->getSummaryStats();

        return view(static::$view, [
            'planStats' => $planStats,
            'evalStats' => $evalStats,
            'plansHubUrl' => PedagogicalPlanReceiptsHub::getUrl(),
            'evalHubUrl' => EvaluationWorkReceiptsHub::getUrl(),
            'plansPdfUrl' => route('print.pedagogical-plan-receipts.pdf', ['locale' => app()->getLocale()]),
            'evalPdfUrl' => route('print.evaluation-work-receipts.pdf', ['locale' => app()->getLocale()]),
        ]);
    }
}

