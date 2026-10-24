<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TeacherPortfolioService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherPortfolioController extends Controller
{
    public function print(
        Request $request,
        string $locale,
        User $user,
        TeacherPortfolioService $service,
    ): View {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);
        Gate::authorize('view', $user); // simplified policy

        return view('print.teacher-portfolio', $service->viewData($user));
    }

    public function pdf(
        Request $request,
        string $locale,
        User $user,
        TeacherPortfolioService $service,
    ): StreamedResponse {
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);
        Gate::authorize('view', $user);

        $pdf = $service->generatePdf($user);

        return response()->streamDownload(
            fn () => print ($pdf),
            'teacher-portfolio-'.$user->name.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
