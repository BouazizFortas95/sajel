<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Filament\Pages\PedagogicalPlanReceiptsHub;
use App\Filament\Pages\TeacherReceiptsHub;
use App\Models\DailyLog;
use App\Models\PedagogicalPlanReceipt;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\DailyLogPedagogicalPlanSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyLogPedagogicalPlanSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $admin;

    protected Schedule $schedule;

    protected Subject $subject;

    protected DailyLogPedagogicalPlanSyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'متوسطة حي 900 مسكن',
                'academic_year' => '2026-2027',
                'directorate_name' => 'مديرية التربية لولاية المغير',
                'municipality_name' => 'المغير',
                'principal_name' => 'أحمد بوزقاق',
                'school_code' => '00-0000-0001',
            ]
        );

        $this->teacher = User::factory()->create([
            'name' => 'الأستاذ قرطاس بوعزيز',
            'role' => Role::Teacher->value,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'المدير بوزقاق أحمد',
            'role' => Role::Admin->value,
        ]);

        $this->subject = Subject::firstOrCreate(
            ['name' => 'الإعلام الآلي'],
            ['color' => '#0284c7']
        );

        $this->schedule = Schedule::factory()->create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
        ]);

        $this->syncService = app(DailyLogPedagogicalPlanSyncService::class);
    }

    public function test_detect_plan_type_recognizes_annual_and_monthly_plan_keywords(): void
    {
        $logAnnual = new DailyLog(['title' => 'تقديم ومناقشة المخطط السنوي لبناء التعلمات']);
        $this->assertEquals('annual_plan', $this->syncService->detectPlanType($logAnnual));

        $logSept = new DailyLog(['title' => 'تدرج شهر سبتمبر وتوزيع الحصص']);
        $this->assertEquals('september', $this->syncService->detectPlanType($logSept));

        $logNov = new DailyLog(['title' => 'مخطط شهر نوفمبر للمادة']);
        $this->assertEquals('november', $this->syncService->detectPlanType($logNov));

        $logJan = new DailyLog(['title' => 'توزيع شهر جانفي البيداغوجي']);
        $this->assertEquals('january', $this->syncService->detectPlanType($logJan));

        $logRegular = new DailyLog(['title' => 'الدرس الأول: مدخل للبرمجة']);
        $this->assertNull($this->syncService->detectPlanType($logRegular));
    }

    public function test_completing_daily_log_with_annual_plan_keyword_syncs_annual_plan(): void
    {
        $log = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'date' => Carbon::parse('2026-09-22'),
            'title' => 'إعداد وتأشير التدرج السنوي للتعلمات',
            'status' => DailyLogStatus::Planned,
        ]);

        $log->markAsCompleted();

        $receipt = PedagogicalPlanReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->annual_plan_submitted);
        $this->assertEquals('2026-09-22', $receipt->annual_plan_date?->format('Y-m-d'));
    }

    public function test_completing_daily_log_with_monthly_plan_keyword_syncs_specific_month(): void
    {
        $log = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'date' => Carbon::parse('2026-10-15'),
            'title' => 'مخطط شهر أكتوبر وتوزيع الأنشطة',
            'status' => DailyLogStatus::Planned,
        ]);

        $log->markAsCompleted();

        $receipt = PedagogicalPlanReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->october_submitted);
        $this->assertEquals('2026-10-15', $receipt->october_date?->format('Y-m-d'));
    }

    public function test_reaching_monthly_milestone_automatically_syncs_monthly_plan(): void
    {
        // Create 3 completed lessons in November
        for ($i = 1; $i <= 3; $i++) {
            $log = DailyLog::factory()->create([
                'schedule_id' => $this->schedule->id,
                'date' => Carbon::parse("2026-11-0{$i}"),
                'title' => "درس المادة رقم {$i}",
                'status' => DailyLogStatus::Planned,
            ]);
            $log->markAsCompleted();
        }

        $receipt = PedagogicalPlanReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->november_submitted);
    }

    public function test_bulk_sync_service_and_artisan_command(): void
    {
        // Create 3 completed lessons in December directly without triggering booted event
        DailyLog::withoutEvents(function () {
            for ($i = 1; $i <= 3; $i++) {
                DailyLog::factory()->create([
                    'schedule_id' => $this->schedule->id,
                    'date' => Carbon::parse("2026-12-0{$i}"),
                    'title' => "حصة رقم {$i}",
                    'status' => DailyLogStatus::Completed,
                ]);
            }
        });

        // Artisan command should sync
        $this->artisan('pedagogical:sync-plans', ['--teacher' => $this->teacher->id])
            ->assertSuccessful();

        $receipt = PedagogicalPlanReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->december_submitted);
    }

    public function test_teacher_receipts_hub_renders_curriculum_progress_section(): void
    {
        $this->actingAs($this->teacher);

        Livewire::test(TeacherReceiptsHub::class)
            ->assertSuccessful()
            ->assertSee('وتيرة إنجاز المنهاج والتقدم الميداني في التعلمات')
            ->assertSee('نسبة التقدم العامة')
            ->assertSee('الموارد المعرفية المنجزة');
    }

    public function test_pedagogical_plan_receipts_hub_sync_action(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(PedagogicalPlanReceiptsHub::class)
            ->assertSuccessful()
            ->callAction('syncFromDailyLogs')
            ->assertHasNoActionErrors();
    }
}
