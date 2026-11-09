<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\Role;
use App\Models\DailyLog;
use App\Models\EvaluationWorkReceipt;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\DailyLogEvaluationWorkSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyLogEvaluationSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected Schedule $schedule;

    protected Subject $subject;

    protected DailyLogEvaluationWorkSyncService $syncService;

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
            'name' => 'الأستاذ بن عيسى علاء الدين',
            'role' => Role::Teacher->value,
        ]);

        $this->subject = Subject::firstOrCreate(
            ['name' => 'الرياضيات'],
            ['color' => '#2563eb']
        );

        $this->schedule = Schedule::factory()->create([
            'user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
        ]);

        $this->syncService = app(DailyLogEvaluationWorkSyncService::class);
    }

    public function test_detect_evaluation_type_recognizes_deliverable_keywords(): void
    {
        $cases = [
            'فرض محروس رقم 1' => 'quiz_1',
            'الفرض الأول للفصل الأول' => 'quiz_1',
            'فرض محروس رقم 2' => 'quiz_2',
            'الفرض الثالث' => 'quiz_3',
            'تقويم تشخيصي للمكتسبات' => 'diagnostic_test',
            'اختبار كشف المستوى' => 'diagnostic_test',
            'اختبار الفصل الأول' => 'exam_1',
            'امتحان الفصل الثاني' => 'exam_2',
            'الاختبار الثالث' => 'exam_3',
            'واجب منزلي رقم 1' => 'homework_1',
            'واجب منزلي 2' => 'homework_2',
            'الواجب المنزلي الثالث' => 'homework_3',
        ];

        foreach ($cases as $title => $expectedType) {
            $log = new DailyLog(['title' => $title, 'date' => '2026-10-15']);
            $this->assertSame(
                $expectedType,
                $this->syncService->detectEvaluationType($log),
                "Failed asserting that title '{$title}' maps to '{$expectedType}'."
            );
        }

        // Regular non-evaluation lessons should return null
        $regularLog = new DailyLog(['title' => 'الأعداد النسبية والعمليات الحسابية', 'date' => '2026-10-15']);
        $this->assertNull($this->syncService->detectEvaluationType($regularLog));
    }

    public function test_completing_daily_log_automatically_syncs_quiz_to_evaluation_receipt(): void
    {
        $log = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'title' => 'الفرض المحروس رقم 1 لمادة الرياضيات',
            'date' => Carbon::parse('2026-10-25'),
            'status' => DailyLogStatus::Planned,
        ]);

        // Teacher confirms completion
        $log->markAsCompleted('تم إنجاز الفرض المحروس الأول في ظروف حسنة.');

        $receipt = EvaluationWorkReceipt::where('user_id', $this->teacher->id)
            ->where('academic_year', '2026-2027')
            ->first();

        $this->assertNotNull($receipt, 'EvaluationWorkReceipt was not created.');
        $this->assertTrue((bool) $receipt->quiz_1_submitted);
        $this->assertSame('2026-10-25', $receipt->quiz_1_date?->format('Y-m-d'));

        // Verify notification delivered to teacher
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->teacher->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_completing_diagnostic_test_syncs_diagnostic_test_to_evaluation_receipt(): void
    {
        $log = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'title' => 'اختبار كشف المستوى وتشخيص المكتسبات القبلية',
            'date' => Carbon::parse('2026-09-22'),
            'status' => DailyLogStatus::Planned,
        ]);

        $log->markAsCompleted();

        $receipt = EvaluationWorkReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->diagnostic_test_submitted);
        $this->assertSame('2026-09-22', $receipt->diagnostic_test_date?->format('Y-m-d'));
    }

    public function test_completing_homework_daily_log_syncs_homework_receipt(): void
    {
        $log = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'title' => 'متابعة وتصحيح الواجب المنزلي 1',
            'homework' => 'حل التمارين 12 و 13 ص 40 كواجب منزلي',
            'date' => Carbon::parse('2026-11-05'),
            'status' => DailyLogStatus::Planned,
        ]);

        $log->markAsCompleted();

        $receipt = EvaluationWorkReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->homework_1_submitted);
        $this->assertSame('2026-11-05', $receipt->homework_1_date?->format('Y-m-d'));
    }

    public function test_direct_save_of_completed_daily_log_triggers_sync(): void
    {
        DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'title' => 'اختبار الفصل الأول',
            'date' => '2026-12-08',
            'status' => DailyLogStatus::Completed,
        ]);

        $receipt = EvaluationWorkReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->exam_1_submitted);
        $this->assertSame('2026-12-08', $receipt->exam_1_date?->format('Y-m-d'));
    }

    public function test_regular_lesson_does_not_affect_evaluation_receipt(): void
    {
        $log = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'title' => 'درس خاصيات متوازي الأضلاع وحل الأنشطة',
            'date' => Carbon::parse('2026-10-18'),
            'status' => DailyLogStatus::Planned,
        ]);

        $log->markAsCompleted();

        $receipt = EvaluationWorkReceipt::where('user_id', $this->teacher->id)->first();
        $this->assertNull($receipt);
    }

    public function test_daily_logs_table_displays_evaluation_badge(): void
    {
        $quizLog = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'title' => 'الفرض المحروس رقم 1',
            'date' => '2026-10-25',
            'status' => DailyLogStatus::Completed,
        ]);

        $this->actingAs($this->teacher);

        \Livewire\Livewire::test(\App\Filament\Resources\DailyLogResource\Pages\ListDailyLogs::class)
            ->assertCanSeeTableRecords([$quizLog])
            ->assertTableColumnExists('evaluation_work');
    }
}