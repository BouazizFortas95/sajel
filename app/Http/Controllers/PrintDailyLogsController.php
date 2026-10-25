<?php

namespace App\Http\Controllers;

use App\Services\DailyLogRegisterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintDailyLogsController extends Controller
{
    public function __invoke(Request $request, DailyLogRegisterService $register): View
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $user = $request->user();

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        $logs = $register->completedLogs($ids, $user);

        return view('print.register', $register->viewData($logs, $user));
    }
}
