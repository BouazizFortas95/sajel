<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AnnualCurriculumDistributionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnualCurriculumDistributionController extends Controller
{
    public function show(
        Request $request,
        string $locale,
        AnnualCurriculumDistributionService $service,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $subjectId = $request->query('subject') ? (int) $request->query('subject') : null;
        $classroomId = $request->query('classroom') ? (int) $request->query('classroom') : null;
        $teacherId = $user->isTeacher() ? $user->id : ($request->query('teacher') ? (int) $request->query('teacher') : null);
        $academicYear = $request->query('year');
        $level = $request->query('level');
        $mode = (string) $request->query('mode', 'annual');
        $month = $request->query('month');

        $data = $service->getDistributionData($subjectId, $classroomId, $teacherId, $academicYear, $level, $mode, $month);

        return view('print.annual-curriculum-distribution', $data);
    }

    public function pdf(
        Request $request,
        string $locale,
        AnnualCurriculumDistributionService $service,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $subjectId = $request->query('subject') ? (int) $request->query('subject') : null;
        $classroomId = $request->query('classroom') ? (int) $request->query('classroom') : null;
        $teacherId = $user->isTeacher() ? $user->id : ($request->query('teacher') ? (int) $request->query('teacher') : null);
        $academicYear = $request->query('year');
        $level = $request->query('level');
        $mode = (string) $request->query('mode', 'annual');
        $month = $request->query('month');

        $data = $service->getDistributionData($subjectId, $classroomId, $teacherId, $academicYear, $level, $mode, $month);

        return $service->pdfResponse($data);
    }
}
