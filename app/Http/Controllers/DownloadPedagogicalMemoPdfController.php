<?php

namespace App\Http\Controllers;

use App\Models\PedagogicalMemo;
use App\Services\PedagogicalMemoPrintService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DownloadPedagogicalMemoPdfController extends Controller
{
    public function __invoke(
        string $locale,
        PedagogicalMemo $memo,
        PedagogicalMemoPrintService $printer,
    ): StreamedResponse {
        Gate::authorize('downloadPdf', $memo);

        try {
            return $printer->pdfResponse($memo);
        } catch (Throwable $exception) {
            report($exception);

            abort(500, __('تعذر إنشاء ملف PDF.'));
        }
    }
}
