<?php

namespace App\Services;

use App\Enums\OrientationAppealStatus;
use App\Enums\OrientationStream;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentOrientation;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Browsershot\Browsershot;

class StudentOrientationService
{
    /**
     * Identify whether a subject belongs to the scientific profile.
     */
    public function isScientificSubject(Subject $subject): bool
    {
        $name = mb_strtolower($subject->name);

        return str_contains($name, 'رياض')
            || str_contains($name, 'math')
            || str_contains($name, 'فيزيا')
            || str_contains($name, 'physiq')
            || str_contains($name, 'طبيع')
            || str_contains($name, 'nature')
            || str_contains($name, 'science');
    }

    /**
     * Identify whether a subject belongs to the literary profile.
     */
    public function isLiterarySubject(Subject $subject): bool
    {
        $name = mb_strtolower($subject->name);

        return str_contains($name, 'عربية')
            || str_contains($name, 'arabe')
            || str_contains($name, 'فرنسية')
            || str_contains($name, 'français')
            || str_contains($name, 'francais')
            || str_contains($name, 'إنجليزية')
            || str_contains($name, 'انجليزية')
            || str_contains($name, 'english')
            || str_contains($name, 'anglais')
            || str_contains($name, 'تاريخ')
            || str_contains($name, 'جغرافيا')
            || str_contains($name, 'histoire');
    }

    /**
     * Computes the student's pedagogical profile (scientific score, literary score, general average).
     */
    public function computeStudentProfile(Student $student, ?string $academicYear = null): array
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();

        // Retrieve grades with assessments and schedules
        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->with(['assessment.schedule.subject'])
            ->get();

        $subjects = Subject::all();
        $subjectAverages = [];

        foreach ($subjects as $subject) {
            $subjectGrades = $grades->filter(function (Grade $g) use ($subject) {
                return $g->assessment?->schedule?->subject_id === $subject->id && $g->score !== null;
            });

            if ($subjectGrades->isNotEmpty()) {
                $totalWeighted = 0.0;
                $totalMax = 0.0;

                foreach ($subjectGrades as $g) {
                    $max = (float) ($g->assessment->max_score ?? 20.0);
                    if ($max > 0) {
                        $totalWeighted += ((float) $g->score / $max) * 20.0;
                        $totalMax += 20.0;
                    }
                }

                if ($totalMax > 0) {
                    $avg = round(($totalWeighted / $subjectGrades->count()), 2);
                    $subjectAverages[$subject->id] = [
                        'subject' => $subject,
                        'name' => $subject->name,
                        'average' => $avg,
                        'is_scientific' => $this->isScientificSubject($subject),
                        'is_literary' => $this->isLiterarySubject($subject),
                    ];
                }
            }
        }

        $sciScores = [];
        $litScores = [];
        $allScores = [];

        foreach ($subjectAverages as $item) {
            $allScores[] = $item['average'];
            if ($item['is_scientific']) {
                $sciScores[] = $item['average'];
            }
            if ($item['is_literary']) {
                $litScores[] = $item['average'];
            }
        }

        $sciAvg = ! empty($sciScores) ? round(array_sum($sciScores) / count($sciScores), 2) : null;
        $litAvg = ! empty($litScores) ? round(array_sum($litScores) / count($litScores), 2) : null;
        $genAvg = ! empty($allScores) ? round(array_sum($allScores) / count($allScores), 2) : null;

        $recommended = null;
        if ($sciAvg !== null || $litAvg !== null) {
            $recommended = ($sciAvg ?? 0) >= ($litAvg ?? 0)
                ? OrientationStream::ScienceTech
                : OrientationStream::ArtsHumanities;
        }

        return [
            'student' => $student,
            'scientific_score' => $sciAvg,
            'literary_score' => $litAvg,
            'general_average' => $genAvg,
            'recommended_stream' => $recommended,
            'gap' => ($sciAvg !== null && $litAvg !== null) ? round(abs($sciAvg - $litAvg), 2) : null,
            'subject_averages' => $subjectAverages,
        ];
    }

    /**
     * Get or create a student orientation record, syncing current profile marks if uncertified.
     */
    public function getOrCreateOrientation(Student $student, ?string $academicYear = null): StudentOrientation
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();

        $orientation = StudentOrientation::firstOrNew([
            'student_id' => $student->id,
            'academic_year' => $academicYear,
        ]);

        if (! $orientation->exists || $orientation->scientific_profile_score === null) {
            $profile = $this->computeStudentProfile($student, $academicYear);
            $orientation->scientific_profile_score = $profile['scientific_score'];
            $orientation->literary_profile_score = $profile['literary_score'];
            $orientation->annual_general_average = $profile['general_average'];

            if (! $orientation->exists) {
                // Default choices if new
                $orientation->first_choice = $profile['recommended_stream'] ?? OrientationStream::ScienceTech;
                $orientation->second_choice = ($orientation->first_choice === OrientationStream::ScienceTech)
                    ? OrientationStream::ArtsHumanities
                    : OrientationStream::ScienceTech;
            }

            $orientation->save();
        }

        return $orientation;
    }

    /**
     * Record student and guardian wishes (Fiche de Vœux).
     */
    public function updateWishes(StudentOrientation $orientation, array $data): StudentOrientation
    {
        $orientation->update([
            'first_choice' => $data['first_choice'] ?? $orientation->first_choice,
            'second_choice' => $data['second_choice'] ?? $orientation->second_choice,
            'guardian_approved' => (bool) ($data['guardian_approved'] ?? $orientation->guardian_approved),
            'guardian_notes' => $data['guardian_notes'] ?? $orientation->guardian_notes,
        ]);

        return $orientation;
    }

    /**
     * Record Trimester 2 Pre-Orientation Council opinion.
     */
    public function recordPreOrientation(StudentOrientation $orientation, OrientationStream|string $choice, ?string $notes = null): StudentOrientation
    {
        $val = $choice instanceof OrientationStream ? $choice->value : $choice;

        $orientation->update([
            'pre_orientation_choice' => $val,
            'pre_orientation_notes' => $notes,
        ]);

        return $orientation;
    }

    /**
     * Record the Orientation and Admission Council's final decision.
     */
    public function recordFinalDecision(
        StudentOrientation $orientation,
        OrientationStream|string $decision,
        ?float $bemScore = null,
        ?string $notes = null
    ): StudentOrientation {
        $val = $decision instanceof OrientationStream ? $decision->value : $decision;
        $genAvg = $orientation->annual_general_average;

        // Calculate admission average and admission status per Algerian regulations
        $admAvg = null;
        $isAdmitted = false;

        if ($bemScore !== null) {
            $orientation->bem_score = $bemScore;
            if ($bemScore >= 10.0) {
                $isAdmitted = true;
                $admAvg = $bemScore;
            } elseif ($genAvg !== null) {
                $compAvg = round(($genAvg + $bemScore) / 2, 2);
                $admAvg = $compAvg;
                $isAdmitted = $compAvg >= 10.0;
            } else {
                $admAvg = $bemScore;
                $isAdmitted = $bemScore >= 10.0;
            }
        } elseif ($genAvg !== null) {
            $admAvg = $genAvg;
            $isAdmitted = $genAvg >= 10.0;
        }

        // If repeating year or vocational training
        if ($val === OrientationStream::RepeatYear->value || $val === OrientationStream::VocationalTraining->value) {
            $isAdmitted = false;
        }

        $orientation->update([
            'final_decision' => $val,
            'bem_score' => $bemScore ?? $orientation->bem_score,
            'admission_average' => $admAvg,
            'is_admitted' => $isAdmitted,
            'deliberated_at' => now(),
            'counselor_notes' => $notes ?? $orientation->counselor_notes,
        ]);

        return $orientation;
    }

    /**
     * Process an appeal submitted to the Wilaya Appeal Commission.
     */
    public function recordAppeal(
        StudentOrientation $orientation,
        OrientationAppealStatus|string $status,
        OrientationStream|string|null $decision = null,
        ?string $notes = null
    ): StudentOrientation {
        $stVal = $status instanceof OrientationAppealStatus ? $status->value : $status;
        $decVal = $decision instanceof OrientationStream ? $decision->value : $decision;

        $orientation->update([
            'appeal_status' => $stVal,
            'appeal_decision' => $decVal,
            'appeal_notes' => $notes ?? $orientation->appeal_notes,
        ]);

        return $orientation;
    }

    /**
     * Compute institutional KPIs and statistics for 4AM guidance.
     */
    public function getOrientationKpis(?string $academicYear = null): array
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();

        $students4am = Student::query()
            ->active()
            ->whereHas('group.classroom.level', function (Builder $q) {
                $q->where('grade_number', 4);
            })
            ->get();

        $totalCandidates = $students4am->count();

        $orientations = StudentOrientation::query()
            ->where('academic_year', $academicYear)
            ->whereIn('student_id', $students4am->pluck('id'))
            ->get();

        $wishesCount = $orientations->where('guardian_approved', true)->count();
        $preOrientedCount = $orientations->whereNotNull('pre_orientation_choice')->count();
        $deliberatedCount = $orientations->whereNotNull('final_decision')->count();
        $admittedCount = $orientations->where('is_admitted', true)->count();

        $scienceCount = $orientations->where('final_decision', OrientationStream::ScienceTech)->count();
        $artsCount = $orientations->where('final_decision', OrientationStream::ArtsHumanities)->count();
        $vocationalCount = $orientations->where('final_decision', OrientationStream::VocationalTraining)->count();
        $repeatCount = $orientations->where('final_decision', OrientationStream::RepeatYear)->count();

        $appealsSubmitted = $orientations->where('appeal_status', OrientationAppealStatus::Submitted)->count();
        $appealsAccepted = $orientations->where('appeal_status', OrientationAppealStatus::Accepted)->count();
        $appealsRejected = $orientations->where('appeal_status', OrientationAppealStatus::Rejected)->count();
        $totalAppeals = $appealsSubmitted + $appealsAccepted + $appealsRejected;

        $sciencePct = $deliberatedCount > 0 ? round(($scienceCount / $deliberatedCount) * 100, 1) : 0.0;
        $artsPct = $deliberatedCount > 0 ? round(($artsCount / $deliberatedCount) * 100, 1) : 0.0;
        $vocationalPct = $deliberatedCount > 0 ? round(($vocationalCount / $deliberatedCount) * 100, 1) : 0.0;
        $repeatPct = $deliberatedCount > 0 ? round(($repeatCount / $deliberatedCount) * 100, 1) : 0.0;
        $admissionRate = $totalCandidates > 0 ? round(($admittedCount / $totalCandidates) * 100, 1) : 0.0;

        return [
            'total_candidates' => $totalCandidates,
            'orientations_registered' => $orientations->count(),
            'wishes_count' => $wishesCount,
            'wishes_rate' => $totalCandidates > 0 ? round(($wishesCount / $totalCandidates) * 100, 1) : 0.0,
            'pre_oriented_count' => $preOrientedCount,
            'deliberated_count' => $deliberatedCount,
            'admitted_count' => $admittedCount,
            'admission_rate' => $admissionRate,
            'streams' => [
                'science_tech' => [
                    'count' => $scienceCount,
                    'percentage' => $sciencePct,
                ],
                'arts_humanities' => [
                    'count' => $artsCount,
                    'percentage' => $artsPct,
                ],
                'vocational_training' => [
                    'count' => $vocationalCount,
                    'percentage' => $vocationalPct,
                ],
                'repeat_year' => [
                    'count' => $repeatCount,
                    'percentage' => $repeatPct,
                ],
            ],
            'appeals' => [
                'total' => $totalAppeals,
                'submitted' => $appealsSubmitted,
                'accepted' => $appealsAccepted,
                'rejected' => $appealsRejected,
            ],
        ];
    }

    /**
     * Assemble full ledger and candidates roster for official council minutes.
     */
    public function assembleCouncilLedger(?int $classroomId = null, ?string $academicYear = null): array
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();

        $query = Student::query()
            ->active()
            ->whereHas('group.classroom.level', function (Builder $q) {
                $q->where('grade_number', 4);
            })
            ->with(['group.classroom', 'orientations' => function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear);
            }]);

        if ($classroomId) {
            $query->whereHas('group', function (Builder $q) use ($classroomId) {
                $q->where('classroom_id', $classroomId);
            });
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->get();

        $rows = $students->map(function (Student $student) use ($academicYear) {
            $orientation = $student->orientations->first() ?? $this->getOrCreateOrientation($student, $academicYear);

            return [
                'student' => $student,
                'orientation' => $orientation,
                'matricule' => $student->matricule_number ?? $student->registration_number ?? '—',
                'classroom_name' => $student->group?->classroom?->name ?? '—',
                'scientific_score' => $orientation->scientific_profile_score,
                'literary_score' => $orientation->literary_profile_score,
                'general_average' => $orientation->annual_general_average,
                'bem_score' => $orientation->bem_score,
                'admission_average' => $orientation->admission_average,
                'first_choice' => $orientation->first_choice,
                'second_choice' => $orientation->second_choice,
                'final_decision' => $orientation->final_decision,
                'effective_decision' => $orientation->effectiveDecision(),
                'is_admitted' => $orientation->is_admitted,
                'appeal_status' => $orientation->appeal_status,
            ];
        });

        $classrooms = Classroom::query()
            ->whereHas('level', fn ($q) => $q->where('grade_number', 4))
            ->orderBy('name')
            ->get();

        return [
            'academic_year' => $academicYear,
            'school' => SchoolSetting::current(),
            'classrooms' => $classrooms,
            'selected_classroom_id' => $classroomId,
            'selected_classroom' => $classroomId ? Classroom::find($classroomId) : null,
            'candidates' => $rows,
            'kpis' => $this->getOrientationKpis($academicYear),
        ];
    }

    /**
     * Render HTML for Wishes Card (A4 portrait).
     */
    public function renderWishesHtml(Student $student, ?string $academicYear = null): string
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();
        $orientation = $this->getOrCreateOrientation($student, $academicYear);
        $profile = $this->computeStudentProfile($student, $academicYear);

        return view('print.orientation-wishes', [
            'student' => $student,
            'orientation' => $orientation,
            'profile' => $profile,
            'school' => SchoolSetting::current(),
            'academic_year' => $academicYear,
        ])->render();
    }

    /**
     * Render HTML for Orientation Notification to guardian (A4 portrait).
     */
    public function renderNotificationHtml(Student $student, ?string $academicYear = null): string
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();
        $orientation = $this->getOrCreateOrientation($student, $academicYear);

        return view('print.orientation-notification', [
            'student' => $student,
            'orientation' => $orientation,
            'school' => SchoolSetting::current(),
            'academic_year' => $academicYear,
        ])->render();
    }

    /**
     * Render HTML for Council Deliberations Minutes (A4 landscape).
     */
    public function renderMinutesHtml(?int $classroomId = null, ?string $academicYear = null): string
    {
        $data = $this->assembleCouncilLedger($classroomId, $academicYear);

        return view('print.orientation-pv', $data)->render();
    }

    /**
     * Generate PDF for Wishes Card.
     */
    public function generateWishesPdf(Student $student): string
    {
        $html = $this->renderWishesHtml($student);

        return Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->pdf();
    }

    /**
     * Generate PDF for Notification.
     */
    public function generateNotificationPdf(Student $student): string
    {
        $html = $this->renderNotificationHtml($student);

        return Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->pdf();
    }

    /**
     * Generate PDF for Council Minutes.
     */
    public function generateMinutesPdf(?int $classroomId = null): string
    {
        $html = $this->renderMinutesHtml($classroomId);

        return Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->showBackground()
            ->margins(8, 8, 8, 8)
            ->pdf();
    }
}
