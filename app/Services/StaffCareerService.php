<?php

namespace App\Services;

use App\Enums\AdvancementRhythm;
use App\Enums\StaffEvaluationAppreciation;
use App\Models\CareerAdvancement;
use App\Models\SchoolSetting;
use App\Models\StaffEvaluation;
use App\Models\StaffMovement;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffCareerService
{
    /**
     * Compute full seniority breakdowns for a staff member.
     */
    public function calculateSeniority(User $user, ?Carbon $targetDate = null): array
    {
        $asOf = $targetDate ?? now();

        return [
            'general_seniority' => $user->getGeneralSeniority($asOf),
            'grade_seniority' => $user->getGradeSeniority($asOf),
            'step_seniority' => $user->getStepSeniority($asOf),
        ];
    }

    /**
     * Project step advancement eligibility and key dates.
     */
    public function projectAdvancementEligibility(User $user, ?Carbon $asOfDate = null): array
    {
        $asOf = $asOfDate ?? now();
        $currentStep = (int) ($user->current_step ?? 0);
        $hasReachedMax = $currentStep >= 12;

        $nextStep = $hasReachedMax ? 12 : $currentStep + 1;
        $stepDate = $user->current_step_date ? Carbon::parse($user->current_step_date) : null;

        if (! $stepDate) {
            return [
                'has_reached_max' => $hasReachedMax,
                'current_step' => $currentStep,
                'next_step' => $nextStep,
                'current_step_date' => null,
                'eligible_at_minimum' => null,
                'eligible_at_medium' => null,
                'eligible_at_maximum' => null,
                'is_eligible_minimum' => false,
                'is_eligible_medium' => false,
                'is_eligible_maximum' => false,
                'suggested_rhythm' => AdvancementRhythm::Medium,
                'is_eligible' => false,
            ];
        }

        // Algerian Public Civil Service Statutory Advancement Rhythms:
        // Minimum (دنيا): 30 months (2.5 years)
        // Medium (متوسطة): 36 months (3.0 years)
        // Maximum (قصوى): 42 months (3.5 years)
        $eligibleMin = $stepDate->copy()->addMonths(30);
        $eligibleMed = $stepDate->copy()->addMonths(36);
        $eligibleMax = $stepDate->copy()->addMonths(42);

        $isEligibleMin = $asOf->greaterThanOrEqualTo($eligibleMin);
        $isEligibleMed = $asOf->greaterThanOrEqualTo($eligibleMed);
        $isEligibleMax = $asOf->greaterThanOrEqualTo($eligibleMax);

        // Determine suggested rhythm based on latest evaluation
        $latestEvaluation = $user->latestEvaluation;
        $score = $latestEvaluation ? (float) $latestEvaluation->composite_score : 14.0;

        $suggestedRhythm = match (true) {
            $score >= 16.0 => AdvancementRhythm::Minimum,
            $score >= 12.0 => AdvancementRhythm::Medium,
            default => AdvancementRhythm::Maximum,
        };

        $isCurrentlyEligible = match ($suggestedRhythm) {
            AdvancementRhythm::Minimum => $isEligibleMin,
            AdvancementRhythm::Medium => $isEligibleMed,
            AdvancementRhythm::Maximum => $isEligibleMax,
        };

        return [
            'has_reached_max' => $hasReachedMax,
            'current_step' => $currentStep,
            'next_step' => $nextStep,
            'current_step_date' => $stepDate,
            'eligible_at_minimum' => $eligibleMin,
            'eligible_at_medium' => $eligibleMed,
            'eligible_at_maximum' => $eligibleMax,
            'is_eligible_minimum' => $isEligibleMin,
            'is_eligible_medium' => $isEligibleMed,
            'is_eligible_maximum' => $isEligibleMax,
            'suggested_rhythm' => $suggestedRhythm,
            'is_eligible' => (! $hasReachedMax && $isCurrentlyEligible),
        ];
    }

    /**
     * Synchronize and record annual advancement proposals for all eligible staff.
     */
    public function syncAdvancementProjections(int $targetYear = 2026): int
    {
        $asOf = Carbon::create($targetYear, 12, 31);
        $users = User::whereNotNull('current_step_date')
            ->where('current_step', '<', 12)
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $projection = $this->projectAdvancementEligibility($user, $asOf);
            if (! $projection['eligible_at_minimum']) {
                continue;
            }

            // Find or create advancement proposal
            $advancement = CareerAdvancement::where('user_id', $user->id)
                ->where('current_step', $user->current_step)
                ->where('next_step', $projection['next_step'])
                ->first();

            if (! $advancement) {
                $advancement = new CareerAdvancement([
                    'user_id' => $user->id,
                    'current_step' => $user->current_step,
                    'next_step' => $projection['next_step'],
                    'current_step_date' => $projection['current_step_date'],
                    'eligible_at_minimum' => $projection['eligible_at_minimum'],
                    'eligible_at_medium' => $projection['eligible_at_medium'],
                    'eligible_at_maximum' => $projection['eligible_at_maximum'],
                    'advancement_rhythm' => $projection['suggested_rhythm'],
                    'effective_date' => match ($projection['suggested_rhythm']) {
                        AdvancementRhythm::Minimum => $projection['eligible_at_minimum'],
                        AdvancementRhythm::Medium => $projection['eligible_at_medium'],
                        AdvancementRhythm::Maximum => $projection['eligible_at_maximum'],
                    },
                    'financial_impact_date' => match ($projection['suggested_rhythm']) {
                        AdvancementRhythm::Minimum => $projection['eligible_at_minimum'],
                        AdvancementRhythm::Medium => $projection['eligible_at_medium'],
                        AdvancementRhythm::Maximum => $projection['eligible_at_maximum'],
                    },
                    'status' => 'draft',
                    'notes' => __('ترقية مقترحة في جدول الترقية السنوي للدرجات لسنة :year', ['year' => $targetYear]),
                ]);
                $advancement->save();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Save or update an annual staff evaluation.
     */
    public function saveEvaluation(array $data, ?User $evaluator = null): StaffEvaluation
    {
        $userId = $data['user_id'];
        $academicYear = $data['academic_year'] ?? '2025/2026';
        $evaluationYear = (int) ($data['evaluation_year'] ?? substr($academicYear, -4));

        $punctuality = (float) ($data['punctuality_score'] ?? 0.0);
        $performance = (float) ($data['performance_score'] ?? 0.0);
        $conduct = (float) ($data['conduct_score'] ?? 0.0);
        $initiative = (float) ($data['initiative_score'] ?? 0.0);
        $adminScore = round($punctuality + $performance + $conduct + $initiative, 2);

        $pedagogicalScore = isset($data['pedagogical_score']) && $data['pedagogical_score'] !== ''
            ? (float) $data['pedagogical_score']
            : null;

        $compositeScore = $pedagogicalScore !== null
            ? round(($adminScore + $pedagogicalScore) / 2, 2)
            : $adminScore;

        $appreciation = $data['appreciation'] ?? StaffEvaluationAppreciation::fromScore($compositeScore)->value;
        if (is_object($appreciation) && enum_exists(get_class($appreciation))) {
            $appreciation = $appreciation->value;
        }

        return DB::transaction(function () use (
            $userId,
            $academicYear,
            $evaluationYear,
            $adminScore,
            $punctuality,
            $performance,
            $conduct,
            $initiative,
            $pedagogicalScore,
            $data,
            $appreciation,
            $evaluator
        ) {
            return StaffEvaluation::updateOrCreate(
                [
                    'user_id' => $userId,
                    'academic_year' => $academicYear,
                ],
                [
                    'evaluation_year' => $evaluationYear,
                    'administrative_score' => $adminScore,
                    'punctuality_score' => $punctuality,
                    'performance_score' => $performance,
                    'conduct_score' => $conduct,
                    'initiative_score' => $initiative,
                    'pedagogical_score' => $pedagogicalScore,
                    'last_inspection_date' => $data['last_inspection_date'] ?? null,
                    'appreciation' => $appreciation,
                    'evaluator_id' => $evaluator?->id ?? $data['evaluator_id'] ?? null,
                    'evaluator_notes' => $data['evaluator_notes'] ?? null,
                    'evaluated_at' => $data['evaluated_at'] ?? now()->toDateString(),
                    'staff_signature_status' => $data['staff_signature_status'] ?? 'pending',
                    'staff_contestation_notes' => $data['staff_contestation_notes'] ?? null,
                ]
            );
        });
    }

    /**
     * Approve career advancement and update user step.
     */
    public function promoteStaff(
        CareerAdvancement $advancement,
        Carbon $effectiveDate,
        ?string $commissionRef = null,
        ?string $notes = null
    ): bool {
        return DB::transaction(function () use ($advancement, $effectiveDate, $commissionRef, $notes) {
            $advancement->update([
                'status' => 'approved_by_cap',
                'effective_date' => $effectiveDate,
                'financial_impact_date' => $effectiveDate,
                'commission_reference' => $commissionRef ?? $advancement->commission_reference,
                'notes' => $notes ?? $advancement->notes,
            ]);

            $user = $advancement->user;
            $user->update([
                'current_step' => $advancement->next_step,
                'current_step_date' => $effectiveDate,
            ]);

            return true;
        });
    }

    /**
     * Record a staff movement (transfer, secondment, availability, retirement, etc.).
     */
    public function recordMovement(array $data): StaffMovement
    {
        return DB::transaction(function () use ($data) {
            $movementType = $data['movement_type'];
            if (is_object($movementType) && enum_exists(get_class($movementType))) {
                $movementType = $movementType->value;
            }

            return StaffMovement::create([
                'user_id' => $data['user_id'],
                'movement_type' => $movementType,
                'status' => $data['status'] ?? 'approved',
                'effective_date' => $data['effective_date'],
                'end_date' => $data['end_date'] ?? null,
                'decision_number' => $data['decision_number'] ?? null,
                'decision_date' => $data['decision_date'] ?? null,
                'source_institution' => $data['source_institution'] ?? null,
                'target_institution' => $data['target_institution'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }

    /**
     * Assemble evaluation sheet data for official A4 printable bulletin.
     */
    public function assembleEvaluationSheetData(StaffEvaluation $evaluation): array
    {
        $evaluation->loadMissing(['user', 'evaluator']);
        $user = $evaluation->user;
        $evaluator = $evaluation->evaluator;

        $school = SchoolSetting::current();
        $schoolName = $school->school_name ?? 'متوسطة الشهيد العربي بن مهيدي';
        $directorate = $school->directorate_name ?? 'مديرية التربية الوطنية';
        $academicYear = $evaluation->academic_year ?? '2025/2026';

        $seniority = $this->calculateSeniority($user);

        return [
            'evaluation' => $evaluation,
            'user' => $user,
            'evaluator' => $evaluator,
            'school_name' => $schoolName,
            'directorate' => $directorate,
            'academic_year' => $academicYear,
            'general_seniority' => $seniority['general_seniority']['formatted'],
            'grade_seniority' => $seniority['grade_seniority']['formatted'],
            'step_seniority' => $seniority['step_seniority']['formatted'],
            'generated_at' => now()->format('Y/m/d'),
        ];
    }

    /**
     * Assemble annual step advancement ledger for the Joint Administrative Committee (CAP).
     */
    public function assembleAdvancementLedgerData(int $year = 2026): array
    {
        $school = SchoolSetting::current();
        $schoolName = $school->school_name ?? 'متوسطة الشهيد العربي بن مهيدي';
        $directorate = $school->directorate_name ?? 'مديرية التربية الوطنية';

        $advancements = CareerAdvancement::with(['user.latestEvaluation'])
            ->where(function ($q) use ($year) {
                $q->whereYear('eligible_at_minimum', $year)
                    ->orWhereYear('eligible_at_medium', $year)
                    ->orWhereYear('eligible_at_maximum', $year)
                    ->orWhereYear('effective_date', $year);
            })
            ->get()
            ->sortBy(function ($item) {
                return $item->user->name ?? '';
            })
            ->values();

        $minCount = $advancements->where('advancement_rhythm', AdvancementRhythm::Minimum)->count();
        $medCount = $advancements->where('advancement_rhythm', AdvancementRhythm::Medium)->count();
        $maxCount = $advancements->where('advancement_rhythm', AdvancementRhythm::Maximum)->count();

        return [
            'year' => $year,
            'school_name' => $schoolName,
            'directorate' => $directorate,
            'advancements' => $advancements,
            'total_count' => $advancements->count(),
            'min_count' => $minCount,
            'med_count' => $medCount,
            'max_count' => $maxCount,
            'generated_at' => now()->format('Y/m/d'),
        ];
    }

    /**
     * Generate CSV for Joint Administrative Committee (CAP) step advancements.
     */
    public function generateAdvancementLedgerCsv(int $year = 2026): string
    {
        $data = $this->assembleAdvancementLedgerData($year);
        $advancements = $data['advancements'];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $handle = fopen('php://memory', 'r+');

        fputcsv($handle, [
            'الرقم',
            'اللقب والاسم',
            'رقم التعريف الوظيفي',
            'رقم التعريف الوطني NIN',
            'تاريخ الميلاد',
            'تاريخ أول تعيين',
            'الرتبة القانونية',
            'تاريخ التعيين في الرتبة',
            'الدرجة الحالية',
            'تاريخ مفعول الدرجة',
            'الدرجة المقترحة',
            'النقطة الإدارية',
            'النقطة التربوية',
            'المجموع المعتمد',
            'تاريخ الاستحقاق بالدنيا (2.5 سنة)',
            'تاريخ الاستحقاق بالمتوسطة (3 سنوات)',
            'تاريخ الاستحقاق بالقصوى (3.5 سنة)',
            'الوتيرة المعتمدة',
            'تاريخ السريان',
            'حالة الملف',
            'مرجع محضر اللجنة (CAP)',
            'ملاحظات',
        ]);

        foreach ($advancements as $idx => $adv) {
            $user = $adv->user;
            $eval = $user?->latestEvaluation;

            fputcsv($handle, [
                $idx + 1,
                $user?->name ?? '—',
                $user?->registration_number ?? '—',
                $user?->national_id_number ?? '—',
                $user?->birth_date?->format('Y/m/d') ?? '—',
                $user?->recruitment_date?->format('Y/m/d') ?? '—',
                $user?->statutory_grade ?? ($user?->role?->label() ?? '—'),
                $user?->grade_appointment_date?->format('Y/m/d') ?? '—',
                $adv->current_step,
                $adv->current_step_date?->format('Y/m/d') ?? '—',
                $adv->next_step,
                $eval ? number_format($eval->administrative_score, 2) : '—',
                $eval && $eval->pedagogical_score !== null ? number_format($eval->pedagogical_score, 2) : '—',
                $eval ? number_format($eval->composite_score, 2) : '—',
                $adv->eligible_at_minimum?->format('Y/m/d') ?? '—',
                $adv->eligible_at_medium?->format('Y/m/d') ?? '—',
                $adv->eligible_at_maximum?->format('Y/m/d') ?? '—',
                $adv->advancement_rhythm?->label() ?? '—',
                $adv->effective_date?->format('Y/m/d') ?? '—',
                $adv->status_label,
                $adv->commission_reference ?? '—',
                $adv->notes ?? '—',
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $output.$content;
    }

    /**
     * Generate A4 Portrait PDF for staff annual evaluation bulletin.
     */
    public function generateEvaluationSheetPdf(StaffEvaluation $evaluation): ?string
    {
        $data = $this->assembleEvaluationSheetData($evaluation);
        $html = view('print.staff-evaluation-sheet', $data)->render();

        try {
            return BrowsershotFactory::html($html)
                ->format('A4')
                ->showBackground()
                ->margins(10, 10, 10, 10)
                ->pdf();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Generate A4 Landscape PDF for Joint Administrative Committee (CAP) step advancement ledger.
     */
    public function generateAdvancementLedgerPdf(int $year = 2026): ?string
    {
        $data = $this->assembleAdvancementLedgerData($year);
        $html = view('print.career-advancement-ledger', $data)->render();

        try {
            return BrowsershotFactory::html($html)
                ->format('A4')
                ->landscape()
                ->showBackground()
                ->margins(8, 8, 8, 8)
                ->pdf();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
