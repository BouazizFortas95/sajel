<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\TeacherAbsenceReason;
use App\Enums\TeacherAbsenceStatus;
use App\Filament\Pages\TeacherAbsenceHub;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherAbsence;
use App\Models\User;
use App\Services\TeacherAbsenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAbsenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_teacher_can_access_teacher_absence_hub(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(TeacherAbsenceHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('رادار غيابات الأساتذة');

        $this->actingAs($teacher)
            ->get(TeacherAbsenceHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('رادار غيابات الأساتذة');

        auth()->logout();
        $this->get(TeacherAbsenceHub::getUrl(['locale' => 'ar']))
            ->assertRedirect();
    }

    public function test_teacher_absence_service_records_absence_and_resolves_affected_sessions(): void
    {
        $service = app(TeacherAbsenceService::class);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 1']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $teacher = User::factory()->teacher()->create(['primary_subject_id' => $subject->id]);

        // Teacher has schedules on Sunday and Tuesday
        $scheduleSunday = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $scheduleTuesday = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Tuesday,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        // Record 4-day absence from Sunday 2026-10-18 to Wednesday 2026-10-21
        $absence = $service->recordAbsence([
            'teacher_id' => $teacher->id,
            'starts_at' => '2026-10-18',
            'ends_at' => '2026-10-21',
            'reason' => TeacherAbsenceReason::SickLeave,
            'reference_number' => 'CERT-2026/088',
            'notes' => 'عطلة مرضية لمدة 4 أيام.',
        ]);

        $this->assertSame(4, $absence->durationInDays());
        $this->assertSame(TeacherAbsenceStatus::Uncovered, $absence->status);

        $affected = $service->getAffectedSessions($absence);

        // Sunday and Tuesday fall inside this window -> 2 sessions
        $this->assertCount(2, $affected);

        $firstSession = $affected->firstWhere('day_name', DayOfWeek::Sunday->label());
        $this->assertNotNull($firstSession);
        $this->assertSame($classroom->id, $firstSession['classroom']->id);
        $this->assertSame($subject->id, $firstSession['subject']->id);
        $this->assertFalse($firstSession['is_covered']);
    }

    public function test_find_available_substitutes_ranks_by_subject_and_excludes_busy_or_absent_teachers(): void
    {
        $service = app(TeacherAbsenceService::class);

        $subjectMath = Subject::factory()->create(['name' => 'الرياضيات']);
        $subjectScience = Subject::factory()->create(['name' => 'العلوم الطبيعية']);

        $teacherAbsent = User::factory()->teacher()->create(['name' => 'أحمد المتغيب', 'primary_subject_id' => $subjectMath->id]);
        $teacherCandidateSameSubject = User::factory()->teacher()->create(['name' => 'بلقاسم الرياضيات', 'primary_subject_id' => $subjectMath->id]);
        $teacherCandidateOtherSubject = User::factory()->teacher()->create(['name' => 'عمر العلوم', 'primary_subject_id' => $subjectScience->id]);
        $teacherBusy = User::factory()->teacher()->create(['name' => 'يوسف المشغول', 'primary_subject_id' => $subjectMath->id]);
        $teacherAlsoAbsent = User::factory()->teacher()->create(['name' => 'سعيد الغائب', 'primary_subject_id' => $subjectMath->id]);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 1']);

        // Schedule to substitute on Sunday 08:00 - 09:00
        $targetSchedule = Schedule::factory()->create([
            'user_id' => $teacherAbsent->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subjectMath->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // TeacherBusy has an overlapping schedule at 08:00 - 09:00 on Sunday
        Schedule::factory()->create([
            'user_id' => $teacherBusy->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subjectMath->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // TeacherAlsoAbsent has an active absence covering 2026-10-18
        TeacherAbsence::factory()->create([
            'teacher_id' => $teacherAlsoAbsent->id,
            'starts_at' => '2026-10-18',
            'ends_at' => '2026-10-18',
        ]);

        $candidates = $service->findAvailableSubstitutes($targetSchedule, '2026-10-18');

        // TeacherBusy and TeacherAlsoAbsent must be excluded!
        $candidateIds = $candidates->pluck('teacher.id')->all();
        $this->assertNotContains($teacherBusy->id, $candidateIds);
        $this->assertNotContains($teacherAlsoAbsent->id, $candidateIds);
        $this->assertNotContains($teacherAbsent->id, $candidateIds);

        // TeacherCandidateSameSubject must be first with priority 1
        $this->assertContains($teacherCandidateSameSubject->id, $candidateIds);
        $this->assertSame($teacherCandidateSameSubject->id, $candidates->first()['teacher']->id);
        $this->assertSame(1, $candidates->first()['priority']);

        // TeacherCandidateOtherSubject is present with priority 3 (general duty)
        $this->assertContains($teacherCandidateOtherSubject->id, $candidateIds);
        $otherItem = $candidates->firstWhere('teacher.id', $teacherCandidateOtherSubject->id);
        $this->assertSame(3, $otherItem['priority']);
    }

    public function test_assign_substitute_updates_daily_log_and_transitions_absence_status(): void
    {
        $service = app(TeacherAbsenceService::class);

        $subject = Subject::factory()->create(['name' => 'اللغة العربية']);
        $teacher = User::factory()->teacher()->create(['primary_subject_id' => $subject->id]);
        $substitute = User::factory()->teacher()->create(['primary_subject_id' => $subject->id]);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '3 م 2']);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        // 1-day absence on Monday 2026-10-19
        $absence = $service->recordAbsence([
            'teacher_id' => $teacher->id,
            'starts_at' => '2026-10-19',
            'ends_at' => '2026-10-19',
            'reason' => TeacherAbsenceReason::AuthorizedLeave,
        ]);

        $this->assertSame(TeacherAbsenceStatus::Uncovered, $absence->status);

        // Assign substitute with pedagogical directives
        $log = $service->assignSubstitute(
            $schedule,
            '2026-10-19',
            $substitute,
            'استكمال نص القراءة وحل أسئلة الفهم.'
        );

        $this->assertInstanceOf(DailyLog::class, $log);
        $this->assertSame($substitute->id, $log->substitute_teacher_id);
        $this->assertStringContainsString('استكمال نص القراءة', $log->observations);

        // The only session is covered -> absence status becomes Covered!
        $absence->refresh();
        $this->assertSame(TeacherAbsenceStatus::Covered, $absence->status);

        // Unassigning sets status back to Uncovered
        $service->unassignSubstitute($log);
        $log->refresh();
        $this->assertNull($log->substitute_teacher_id);

        $absence->refresh();
        $this->assertSame(TeacherAbsenceStatus::Uncovered, $absence->status);
    }

    public function test_print_substitute_mandate_view_and_pdf_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $absentTeacher = User::factory()->teacher()->create();
        $substitute = User::factory()->teacher()->create();
        $unrelatedTeacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '4 م 1']);
        $subject = Subject::factory()->create(['name' => 'الفيزياء']);

        $schedule = Schedule::factory()->create([
            'user_id' => $absentTeacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Thursday,
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $absence = TeacherAbsence::factory()->create([
            'teacher_id' => $absentTeacher->id,
            'starts_at' => '2026-10-22',
            'ends_at' => '2026-10-22',
            'reason' => TeacherAbsenceReason::OfficialMission,
        ]);

        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-22',
            'substitute_teacher_id' => $substitute->id,
            'observations' => 'إشراف على تجربة التيار المتناوب.',
        ]);

        // 1. Admin can view mandate
        $this->actingAs($admin)
            ->get(route('print.teacher-absence.mandate', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertOk()
            ->assertSee('استدعاء وتكليف بمهمة استخلاف بيداغوجي')
            ->assertSee($substitute->name)
            ->assertSee($absentTeacher->name)
            ->assertSee('الفيزياء')
            ->assertSee('إشراف على تجربة التيار المتناوب.');

        // 2. Substitute teacher can view mandate
        $this->actingAs($substitute)
            ->get(route('print.teacher-absence.mandate', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertOk();

        // 3. Unrelated teacher is forbidden (403)
        $this->actingAs($unrelatedTeacher)
            ->get(route('print.teacher-absence.mandate', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertForbidden();

        // 4. Mocked PDF download route
        $this->mock(TeacherAbsenceService::class, function ($mock): void {
            $mock->shouldReceive('generateMandatePdf')
                ->once()
                ->andReturn('%PDF-1.4 Mocked Mandate');
        });

        $this->actingAs($admin)
            ->get(route('print.teacher-absence.mandate.pdf', [
                'locale' => 'ar',
                'absence' => $absence,
                'log' => $log,
            ]))
            ->assertDownload('mandate-substitute-log-'.$log->id.'.pdf');
    }

    public function test_print_monthly_absence_ledger_view_and_pdf_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        TeacherAbsence::factory()->create([
            'teacher_id' => $teacher->id,
            'starts_at' => '2026-10-05',
            'ends_at' => '2026-10-06',
            'reason' => TeacherAbsenceReason::SickLeave,
            'reference_number' => 'REF-1002',
        ]);

        // Admin can access monthly ledger
        $this->actingAs($admin)
            ->get(route('print.teacher-absence.monthly-ledger', [
                'locale' => 'ar',
                'year' => 2026,
                'month' => 10,
            ]))
            ->assertOk()
            ->assertSee('الكشف الإجمالي الشهري لغيابات الأساتذة')
            ->assertSee($teacher->name)
            ->assertSee('REF-1002');

        // Teacher is forbidden from viewing global administration ledger
        $this->actingAs($teacher)
            ->get(route('print.teacher-absence.monthly-ledger', [
                'locale' => 'ar',
                'year' => 2026,
                'month' => 10,
            ]))
            ->assertForbidden();

        // Mocked PDF download
        $this->mock(TeacherAbsenceService::class, function ($mock): void {
            $mock->shouldReceive('generateMonthlyLedgerPdf')
                ->once()
                ->with(2026, 10)
                ->andReturn('%PDF-1.4 Mocked Monthly Ledger');
        });

        $this->actingAs($admin)
            ->get(route('print.teacher-absence.monthly-ledger.pdf', [
                'locale' => 'ar',
                'year' => 2026,
                'month' => 10,
            ]))
            ->assertDownload('monthly-absence-ledger-2026-10.pdf');
    }
}
