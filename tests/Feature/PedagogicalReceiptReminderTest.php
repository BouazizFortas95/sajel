<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\EvaluationWorkReceiptsHub;
use App\Filament\Pages\PedagogicalPlanReceiptsHub;
use App\Models\EvaluationWorkReceipt;
use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalReceiptReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PedagogicalReceiptReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $principal;

    protected User $teacher1;

    protected User $teacher2;

    protected Subject $arabic;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'متوسطة حي 900 مسكن',
                'academic_year' => '2026-2027',
            ]
        );

        $this->arabic = Subject::firstOrCreate(
            ['name' => 'اللغة العربية'],
            ['color' => '#dc2626']
        );

        $this->principal = User::factory()->create([
            'name' => 'أحمد بوزقاق',
            'email' => 'principal.reminder@sajel.test',
            'role' => Role::Principal->value,
        ]);

        $this->teacher1 = User::factory()->create([
            'name' => 'الأستاذ بالراشد',
            'email' => 'teacher1.reminder@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->arabic->id,
        ]);

        $this->teacher2 = User::factory()->create([
            'name' => 'الأستاذة خديجة',
            'email' => 'teacher2.reminder@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->arabic->id,
        ]);
    }

    public function test_service_identifies_pending_teachers_correctly(): void
    {
        $service = app(PedagogicalReceiptReminderService::class);

        // Teacher 1 submits annual plan
        PedagogicalPlanReceipt::create([
            'user_id' => $this->teacher1->id,
            'academic_year' => '2026-2027',
            'annual_plan_submitted' => true,
        ]);

        $pending = $service->getPendingTeachers('annual_plan');

        // Only Teacher 2 is pending
        $this->assertCount(1, $pending);
        $this->assertEquals($this->teacher2->id, $pending->first()->id);
    }

    public function test_service_sends_database_notification_to_teacher(): void
    {
        $service = app(PedagogicalReceiptReminderService::class);

        $service->sendTeacherReminder(
            $this->teacher2,
            'quiz_1',
            'يرجى تسليم موضوع الفرض الأول قبل الخميس'
        );

        $this->assertEquals(1, $this->teacher2->notifications()->count());
        $notification = $this->teacher2->notifications()->first();

        $this->assertStringContainsString('الفرض المحروس', $notification->data['title']);
        $this->assertStringContainsString('قبل الخميس', $notification->data['body']);
    }

    public function test_bulk_reminders_only_contacts_pending_teachers(): void
    {
        $service = app(PedagogicalReceiptReminderService::class);

        // Teacher 1 submitted diagnostic test
        EvaluationWorkReceipt::create([
            'user_id' => $this->teacher1->id,
            'academic_year' => '2026-2027',
            'diagnostic_test_submitted' => true,
        ]);

        $remindedCount = $service->sendBulkReminders('diagnostic_test');

        $this->assertEquals(1, $remindedCount);
        $this->assertEquals(0, $this->teacher1->notifications()->count());
        $this->assertEquals(1, $this->teacher2->notifications()->count());
    }

    public function test_artisan_command_runs_in_dry_run_and_active_mode(): void
    {
        // Dry-run: should not create notifications
        $this->artisan('pedagogical:send-reminders', [
            '--type' => 'annual_plan',
            '--dry-run' => true,
        ])
            ->assertSuccessful();

        $this->assertEquals(0, $this->teacher1->notifications()->count());
        $this->assertEquals(0, $this->teacher2->notifications()->count());

        // Active mode: should send notifications to both pending teachers
        $this->artisan('pedagogical:send-reminders', [
            '--type' => 'annual_plan',
        ])
            ->assertSuccessful();

        $this->assertEquals(1, $this->teacher1->notifications()->count());
        $this->assertEquals(1, $this->teacher2->notifications()->count());
    }

    public function test_artisan_command_with_auto_flag_reminds_for_due_calendar_deliverables(): void
    {
        $this->artisan('pedagogical:send-reminders', ['--auto' => true])
            ->assertSuccessful();

        // Should have sent notifications for pending active deliverables
        $this->assertGreaterThanOrEqual(1, $this->teacher1->notifications()->count());
        $this->assertGreaterThanOrEqual(1, $this->teacher2->notifications()->count());
    }

    public function test_livewire_action_send_reminders_on_plan_hub(): void
    {
        Livewire::actingAs($this->principal)
            ->test(PedagogicalPlanReceiptsHub::class)
            ->assertSuccessful()
            ->callAction('sendReminders', [
                'itemKey' => 'annual_plan',
                'customMessage' => 'تنبيه إداري رسمي من الإدارة',
            ]);

        $this->assertEquals(1, $this->teacher1->notifications()->count());
        $this->assertEquals(1, $this->teacher2->notifications()->count());
    }

    public function test_livewire_action_send_reminders_on_evaluation_hub(): void
    {
        Livewire::actingAs($this->principal)
            ->test(EvaluationWorkReceiptsHub::class)
            ->assertSuccessful()
            ->callAction('sendReminders', [
                'itemKey' => 'quiz_1',
                'customMessage' => 'تنبيه بتسليم مواضيع الفروض',
            ]);

        $this->assertEquals(1, $this->teacher1->notifications()->count());
        $this->assertEquals(1, $this->teacher2->notifications()->count());
    }
}
