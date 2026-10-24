<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\ParentPortalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ParentPortalController extends Controller
{
    public function portal(
        Request $request,
        string $locale,
        Student $student,
        ParentPortalService $service,
    ): View {
        $data = $service->viewData($student);
        $data['portalUrl'] = URL::temporarySignedRoute(
            'parent.student-portal',
            now()->addDays(90),
            ['locale' => $locale, 'student' => $student]
        );

        return view('parent.student-portal', $data);
    }
}
