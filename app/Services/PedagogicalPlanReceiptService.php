<?php

namespace App\Services;

use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Illuminate\Support\Collection;

class PedagogicalPlanReceiptService
{
    /**
     * Get teachers along with their plan receipts for the given academic year and optional subject.
     */
    public function getTeachersWithReceipts(?int $subjectId = null, ?string $academicYear = null): Collection
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $query = User::query()
            ->where('role', 'teacher')
            ->with([
                'primarySubject',
                'secondarySubject',
                'pedagogicalPlanReceipts' => fn ($q) => $q->where('academic_year', $year),
            ])
            ->orderBy('primary_subject_id')
            ->orderBy('name');

        if ($subjectId) {
            $query->where(function ($q) use ($subjectId) {
                $q->where('primary_subject_id', $subjectId)
                    ->orWhere('secondary_subject_id', $subjectId);
            });
        }

        $teachers = $query->get();

        // Ensure every teacher has a materialized receipt instance
        foreach ($teachers as $teacher) {
            $receipt = $teacher->pedagogicalPlanReceipts->first();
            if (! $receipt) {
                $receipt = new PedagogicalPlanReceipt([
                    'user_id' => $teacher->id,
                    'academic_year' => $year,
                    'annual_plan_submitted' => false,
                ]);
                $teacher->setRelation('pedagogicalPlanReceipts', collect([$receipt]));
            }
        }

        return $teachers;
    }

    /**
     * Group teachers by their primary educational subject.
     */
    public function getTeachersGroupedBySubject(?int $subjectId = null, ?string $academicYear = null): Collection
    {
        $teachers = $this->getTeachersWithReceipts($subjectId, $academicYear);

        return $teachers->groupBy(function ($teacher) {
            return $teacher->primarySubject?->name ?? 'غير محدد';
        });
    }

    /**
     * Toggle submission status for annual plan or a specific month.
     */
    public function togglePlan(
        int $userId,
        string $planKey,
        bool $submitted,
        ?string $date = null,
        ?string $academicYear = null
    ): PedagogicalPlanReceipt {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        $subCol = ($planKey === 'annual') ? 'annual_plan_submitted' : strtolower($planKey).'_submitted';
        $dateCol = ($planKey === 'annual') ? 'annual_plan_date' : strtolower($planKey).'_date';

        $receipt->{$subCol} = $submitted;
        $receipt->{$dateCol} = $submitted ? ($date ?: now()->format('Y-m-d')) : null;

        $receipt->save();

        return $receipt;
    }

    /**
     * Update notes for a teacher's plan receipt.
     */
    public function updateNotes(int $userId, ?string $notes, ?string $academicYear = null): PedagogicalPlanReceipt
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        $receipt->notes = $notes;
        $receipt->save();

        return $receipt;
    }

    /**
     * Bulk mark annual plan submission for multiple teachers.
     */
    public function bulkMarkAnnualPlan(
        array $userIds,
        bool $submitted,
        ?string $date = null,
        ?string $academicYear = null
    ): int {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $targetDate = $submitted ? ($date ?: now()->format('Y-m-d')) : null;
        $count = 0;

        foreach ($userIds as $userId) {
            $receipt = PedagogicalPlanReceipt::firstOrNew([
                'user_id' => $userId,
                'academic_year' => $year,
            ]);

            $receipt->annual_plan_submitted = $submitted;
            $receipt->annual_plan_date = $targetDate;
            $receipt->save();
            $count++;
        }

        return $count;
    }

    /**
     * Toggle director or censor visa.
     */
    public function toggleVisa(int $userId, string $role, bool $signed, ?string $academicYear = null): PedagogicalPlanReceipt
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        if ($role === 'director') {
            $receipt->director_visa = $signed;
            $receipt->director_visa_at = $signed ? now() : null;
        } elseif ($role === 'censor') {
            $receipt->censor_visa = $signed;
            $receipt->censor_visa_at = $signed ? now() : null;
        }

        $receipt->save();

        return $receipt;
    }

    /**
     * Compute summary statistics for pedagogical plan receipts.
     */
    public function getSummaryStats(?string $academicYear = null): array
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $totalTeachers = User::where('role', 'teacher')->count();

        $annualSubmitted = PedagogicalPlanReceipt::where('academic_year', $year)
            ->where('annual_plan_submitted', true)
            ->count();

        $currentMonthKey = $this->getCurrentAcademicMonthKey();
        $currentMonthSubmitted = 0;

        if ($currentMonthKey) {
            $currentMonthSubmitted = PedagogicalPlanReceipt::where('academic_year', $year)
                ->where($currentMonthKey.'_submitted', true)
                ->count();
        }

        $annualPercentage = $totalTeachers > 0 ? round(($annualSubmitted / $totalTeachers) * 100, 1) : 0;
        $monthPercentage = $totalTeachers > 0 ? round(($currentMonthSubmitted / $totalTeachers) * 100, 1) : 0;

        return [
            'total_teachers' => $totalTeachers,
            'annual_submitted' => $annualSubmitted,
            'annual_percentage' => $annualPercentage,
            'current_month_key' => $currentMonthKey,
            'current_month_label' => $currentMonthKey ? PedagogicalPlanReceipt::MONTHS[$currentMonthKey] : null,
            'current_month_submitted' => $currentMonthSubmitted,
            'current_month_percentage' => $monthPercentage,
        ];
    }

    /**
     * Assemble full printable dataset conforming to the 2-page Algerian ministerial model.
     */
    public function assemblePrintData(?int $subjectId = null, ?string $academicYear = null): array
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $school = SchoolSetting::current();
        $groupedTeachers = $this->getTeachersGroupedBySubject($subjectId, $year);

        $selectedSubject = $subjectId ? Subject::find($subjectId) : null;

        return [
            'school' => $school,
            'academic_year' => $year,
            'groupedTeachers' => $groupedTeachers,
            'selectedSubject' => $selectedSubject,
            'months' => PedagogicalPlanReceipt::MONTHS,
            'stats' => $this->getSummaryStats($year),
            'printed_at' => now()->locale('ar')->isoFormat('LL'),
        ];
    }

    /**
     * Generate PDF binary using Browsershot.
     */
    public function generatePdf(?int $subjectId = null, ?string $academicYear = null): string
    {
        $data = $this->assemblePrintData($subjectId, $academicYear);
        $html = view('print.pedagogical-plan-receipts', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(8, 10, 8, 10)
            ->showBackground()
            ->pdf();
    }

    /**
     * Get current active academic year from school settings or fallback.
     */
    public function getCurrentAcademicYear(): string
    {
        return SchoolSetting::current()?->academic_year ?? '2026-2027';
    }

    /**
     * Resolve the current school calendar month key (September to May).
     */
    public function getCurrentAcademicMonthKey(): ?string
    {
        $monthNum = (int) now()->format('n');

        $map = [
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
        ];

        return $map[$monthNum] ?? 'september';
    }
}
