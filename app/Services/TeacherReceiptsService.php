<?php

namespace App\Services;

use App\Models\EvaluationWorkReceipt;
use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Support\BrowsershotFactory;

class TeacherReceiptsService
{
    /**
     * Build all view data for a teacher's pedagogical and evaluation receipt profile.
     *
     * @return array<string, mixed>
     */
    public function viewData(int|User $teacher, ?string $academicYear = null): array
    {
        $teacherModel = $teacher instanceof User
            ? $teacher
            : User::with(['primarySubject', 'secondarySubject'])->findOrFail($teacher);

        $school = SchoolSetting::current();
        $year = $academicYear ?: ($school?->academic_year ?? '2026-2027');

        $planReceipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $teacherModel->id,
            'academic_year' => $year,
        ]);

        $evalReceipt = EvaluationWorkReceipt::firstOrNew([
            'user_id' => $teacherModel->id,
            'academic_year' => $year,
        ]);

        // Monthly plans
        $monthlyPlans = [];
        $submittedMonthsCount = 0;
        foreach (PedagogicalPlanReceipt::MONTHS as $key => $label) {
            $isSubmitted = (bool) $planReceipt->{$key.'_submitted'};
            $subDate = $planReceipt->{$key.'_date'};
            if ($isSubmitted) {
                $submittedMonthsCount++;
            }
            $monthlyPlans[$key] = [
                'key' => $key,
                'label' => $label,
                'submitted' => $isSubmitted,
                'date' => $subDate,
            ];
        }

        $monthsPercentage = round(($submittedMonthsCount / 9) * 100, 1);

        // Evaluation works
        $evalDefinitions = [
            'diagnostic_test' => ['label' => 'اختبار كشف المستوى', 'category' => 'تقويم تشخيصي', 'order' => 1],
            'homework_1' => ['label' => 'الواجب المنزلي (1)', 'category' => 'الفصل الأول', 'order' => 2],
            'quiz_1' => ['label' => 'الفرض المحروس (1)', 'category' => 'الفصل الأول', 'order' => 3],
            'exam_1' => ['label' => 'اختبار الفصل الأول', 'category' => 'الفصل الأول', 'order' => 4],
            'homework_2' => ['label' => 'الواجب المنزلي (2)', 'category' => 'الفصل الثاني', 'order' => 5],
            'quiz_2' => ['label' => 'الفرض المحروس (2)', 'category' => 'الفصل الثاني', 'order' => 6],
            'exam_2' => ['label' => 'اختبار الفصل الثاني', 'category' => 'الفصل الثاني', 'order' => 7],
            'homework_3' => ['label' => 'الواجب المنزلي (3)', 'category' => 'الفصل الثالث', 'order' => 8],
            'quiz_3' => ['label' => 'الفرض المحروس (3)', 'category' => 'الفصل الثالث', 'order' => 9],
            'exam_3' => ['label' => 'اختبار الفصل الثالث', 'category' => 'الفصل الثالث', 'order' => 10],
        ];

        $evalItems = [];
        $submittedEvalsCount = 0;
        foreach ($evalDefinitions as $key => $meta) {
            $isSubmitted = (bool) $evalReceipt->{$key.'_submitted'};
            $subDate = $evalReceipt->{$key.'_date'};
            if ($isSubmitted) {
                $submittedEvalsCount++;
            }
            $evalItems[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'category' => $meta['category'],
                'submitted' => $isSubmitted,
                'date' => $subDate,
            ];
        }

        $evalsPercentage = round(($submittedEvalsCount / 10) * 100, 1);

        // Total compliance score (20 items in total)
        $totalItems = 20;
        $totalSubmitted = ($planReceipt->annual_plan_submitted ? 1 : 0) + $submittedMonthsCount + $submittedEvalsCount;
        $overallPercentage = round(($totalSubmitted / $totalItems) * 100, 1);

        return [
            'teacher' => $teacherModel,
            'school' => $school,
            'academicYear' => $year,
            'planReceipt' => $planReceipt,
            'evalReceipt' => $evalReceipt,
            'annualPlan' => [
                'submitted' => (bool) $planReceipt->annual_plan_submitted,
                'date' => $planReceipt->annual_plan_date,
                'director_visa' => (bool) $planReceipt->director_visa,
                'director_visa_at' => $planReceipt->director_visa_at,
                'censor_visa' => (bool) $planReceipt->censor_visa,
                'censor_visa_at' => $planReceipt->censor_visa_at,
                'notes' => $planReceipt->notes,
            ],
            'monthlyPlans' => $monthlyPlans,
            'submittedMonthsCount' => $submittedMonthsCount,
            'monthsPercentage' => $monthsPercentage,
            'evalItems' => $evalItems,
            'submittedEvalsCount' => $submittedEvalsCount,
            'evalsPercentage' => $evalsPercentage,
            'evalNotes' => $evalReceipt->notes,
            'totalItems' => $totalItems,
            'totalSubmitted' => $totalSubmitted,
            'overallPercentage' => $overallPercentage,
            'generatedAt' => now(),
        ];
    }

    /**
     * Generate an official individual receipt and compliance status PDF voucher.
     */
    public function generatePdf(int|User $teacher, ?string $academicYear = null): string
    {
        $data = $this->viewData($teacher, $academicYear);
        $html = view('print.teacher-receipts-individual', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->portrait()
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    }
}

