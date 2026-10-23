<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Subject;
use App\Services\OfficialReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OfficialReportController extends Controller
{
    public function timetable(
        Request $request,
        string $locale,
        OfficialReportService $reports,
    ): View {
        [$teacherId, $classroomId] = $this->timetableFilters($request);

        return view('print.timetable', $reports->timetableViewData(
            $request->user(),
            $teacherId,
            $classroomId,
        ));
    }

    public function timetablePdf(
        Request $request,
        string $locale,
        OfficialReportService $reports,
    ): StreamedResponse {
        [$teacherId, $classroomId] = $this->timetableFilters($request);

        return $this->pdf(
            $reports,
            'print.timetable',
            $reports->timetableViewData($request->user(), $teacherId, $classroomId),
            'جدول-التوقيت-'.now()->format('Y-m-d').'.pdf',
            landscape: true,
        );
    }

    public function attendanceSummary(
        Request $request,
        string $locale,
        OfficialReportService $reports,
    ): View {
        [$classroom, $group, $subject, $from, $to] = $this->attendanceFilters($request);

        return view('print.attendance-summary', $reports->attendanceViewData(
            $request->user(),
            $classroom,
            $group,
            $subject,
            $from,
            $to,
        ));
    }

    public function attendanceSummaryPdf(
        Request $request,
        string $locale,
        OfficialReportService $reports,
    ): StreamedResponse {
        [$classroom, $group, $subject, $from, $to] = $this->attendanceFilters($request);

        return $this->pdf(
            $reports,
            'print.attendance-summary',
            $reports->attendanceViewData($request->user(), $classroom, $group, $subject, $from, $to),
            'تقرير-الحضور-'.now()->format('Y-m-d').'.pdf',
            landscape: false,
        );
    }

    public function gradeTranscript(
        Request $request,
        string $locale,
        OfficialReportService $reports,
    ): View {
        [$classroom, $group, $subject, $from, $to] = $this->transcriptFilters($request);

        return view('print.grade-transcript', $reports->transcriptViewData(
            $request->user(),
            $classroom,
            $group,
            $subject,
            $from,
            $to,
        ));
    }

    public function gradeTranscriptPdf(
        Request $request,
        string $locale,
        OfficialReportService $reports,
    ): StreamedResponse {
        [$classroom, $group, $subject, $from, $to] = $this->transcriptFilters($request);

        return $this->pdf(
            $reports,
            'print.grade-transcript',
            $reports->transcriptViewData($request->user(), $classroom, $group, $subject, $from, $to),
            'كشف-النقاط-'.now()->format('Y-m-d').'.pdf',
            landscape: true,
        );
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function timetableFilters(Request $request): array
    {
        $validated = $request->validate([
            'teacher' => ['nullable', 'integer'],
            'classroom' => ['nullable', 'integer'],
        ]);

        return [
            isset($validated['teacher']) ? (int) $validated['teacher'] : null,
            isset($validated['classroom']) ? (int) $validated['classroom'] : null,
        ];
    }

    /**
     * @return array{0: Classroom, 1: Group|null, 2: Subject|null, 3: string, 4: string}
     */
    private function attendanceFilters(Request $request): array
    {
        $validated = $request->validate([
            'classroom' => ['required', 'integer'],
            'group' => ['nullable', 'integer'],
            'subject' => ['nullable', 'integer'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return [
            Classroom::query()->with('level.educationPhase')->findOrFail($validated['classroom']),
            filled($validated['group'] ?? null) ? Group::query()->findOrFail($validated['group']) : null,
            filled($validated['subject'] ?? null) ? Subject::query()->findOrFail($validated['subject']) : null,
            $validated['from'],
            $validated['to'],
        ];
    }

    /**
     * @return array{0: Classroom, 1: Group|null, 2: Subject, 3: string, 4: string}
     */
    private function transcriptFilters(Request $request): array
    {
        $validated = $request->validate([
            'classroom' => ['required', 'integer'],
            'group' => ['nullable', 'integer'],
            'subject' => ['required', 'integer'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return [
            Classroom::query()->with('level.educationPhase')->findOrFail($validated['classroom']),
            filled($validated['group'] ?? null) ? Group::query()->findOrFail($validated['group']) : null,
            Subject::query()->findOrFail($validated['subject']),
            $validated['from'],
            $validated['to'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pdf(
        OfficialReportService $reports,
        string $view,
        array $data,
        string $filename,
        bool $landscape,
    ): StreamedResponse {
        try {
            return $reports->pdfResponse($view, $data, $filename, $landscape);
        } catch (Throwable $exception) {
            report($exception);

            abort(500, __('تعذر إنشاء ملف PDF.'));
        }
    }
}
