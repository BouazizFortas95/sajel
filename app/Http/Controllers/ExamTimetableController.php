<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\ExamConflictService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExamTimetableController extends Controller
{
    public function print(
        Request $request,
        string $locale,
        Classroom $classroom,
        ExamConflictService $service,
    ): View {
        Gate::authorize('view', $classroom);
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $trimester = (int) $request->query('trimester', 1);
        $period = $service->getTrimesterPeriod($trimester);
        $assessments = $service->getClassroomAssessments($classroom->id, $period['from'], $period['to']);
        $conflicts = $service->detectClassroomConflicts($classroom->id, $period['from'], $period['to']);

        return view('print.exam-timetable', [
            'classroom' => $classroom,
            'assessments' => $assessments,
            'trimester' => $trimester,
            'period' => $period,
            'conflicts' => $conflicts,
            'schoolSetting' => SchoolSetting::current(),
            'academicYear' => $classroom->academic_year ?? SchoolSetting::current()?->academic_year ?? '2026/2027',
        ]);
    }

    public function pdf(
        Request $request,
        string $locale,
        Classroom $classroom,
        ExamConflictService $service,
    ): StreamedResponse {
        Gate::authorize('view', $classroom);
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $trimester = (int) $request->query('trimester', 1);

        try {
            $pdf = $service->generatePdf($classroom, $trimester);

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                'exam-timetable-class-'.$classroom->id.'-trimester-'.$trimester.'.pdf',
                ['Content-Type' => 'application/pdf'],
            );
        } catch (Throwable $exception) {
            report($exception);

            abort(500, __('تعذر إنشاء ملف PDF.'));
        }
    }
}
