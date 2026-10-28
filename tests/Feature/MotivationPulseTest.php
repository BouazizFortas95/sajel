<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Filament\Pages\MotivationPulseHub;
use App\Livewire\DataShowMode;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentMotivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MotivationPulseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function badges_computed_from_existing_domain_data(): void
    {
        $student = Student::factory()->create();
        $classroom = Classroom::factory()->create();
        $schedule = Schedule::factory()->for($classroom)->create();

        // Attendance streak (5 distinct present school days in the last 14 days).
        for ($i = 0; $i < 5; $i++) {
            $log = DailyLog::factory()->for($schedule)->create([
                'date' => now()->subDays(4 - $i)->toDateString(),
                'status' => DailyLogStatus::Completed,
            ]);

            Attendance::factory()->for($log)->create([
                'student_id' => $student->id,
                'status' => AttendanceStatus::Present,
            ]);
        }

        // Positive role model (positive observation, 0 negative)
        BehaviorObservation::create([
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'category' => 'positive',
            'notes' => 'مشاركة فعالة في الحصة.',
            'date' => today(),
        ]);

        $service = app(StudentMotivationService::class);
        $badges = $service->badgesForStudent($student, $classroom);

        $this->assertArrayHasKey('attendance_streak', $badges);
        $this->assertArrayHasKey('positive_role_model', $badges);
    }

    /** @test */
    public function comeback_champion_badge_for_grade_improvement(): void
    {
        $student = Student::factory()->create();
        $classroom = Classroom::factory()->create();
        $schedule = Schedule::factory()->for($classroom)->create();

        $a1 = Assessment::factory()->for($schedule)->create(['max_score' => 20, 'date' => now()->subDays(10)]);
        $a2 = Assessment::factory()->for($schedule)->create(['max_score' => 20, 'date' => now()->subDays(5)]);

        Grade::factory()->create(['student_id' => $student->id, 'assessment_id' => $a1->id, 'score' => 10]);
        Grade::factory()->create(['student_id' => $student->id, 'assessment_id' => $a2->id, 'score' => 14]);

        $service = app(StudentMotivationService::class);
        $badges = $service->badgesForStudent($student, $classroom);

        $this->assertArrayHasKey('comeback_champion', $badges);
    }

    /** @test */
    public function award_quick_kudos_records_positive_observation_for_authorized_roster_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = Student::factory()->create();
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->for($classroom)->create();
        $student->update(['group_id' => $group->id]);
        $schedule = Schedule::factory()->for($classroom)->for($teacher)->create();
        $log = DailyLog::factory()->for($schedule)->create(['status' => DailyLogStatus::Completed, 'date' => today()]);

        $this->actingAs($teacher);

        Livewire::test(DataShowMode::class, ['logId' => $log->id])
            ->call('awardQuickKudos', $student->id, 'participation')
            ->assertDispatched('kudos-awarded');

        $this->assertDatabaseHas('behavior_observations', [
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'category' => 'positive',
        ]);
    }

    /** @test */
    public function motivation_pulse_hub_accessible_to_teacher_and_admin(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($teacher);
        $this->get(MotivationPulseHub::getUrl())->assertOk();

        $this->actingAs($admin);
        $this->get(MotivationPulseHub::getUrl())->assertOk();
    }

    /** @test */
    public function student_certificate_route_authorized_and_shows_badges(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $this->actingAs($admin);
        $this->get('/ar/print/students/'.$student->id.'/certificate')->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('lang="ar"', false)
            ->assertSee(__('شهادة التميز'));
    }

    /** @test */
    public function student_certificate_follows_the_requested_locale(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $this->actingAs($admin);

        $this->get('/en/print/students/'.$student->id.'/certificate')->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('lang="en"', false)
            ->assertSee('Certificate of Excellence and Educational Motivation')
            ->assertSee('Earned badges');

        $this->get('/fr/print/students/'.$student->id.'/certificate')->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('lang="fr"', false)
            ->assertSee('Certificat d’excellence et de motivation pédagogique')
            ->assertSee('Badges obtenus');
    }
}
