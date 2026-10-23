<?php

namespace App\Http\Controllers;

use App\Models\PedagogicalMemo;
use App\Services\PedagogicalMemoPrintService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PrintPedagogicalMemoController extends Controller
{
    public function __invoke(
        string $locale,
        PedagogicalMemo $memo,
        PedagogicalMemoPrintService $printer,
    ): View {
        // The route is available to any authenticated user, but each memo
        // remains scoped to its owner (admins bypass the policy).
        Gate::authorize('print', $memo);

        return view('print.pedagogical-memo', $printer->viewData($memo));
    }
}
