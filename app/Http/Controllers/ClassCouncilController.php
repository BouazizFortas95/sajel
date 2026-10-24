<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\ClassCouncilService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassCouncilController extends Controller
{
    public function print(
        Request $request,
        string $locale,
        Classroom $classroom,
        ClassCouncilService $service,
    ): View {
        $viewer = $request->user();
        Gate::authorize('view', $classroom);

        return view('print.class-council', $service->viewData($classroom));
    }

    public function pdf(
        Request $request,
        string $locale,
        Classroom $classroom,
        ClassCouncilService $service,
    ): StreamedResponse {
        $viewer = $request->user();
        Gate::authorize('view', $classroom);

        $pdf = $service->generatePdf($classroom);

        return response()->streamDownload(
            fn () => print ($pdf),
            'class-council-'.$classroom->name.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
