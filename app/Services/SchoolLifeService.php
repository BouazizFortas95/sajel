<?php

namespace App\Services;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Models\ClubActivity;
use App\Models\ClubMembership;
use App\Models\SchoolClub;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Support\BrowsershotFactory;

class SchoolLifeService
{
    /**
     * Compute comprehensive school life & clubs overview for an academic year.
     *
     * @return array<string, mixed>
     */
    public function getClubsOverview(string $academicYear = '2026-2027'): array
    {
        $clubs = SchoolClub::query()
            ->with([
                'leadTeacher',
                'assistantTeacher',
                'memberships.student.group.classroom.level',
                'activities',
            ])
            ->where('academic_year', $academicYear)
            ->latest('id')
            ->get();

        $activeClubsCount = $clubs->where('status', ClubStatus::Active)->count();

        // Enrolled students analytics
        $allMemberships = $clubs->pluck('memberships')->flatten();
        $activeMemberships = $allMemberships->where('status', ClubMemberStatus::Active);
        $uniqueStudentIds = $activeMemberships->pluck('student_id')->unique();
        $totalEnrolledStudents = $uniqueStudentIds->count();

        $bureauMembersCount = $activeMemberships->filter(
            fn (ClubMembership $m): bool => $m->role->isBureau()
        )->count();

        // Activities analytics
        $allActivities = $clubs->pluck('activities')->flatten();
        $totalActivities = $allActivities->count();
        $completedActivities = $allActivities->where('status', ClubActivityStatus::Completed)->count();
        $plannedActivities = $allActivities->where('status', ClubActivityStatus::Planned)->count();
        $totalAttendees = (int) $allActivities->where('status', ClubActivityStatus::Completed)->sum('attendees_count');

        // Clubs by Type
        $clubsByType = [];
        foreach (ClubType::cases() as $type) {
            $count = $clubs->where('type', $type)->count();
            $clubsByType[$type->value] = [
                'type' => $type,
                'label' => $type->label(),
                'icon' => $type->icon(),
                'color' => $type->color(),
                'count' => $count,
            ];
        }

        // Student participation by level (1AM, 2AM, 3AM, 4AM)
        $levelDistribution = [];
        foreach ($activeMemberships as $m) {
            $lvlName = $m->student?->group?->classroom?->level?->name ?? __('غير محدد');
            $levelDistribution[$lvlName] = ($levelDistribution[$lvlName] ?? 0) + 1;
        }

        return [
            'academic_year' => $academicYear,
            'total_clubs' => $clubs->count(),
            'active_clubs' => $activeClubsCount,
            'total_enrolled_students' => $totalEnrolledStudents,
            'total_memberships' => $activeMemberships->count(),
            'bureau_members_count' => $bureauMembersCount,
            'total_activities' => $totalActivities,
            'completed_activities' => $completedActivities,
            'planned_activities' => $plannedActivities,
            'total_attendees' => $totalAttendees,
            'clubs_by_type' => $clubsByType,
            'level_distribution' => $levelDistribution,
            'clubs' => $clubs,
        ];
    }

    /**
     * Create a new school club.
     *
     * @param  array<string, mixed>  $data
     */
    public function createClub(array $data): SchoolClub
    {
        if (empty($data['code'])) {
            $typePrefix = strtoupper(substr($data['type'] ?? 'GEN', 0, 3));
            $data['code'] = sprintf('CLUB-%s-%03d', $typePrefix, SchoolClub::count() + 1);
        }

        return SchoolClub::create($data);
    }

    /**
     * Update an existing school club.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateClub(SchoolClub $club, array $data): SchoolClub
    {
        $club->update($data);

        return $club->fresh();
    }

    /**
     * Enroll a student into a school club.
     */
    public function enrollStudent(
        SchoolClub $club,
        int $studentId,
        ClubMemberRole $role = ClubMemberRole::ActiveMember,
        ?string $notes = null
    ): ClubMembership {
        return ClubMembership::updateOrCreate(
            [
                'school_club_id' => $club->id,
                'student_id' => $studentId,
            ],
            [
                'role' => $role,
                'joined_at' => now()->toDateString(),
                'status' => ClubMemberStatus::Active,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Record a new activity or workshop for a club.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordActivity(SchoolClub $club, array $data): ClubActivity
    {
        $data['school_club_id'] = $club->id;

        return ClubActivity::create($data);
    }

    /**
     * Generate printable PDF for Club Technical & Bureau Sheet (البطاقة الفنية للنادي).
     */
    public function generateClubTechnicalSheetPdf(SchoolClub $club): string
    {
        $club->load([
            'leadTeacher.primarySubject',
            'assistantTeacher.primarySubject',
            'memberships.student.group.classroom.level',
            'activities',
        ]);

        $html = view('print.club-technical-sheet', [
            'club' => $club,
            'bureau' => $club->bureauMembers(),
            'school' => SchoolSetting::current(),
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable PDF for Club Membership & Achievement Certificate (شهادة التقدير والمشاركة).
     */
    public function generateMembershipCertificatePdf(ClubMembership $membership): string
    {
        $membership->load([
            'club.leadTeacher',
            'student.group.classroom.level',
        ]);

        $html = view('print.club-member-certificate', [
            'membership' => $membership,
            'club' => $membership->club,
            'student' => $membership->student,
            'school' => SchoolSetting::current(),
            'inlineCss' => true,
        ])->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable PDF for Annual School Life & Clubs Bilan (الحصيلة السنوية للحياة المدرسية).
     */
    public function generateAnnualLifeBilanPdf(string $academicYear = '2026-2027'): string
    {
        $data = $this->getClubsOverview($academicYear);
        $data['school'] = SchoolSetting::current();
        $data['inlineCss'] = true;

        $html = view('print.annual-school-life-bilan', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }
}
