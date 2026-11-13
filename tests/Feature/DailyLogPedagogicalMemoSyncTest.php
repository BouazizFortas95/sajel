<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Level;
use App\Models\PedagogicalMemo;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyLogPedagogicalMemoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_log_auto_links_matching_pedagogical_memo_on_save(): void
    {
        $teacher = User::factory()->create([
            'role' => Role::Teacher->value,
            'name' => 'الأستاذ البيداغوجي',
        ]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1 م 1']);

        $schedule = Schedule::create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => DayOfWeek::Monday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-20',
        ]);

        $memo = PedagogicalMemo::create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'title' => 'تعريف المعلوماتية والحاسوب',
            'competence' => 'أن يتعرف المتعلم على الحاسوب.',
            'starting_situation' => 'وضعية تمهيدية لاكتشاف الحاسوب.',
            'activities' => [
                [
                    'phase_name' => 'بناء التعلمات',
                    'teacher_role' => 'توجيه التلاميذ',
                    'student_role' => 'الملاحظة والاستنتاج',
                    'written_trace' => 'المعلوماتية علم معالجة المعلومات آلياً.',
                ],
            ],
            'evaluation' => 'تقويم تشخيصي وبطاقات عمل.',
        ]);

        // Create a DailyLog with matching title
        $log = DailyLog::create([
            'schedule_id' => $schedule->id,
            'date' => '2026-10-05',
            'title' => 'تعريف المعلوماتية والحاسوب',
            'content_data' => [],
            'status' => DailyLogStatus::Completed,
        ]);

        $this->assertEquals($memo->id, $log->pedagogical_memo_id);
        $this->assertTrue($log->pedagogicalMemo->is($memo));
        $this->assertTrue($memo->dailyLogs->contains($log));
    }

    public function test_pedagogical_memo_to_daily_log_content_data_transformation(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);

        $memo = PedagogicalMemo::create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'title' => 'نظام التشغيل ومفهوم الملفات',
            'competence' => 'أن يتحكم التلميذ في تنظيم الملفات والمجلدات.',
            'starting_situation' => 'كيف نصنف ملفاتنا في الحاسوب؟',
            'activities' => [
                [
                    'phase_name' => 'إرساء الموارد',
                    'teacher_role' => 'شرح هيكل المجلدات',
                    'student_role' => 'إنشاء مجلد وحفظ ملف',
                    'written_trace' => 'المجلد هو حاوية رقمية لحفظ الملفات.',
                ],
            ],
            'evaluation' => 'تطبيق عملي: أنشئ مجلداً باسمك.',
        ]);

        $blocks = $memo->toDailyLogContentData();

        $this->assertNotEmpty($blocks);
        $this->assertEquals('lesson_structure', $blocks[0]['type']);
        $this->assertEquals('أن يتحكم التلميذ في تنظيم الملفات والمجلدات.', $blocks[0]['data']['competence']);
        $phases = $blocks[0]['data']['lesson_phases'];
        $this->assertCount(3, $phases);
        $this->assertEquals('وضعية الانطلاق والتهيئة النفسية', $phases[0]['phase_name']);
        $this->assertEquals('إرساء الموارد', $phases[1]['phase_name']);
        $this->assertEquals('استثمار المكتسبات والتقويم الختامي', $phases[2]['phase_name']);
    }

    public function test_printed_register_displays_linked_pedagogical_memo(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1 م 1']);

        $schedule = Schedule::create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => DayOfWeek::Tuesday->value,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'valid_from' => '2026-09-20',
        ]);

        $memo = PedagogicalMemo::create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'title' => 'معالج النصوص Word',
            'competence' => 'كتابة وتنسيق وثيقة.',
            'starting_situation' => 'تمهيد',
            'activities' => [],
        ]);

        $log = DailyLog::create([
            'schedule_id' => $schedule->id,
            'pedagogical_memo_id' => $memo->id,
            'date' => '2026-10-06',
            'title' => 'معالج النصوص Word',
            'content_data' => $memo->toDailyLogContentData(),
            'actual_content' => '<p>تم إنجاز الدرس والتطبيقات</p>',
            'status' => DailyLogStatus::Completed,
        ]);

        $this->actingAs($teacher);

        $response = $this->get(route('print.register', [
            'classroom_id' => $classroom->id,
            'ids' => [$log->id],
        ]));

        $response->assertOk();
        $response->assertSee('المذكرة البيداغوجية:');
        $response->assertSee('معالج النصوص Word');
        $response->assertSee(sprintf('رقم %02d', $memo->id));
    }
}
