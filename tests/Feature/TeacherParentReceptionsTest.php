<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Filament\Pages\TeacherParentReceptionsHub;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\TeacherParentReceptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherParentReceptionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected Subject $subject;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::create([
            'school_name' => 'متوسطة الشهيد زبانة',
            'academic_year' => '2026/2027',
            'principal_name' => 'بلقاسم بن علي',
            'counselor_name' => 'فاطمة الزهراء عيساني',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'المدير العام',
            'role' => 'admin',
        ]);

        $this->subject = Subject::factory()->create([
            'name' => 'الإعلام الآلي',
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'أحمد بوزيد',
            'role' => 'teacher',
            'primary_subject_id' => $this->subject->id,
        ]);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->create(['education_phase_id' => $phase->id]);
        $this->classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1 م 1']);

        // Schedule 1: Sunday 08:00 - 09:00
        Schedule::factory()->create([
            'user_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // Schedule 2: Sunday 10:00 - 11:00 (creates 09:00 - 10:00 intra-day gap)
        Schedule::factory()->create([
            'user_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => DayOfWeek::Sunday,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
    }

    public function test_service_detects_intraday_gap_slots_between_sessions(): void
    {
        $service = app(TeacherParentReceptionService::class);
        $gaps = $service->detectGapSlots($this->teacher);

        $this->assertNotEmpty($gaps);

        $firstGap = $gaps[0];
        $this->assertSame('sunday', $firstGap['day']);
        $this->assertSame('09:00', $firstGap['start_time']);
        $this->assertSame('10:00', $firstGap['end_time']);
        $this->assertSame(60, $firstGap['duration_minutes']);
        $this->assertTrue($firstGap['is_between_sessions']);
    }

    public function test_service_auto_assigns_reception_slots_from_schedule_gaps(): void
    {
        $service = app(TeacherParentReceptionService::class);
        $this->assertFalse($this->teacher->hasParentReceptionSlot());

        $assigned = $service->autoAssignReceptions();
        $this->assertGreaterThanOrEqual(1, $assigned);

        $this->teacher->refresh();
        $this->assertTrue($this->teacher->hasParentReceptionSlot());
        $this->assertSame('sunday', $this->teacher->parent_reception_day);
        $this->assertSame('09:00', $this->teacher->parent_reception_start_time);
        $this->assertSame('10:00', $this->teacher->parent_reception_end_time);
        $this->assertSame('مكتب الاستشارة التربوية', $this->teacher->parent_reception_location);
    }

    public function test_service_calculates_next_reception_date_occurrence(): void
    {
        $service = app(TeacherParentReceptionService::class);
        $this->teacher->update([
            'parent_reception_day' => 'sunday',
            'parent_reception_start_time' => '09:00',
            'parent_reception_end_time' => '10:00',
        ]);

        // Fix reference date to a Monday (e.g. 2026-11-09 is Monday)
        $monday = Carbon::parse('2026-11-09 10:00:00');
        $next = $service->calculateNextReceptionDate($this->teacher, $monday);

        // Next Sunday from Monday Nov 9 is Nov 15 at 09:00
        $this->assertSame('2026-11-15 09:00:00', $next->format('Y-m-d H:i:s'));
        $this->assertSame(Carbon::SUNDAY, $next->dayOfWeek);
    }

    public function test_official_parent_reception_schedule_print_view(): void
    {
        $this->teacher->update([
            'parent_reception_day' => 'sunday',
            'parent_reception_start_time' => '09:00',
            'parent_reception_end_time' => '10:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('print.teacher-parent-receptions', [
            'locale' => 'ar',
        ]));

        $response->assertOk();
        $response->assertSee('جدول الاستقبال الأسبوعي لأولياء التلاميذ');
        $response->assertSee($this->teacher->name);
        $response->assertSee('الإعلام الآلي');
        $response->assertSee('الأحد');
        $response->assertSee('09:00');
        $response->assertSee('10:00');
        $response->assertSee('مكتب الاستشارة التربوية');
    }

    public function test_behavior_observation_guardian_summons_uses_teacher_reception_slot(): void
    {
        $this->teacher->update([
            'parent_reception_day' => 'sunday',
            'parent_reception_start_time' => '09:00',
            'parent_reception_end_time' => '10:00',
        ]);

        $group = Group::factory()->create(['classroom_id' => $this->classroom->id]);

        $student = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'ياسين',
            'last_name' => 'بن عمر',
        ]);

        $schedule = Schedule::where('user_id', $this->teacher->id)->first();

        $observation = BehaviorObservation::factory()->create([
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'recorded_by_id' => $this->teacher->id,
            'category' => 'disciplinary',
            'date' => '2026-11-10', // Tuesday
            'notes' => 'تشويش وعرقلة الحصة',
            'administrative_action' => 'pending',
        ]);

        // Change administrative action to guardian summons
        $observation->applyAdministrativeAction('guardian_summons', $this->admin);

        $observation->refresh();
        $this->assertSame('guardian_summons', $observation->administrative_action);
        $this->assertNotNull($observation->guardian_summons_appointment_at);

        // Should be scheduled on the teacher's next reception day (Sunday at 09:00)
        $appointment = Carbon::parse($observation->guardian_summons_appointment_at);
        $this->assertSame(Carbon::SUNDAY, $appointment->dayOfWeek);
        $this->assertSame('09:00', $appointment->format('H:i'));
    }

    public function test_filament_hub_page_can_be_rendered_and_actions_work(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(TeacherParentReceptionsHub::class)
            ->assertSuccessful()
            ->assertSee('جدول ومواعيد استقبال أولياء التلاميذ')
            ->call('applySlot', $this->teacher->id, 'sunday', '09:00', '10:00')
            ->assertHasNoErrors();

        $this->teacher->refresh();
        $this->assertSame('sunday', $this->teacher->parent_reception_day);
        $this->assertSame('09:00', $this->teacher->parent_reception_start_time);
        $this->assertSame('10:00', $this->teacher->parent_reception_end_time);

        Livewire::test(TeacherParentReceptionsHub::class)
            ->call('clearSlot', $this->teacher->id)
            ->assertHasNoErrors();

        $this->teacher->refresh();
        $this->assertNull($this->teacher->parent_reception_day);
    }
}
