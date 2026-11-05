<?php

namespace App\Services;

use App\Enums\CounselingSessionReason;
use App\Enums\CounselingSessionStatus;
use App\Enums\FamilyStatus;
use App\Models\CounselingSession;
use App\Models\Grade;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentCounselingProfile;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;

class StudentCounselingService
{
    /**
     * Get or create a student's counseling profile.
     */
    public function getOrCreateProfile(Student $student): StudentCounselingProfile
    {
        return StudentCounselingProfile::firstOrCreate(
            ['student_id' => $student->id],
            [
                'family_status' => FamilyStatus::BothParents,
                'siblings_count' => 0,
                'birth_order' => 1,
                'social_grant' => false,
                'has_physical_disability' => false,
                'pe_exemption' => false,
                'repeated_years_count' => 0,
                'is_confidential' => true,
            ]
        );
    }

    /**
     * Update a student's counseling profile.
     */
    public function updateProfile(Student $student, array $data): StudentCounselingProfile
    {
        $profile = $this->getOrCreateProfile($student);

        if (isset($data['chronic_conditions']) && is_string($data['chronic_conditions'])) {
            $data['chronic_conditions'] = array_values(array_filter(array_map('trim', explode(',', $data['chronic_conditions']))));
        }

        if (isset($data['learning_difficulties']) && is_string($data['learning_difficulties'])) {
            $data['learning_difficulties'] = array_values(array_filter(array_map('trim', explode(',', $data['learning_difficulties']))));
        }

        if (isset($data['behavior_tendencies']) && is_string($data['behavior_tendencies'])) {
            $data['behavior_tendencies'] = array_values(array_filter(array_map('trim', explode(',', $data['behavior_tendencies']))));
        }

        if (isset($data['interests_and_talents']) && is_string($data['interests_and_talents'])) {
            $data['interests_and_talents'] = array_values(array_filter(array_map('trim', explode(',', $data['interests_and_talents']))));
        }

        if (isset($data['repeated_levels']) && is_string($data['repeated_levels'])) {
            $data['repeated_levels'] = array_values(array_filter(array_map('trim', explode(',', $data['repeated_levels']))));
        }

        $profile->update($data);

        return $profile->fresh();
    }

    /**
     * Record a new counseling interview session.
     */
    public function recordSession(Student $student, User $counselor, array $data): CounselingSession
    {
        $academicYear = $data['academic_year'] ?? SchoolSetting::currentAcademicYear();

        return CounselingSession::create([
            'student_id' => $student->id,
            'counselor_id' => $counselor->id,
            'academic_year' => $academicYear,
            'session_date' => $data['session_date'] ?? Carbon::today(),
            'reason_category' => $data['reason_category'] ?? CounselingSessionReason::AcademicDifficulty,
            'interviewee_type' => $data['interviewee_type'] ?? 'student_only',
            'observations' => $data['observations'] ?? '',
            'action_plan' => $data['action_plan'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'status' => $data['status'] ?? CounselingSessionStatus::Open,
            'is_confidential' => $data['is_confidential'] ?? true,
        ]);
    }

    /**
     * Update counseling session status and follow-up plan.
     */
    public function updateSessionStatus(CounselingSession $session, CounselingSessionStatus|string $status, ?string $actionPlan = null): CounselingSession
    {
        $payload = ['status' => $status];
        if ($actionPlan !== null) {
            $payload['action_plan'] = $actionPlan;
        }

        $session->update($payload);

        return $session->fresh();
    }

    /**
     * Compute statistics and KPIs for counseling interventions.
     */
    public function computeCounselingKpis(?string $academicYear = null): array
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();

        $totalStudents = Student::query()->active()->count();
        $totalProfiles = StudentCounselingProfile::count();
        $orphanCount = StudentCounselingProfile::query()
            ->whereIn('family_status', [FamilyStatus::OrphanFather, FamilyStatus::OrphanMother, FamilyStatus::OrphanBoth])
            ->count();
        $divorcedCount = StudentCounselingProfile::query()
            ->where('family_status', FamilyStatus::DivorcedSeparated)
            ->count();
        $socialGrantCount = StudentCounselingProfile::query()->withSocialGrant()->count();
        $chronicCount = StudentCounselingProfile::query()->withChronicConditions()->count();
        $peExemptionCount = StudentCounselingProfile::query()->withPeExemption()->count();
        $difficultiesCount = StudentCounselingProfile::query()->withLearningDifficulties()->count();

        $sessionsQuery = CounselingSession::query()->where('academic_year', $academicYear);
        $totalSessions = (clone $sessionsQuery)->count();
        $openSessions = (clone $sessionsQuery)->open()->count();
        $inProgressSessions = (clone $sessionsQuery)->inProgress()->count();
        $resolvedSessions = (clone $sessionsQuery)->resolved()->count();

        // Breakdown by reason
        $reasonCounts = [];
        foreach (CounselingSessionReason::cases() as $reason) {
            $count = (clone $sessionsQuery)->where('reason_category', $reason)->count();
            $percentage = $totalSessions > 0 ? round(($count / $totalSessions) * 100, 1) : 0;
            $reasonCounts[$reason->value] = [
                'reason' => $reason,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'academic_year' => $academicYear,
            'total_students' => $totalStudents,
            'total_profiles' => $totalProfiles,
            'orphan_count' => $orphanCount,
            'divorced_count' => $divorcedCount,
            'social_grant_count' => $socialGrantCount,
            'chronic_count' => $chronicCount,
            'pe_exemption_count' => $peExemptionCount,
            'difficulties_count' => $difficultiesCount,
            'total_sessions' => $totalSessions,
            'open_sessions' => $openSessions,
            'in_progress_sessions' => $inProgressSessions,
            'resolved_sessions' => $resolvedSessions,
            'resolution_rate' => $totalSessions > 0 ? round(($resolvedSessions / $totalSessions) * 100, 1) : 0,
            'reasons_breakdown' => $reasonCounts,
        ];
    }

    /**
     * Assemble all necessary data for the student's Dossier Psycho-Pédagogique.
     */
    public function assembleStudentDossierData(Student $student): array
    {
        $profile = $this->getOrCreateProfile($student);
        $sessions = CounselingSession::query()
            ->where('student_id', $student->id)
            ->with(['counselor'])
            ->orderByDesc('session_date')
            ->get();

        // Recent assessment performance overview
        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->with(['assessment.schedule.subject'])
            ->get();

        $subjectScores = [];
        foreach ($grades as $grade) {
            $subject = $grade->assessment?->schedule?->subject;
            if ($subject && $grade->score !== null) {
                $max = (float) ($grade->assessment->max_score ?? 20.0);
                if ($max > 0) {
                    $scaled = ((float) $grade->score / $max) * 20.0;
                    $subjectScores[$subject->name][] = $scaled;
                }
            }
        }

        $subjectAverages = [];
        foreach ($subjectScores as $subjName => $scores) {
            $subjectAverages[$subjName] = round(array_sum($scores) / count($scores), 2);
        }

        $generalAverage = ! empty($subjectAverages) ? round(array_sum($subjectAverages) / count($subjectAverages), 2) : null;

        return [
            'student' => $student,
            'group' => $student->group,
            'classroom' => $student->group?->classroom,
            'level' => $student->group?->classroom?->level,
            'profile' => $profile,
            'sessions' => $sessions,
            'subject_averages' => $subjectAverages,
            'general_average' => $generalAverage,
            'school' => SchoolSetting::current(),
            'academic_year' => SchoolSetting::currentAcademicYear(),
        ];
    }

    /**
     * Assemble annual ledger data of all counseling interventions.
     */
    public function assembleAnnualLedgerData(?string $academicYear = null): array
    {
        $academicYear = $academicYear ?? SchoolSetting::currentAcademicYear();

        $sessions = CounselingSession::query()
            ->where('academic_year', $academicYear)
            ->with(['student.group.classroom', 'counselor'])
            ->orderBy('session_date')
            ->get();

        return [
            'academic_year' => $academicYear,
            'sessions' => $sessions,
            'kpis' => $this->computeCounselingKpis($academicYear),
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Render HTML for the Comprehensive Student Dossier (A4 portrait).
     */
    public function renderDossierHtml(Student $student): string
    {
        $data = $this->assembleStudentDossierData($student);

        return view('print.counseling-dossier', $data)->render();
    }

    /**
     * Generate PDF for the Comprehensive Student Dossier.
     */
    public function generateDossierPdf(Student $student): string
    {
        $html = $this->renderDossierHtml($student);

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(8, 8, 8, 8)
            ->pdf();
    }

    /**
     * Render HTML for an Individual Counseling Interview Sheet (A4 portrait).
     */
    public function renderSessionHtml(CounselingSession $session): string
    {
        $session->loadMissing(['student.group.classroom', 'counselor']);

        return view('print.counseling-session', [
            'session' => $session,
            'student' => $session->student,
            'profile' => $this->getOrCreateProfile($session->student),
            'counselor' => $session->counselor,
            'school' => SchoolSetting::current(),
            'academic_year' => $session->academic_year,
        ])->render();
    }

    /**
     * Generate PDF for an Individual Counseling Interview Sheet.
     */
    public function generateSessionPdf(CounselingSession $session): string
    {
        $html = $this->renderSessionHtml($session);

        return BrowsershotFactory::html($html)
            ->margins(10, 10, 10, 10)
            ->pdf();
    }

    /**
     * Render HTML for Annual Counseling Register Ledger (A4 landscape).
     */
    public function renderAnnualLedgerHtml(?string $academicYear = null): string
    {
        $data = $this->assembleAnnualLedgerData($academicYear);

        return view('print.counseling-ledger', $data)->render();
    }

    /**
     * Generate PDF for Annual Counseling Register Ledger.
     */
    public function generateAnnualLedgerPdf(?string $academicYear = null): string
    {
        $html = $this->renderAnnualLedgerHtml($academicYear);

        return BrowsershotFactory::html($html)
            ->landscape()
            ->margins(8, 8, 8, 8)
            ->pdf();
    }
}
