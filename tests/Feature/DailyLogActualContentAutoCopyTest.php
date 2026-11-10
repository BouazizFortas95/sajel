<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Filament\Resources\DailyLogResource\Pages\CreateDailyLog;
use App\Filament\Resources\DailyLogResource\Pages\EditDailyLog;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DailyLogActualContentAutoCopyTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $this->teacher->update(['primary_subject_id' => $subject->id]);

        $classroom = Classroom::factory()->create(['name' => '1 م 1']);

        $this->schedule = Schedule::factory()->create([
            'user_id' => $this->teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'sunday',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
    }

    public function test_completed_daily_log_without_actual_content_saves_successfully(): void
    {
        $this->actingAs($this->teacher);

        $blockId = (string) Str::uuid();
        $phaseId = (string) Str::uuid();

        Livewire::test(CreateDailyLog::class)
            ->fillForm([
                'schedule_id' => $this->schedule->id,
                'date' => '2026-11-08',
                'title' => 'المخططات الانسيابية',
                'status' => DailyLogStatus::Completed->value,
                'actual_content' => null, // Left empty intentionally
                'content_data' => [
                    $blockId => [
                        'type' => 'lesson_structure',
                        'data' => [
                            'competence' => 'أن يرسم التلميذ مخططا انسيابيا بسيطا.',
                            'lesson_phases' => [
                                $phaseId => [
                                    'phase_name' => 'وضعية الانطلاق',
                                    'content' => 'تمهيد ومناقشة أمثلة من الحياة اليومية.',
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $log = DailyLog::where('schedule_id', $this->schedule->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(DailyLogStatus::Completed, $log->status);
        $this->assertNotNull($log->actual_content);
        $this->assertStringContainsString('المخططات الانسيابية', $log->actual_content);
        $this->assertStringContainsString('أن يرسم التلميذ مخططا انسيابيا بسيطا', $log->actual_content);
    }

    public function test_editing_daily_log_to_completed_with_blank_actual_content_auto_populates_summary(): void
    {
        $this->actingAs($this->teacher);

        $log = DailyLog::factory()->create([
            'schedule_id' => $this->schedule->id,
            'date' => '2026-11-08',
            'title' => 'مكونات الحاسوب',
            'status' => DailyLogStatus::Planned,
            'actual_content' => null,
            'content_data' => [
                (string) Str::uuid() => [
                    'type' => 'lesson_structure',
                    'data' => [
                        'competence' => 'التعرف على العتاد والبرمجيات.',
                        'lesson_phases' => [
                            (string) Str::uuid() => [
                                'phase_name' => 'بناء التعلمات',
                                'content' => 'شرح لوحة الأم والمعالج والذاكرة.',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Livewire::test(EditDailyLog::class, ['record' => $log->getRouteKey()])
            ->callAction('confirmCompletion')
            ->assertHasNoActionErrors();

        $log->refresh();
        $this->assertSame(DailyLogStatus::Completed, $log->status);
        $this->assertNotNull($log->actual_content);
        $this->assertStringContainsString('مكونات الحاسوب', $log->actual_content);
        $this->assertStringContainsString('التعرف على العتاد والبرمجيات', $log->actual_content);
    }

    public function test_explicit_actual_content_is_preserved_when_provided(): void
    {
        $this->actingAs($this->teacher);

        Livewire::test(CreateDailyLog::class)
            ->fillForm([
                'schedule_id' => $this->schedule->id,
                'date' => '2026-11-08',
                'title' => 'درس مخصص',
                'status' => DailyLogStatus::Completed->value,
                'actual_content' => '<p>تم إنجاز النشاط الأول والثاني فقط نظراً لضيق الوقت.</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $log = DailyLog::where('schedule_id', $this->schedule->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('<p>تم إنجاز النشاط الأول والثاني فقط نظراً لضيق الوقت.</p>', $log->actual_content);
    }

    public function test_auto_populated_summary_includes_active_learning_strategy_badge(): void
    {
        $this->actingAs($this->teacher);

        $blockId = (string) Str::uuid();
        $phaseId = (string) Str::uuid();

        Livewire::test(CreateDailyLog::class)
            ->fillForm([
                'schedule_id' => $this->schedule->id,
                'date' => '2026-11-08',
                'title' => 'الشبكات والإنترنت',
                'status' => DailyLogStatus::Completed->value,
                'actual_content' => null,
                'content_data' => [
                    $blockId => [
                        'type' => 'lesson_structure',
                        'data' => [
                            'competence' => 'يتعرف على أنواع الشبكات المحلية.',
                            'lesson_phases' => [
                                $phaseId => [
                                    'phase_name' => 'بناء التعلمات',
                                    'strategy' => 'التفكير — المشاركة — العرض',
                                    'content' => 'مقارنة بين الشبكة السلكية واللاسلكية.',
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $log = DailyLog::where('schedule_id', $this->schedule->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('class="strategy-badge"', $log->actual_content);
        $this->assertStringContainsString('التفكير — المشاركة — العرض', $log->actual_content);
    }
}
