<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\DailyLogResource;
use App\Filament\Resources\DailyLogResource\Pages\ListDailyLogs;
use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\OfficialReportService;
use App\Services\StudentIncidentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BehaviorObservationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_behavior_observations_list_with_follow_up_badges(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        BehaviorObservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'category' => 'positive',
            'notes' => 'مشاركة مميزة خلال الحصة.',
            'date' => today(),
            'is_followed_up' => true,
        ]);

        $this->actingAs($admin)
            ->get('/en/auth/behavior-observations')
            ->assertOk()
            ->assertSee(__('تمت المعالجة'));
    }

    public function test_student_incident_report_service_assembles_report_with_presets(): void
    {
        $service = app(StudentIncidentReportService::class);
        $student = Student::factory()->create(['first_name' => 'بلقاسم', 'last_name' => 'دحمان']);
        $teacher = User::factory()->create(['name' => 'الأستاذ فرطاس']);

        $assembled = $service->assembleReportText(
            student: $student,
            teacher: $teacher,
            category: 'disciplinary',
            facts: ['disruption', 'missing_tools'],
            actions: ['verbal_warning', 'seat_change'],
            recommendations: ['guardian_summons'],
            incidentDate: '2026/11/10',
            subjectName: 'الإعلام الآلي',
        );

        $this->assertStringContainsString('الموضوع: مخالفة انضباطية وسلوكية', $assembled);
        $this->assertStringContainsString('بلقاسم دحمان', $assembled);
        $this->assertStringContainsString('الإعلام الآلي', $assembled);
        $this->assertStringContainsString('تشويش مستمر ومقاطعة الشرح', $assembled);
        $this->assertStringContainsString('عدم إحضار الأدوات المدرسية', $assembled);
        $this->assertStringContainsString('تم تنبيه التلميذ(ة) شفوياً', $assembled);
        $this->assertStringContainsString('استدعاء ولي أمر التلميذ(ة)', $assembled);
        $this->assertStringContainsString('الأستاذ فرطاس', $assembled);
    }

    public function test_categories_have_mutually_exclusive_options_and_enforce_single_page_caps(): void
    {
        $service = app(StudentIncidentReportService::class);

        // Disciplinary category
        $discFacts = $service->getObservedFactsOptions('disciplinary');
        $this->assertArrayHasKey('disruption', $discFacts);
        $this->assertArrayNotHasKey('academic_excellence', $discFacts);
        $this->assertArrayNotHasKey('positive_cooperation', $discFacts);

        $discRecoms = $service->getRecommendationsOptions('disciplinary');
        $this->assertArrayHasKey('disciplinary_council', $discRecoms);
        $this->assertArrayNotHasKey('honor_certificate', $discRecoms);
        $this->assertArrayNotHasKey('board_of_honor', $discRecoms);

        // Positive category
        $posFacts = $service->getObservedFactsOptions('positive');
        $this->assertArrayHasKey('academic_excellence', $posFacts);
        $this->assertArrayHasKey('positive_cooperation', $posFacts);
        $this->assertArrayNotHasKey('disruption', $posFacts);
        $this->assertArrayNotHasKey('quarrel', $posFacts);

        $posRecoms = $service->getRecommendationsOptions('positive');
        $this->assertArrayHasKey('honor_certificate', $posRecoms);
        $this->assertArrayHasKey('board_of_honor', $posRecoms);
        $this->assertArrayNotHasKey('disciplinary_council', $posRecoms);
        $this->assertArrayNotHasKey('internal_regulation_action', $posRecoms);

        // Single-page capping test: 5 facts, 4 actions, 4 recoms -> capped to 3, 2, 2
        $assembled = $service->assembleReportText(
            facts: ['disruption', 'missing_tools', 'refusing_work', 'disrespect', 'phone_usage'],
            actions: ['verbal_warning', 'seat_change', 'private_dialogue', 'correspondence_note'],
            recommendations: ['guardian_summons', 'internal_regulation_action', 'disciplinary_council'],
        );

        $this->assertStringContainsString('تشويش مستمر', $assembled);
        $this->assertStringContainsString('عدم إحضار الأدوات', $assembled);
        $this->assertStringContainsString('رفض إنجاز الأنشطة', $assembled);
        $this->assertStringNotContainsString('التلفظ بعبارات غير لائقة', $assembled);
        $this->assertStringNotContainsString('استخدام الهاتف النقال', $assembled);
    }

    public function test_print_student_behavior_report_route_renders_official_layout_and_signatures(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['name' => 'فرطاس بوعزيز']);
        $student = Student::factory()->create(['first_name' => 'ياسين', 'last_name' => 'قادري']);

        $observation = BehaviorObservation::query()->create([
            'student_id' => $student->id,
            'recorded_by_id' => $teacher->id,
            'category' => 'disciplinary',
            'notes' => "أولاً: الوقائع المرصودة:\n• تشويش متكرر داخل الحصة.\n\nثانياً: المطلب المرفوع للإدارة:\n• استدعاء الولي.",
            'date' => today(),
            'is_followed_up' => false,
        ]);

        $url = route('print.student-behavior-report', [
            'locale' => 'ar',
            'behaviorObservation' => $observation->id,
        ]);

        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertSee('تقرير أستاذ(ة) حول تلميذ(ة)')
            ->assertSee('ياسين قادري')
            ->assertSee('فرطاس بوعزيز')
            ->assertSee('إمضاء وتأشيرة الأستاذ(ة)')
            ->assertSee('تأشيرة السيد(ة) مستشار(ة) التربية')
            ->assertSee('قرار وتأشيرة السيد(ة) مدير(ة) المؤسسة');
    }

    public function test_print_student_behavior_report_pdf_route_returns_stream(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $student = Student::factory()->create();

        $observation = BehaviorObservation::query()->create([
            'student_id' => $student->id,
            'recorded_by_id' => $admin->id,
            'category' => 'disciplinary',
            'notes' => 'تقرير سلوكي رسمي.',
            'date' => today(),
        ]);

        $this->mock(OfficialReportService::class, function ($mock): void {
            $mock->shouldReceive('pdfResponse')->once()->andReturn(
                response()->streamDownload(fn () => print 'pdf-content', 'تقرير-تلميذ.pdf', [
                    'Content-Type' => 'application/pdf',
                ])
            );
        });

        $url = route('print.student-behavior-report.pdf', [
            'locale' => 'ar',
            'behaviorObservation' => $observation->id,
        ]);

        $this->actingAs($admin)
            ->get($url)
            ->assertDownload();
    }

    public function test_teacher_cannot_print_another_teachers_behavior_report(): void
    {
        $teacherA = User::factory()->create(['role' => Role::Teacher->value]);
        $teacherB = User::factory()->create(['role' => Role::Teacher->value]);
        $student = Student::factory()->create();

        $observation = BehaviorObservation::query()->create([
            'student_id' => $student->id,
            'recorded_by_id' => $teacherB->id,
            'category' => 'disciplinary',
            'notes' => 'تقرير سري.',
            'date' => today(),
        ]);

        $url = route('print.student-behavior-report', [
            'locale' => 'ar',
            'behaviorObservation' => $observation->id,
        ]);

        $this->actingAs($teacherA)
            ->get($url)
            ->assertForbidden();
    }

    public function test_teacher_can_record_student_report_via_daily_log_action(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value, 'name' => 'الأستاذ فرطاس']);
        $student = Student::factory()->create(['first_name' => 'أحمد', 'last_name' => 'زروقي']);
        $classroom = Classroom::factory()->create(['name' => '3 م 4']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student->update(['group_id' => $group->id]);

        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
        ]);

        $dailyLog = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => today(),
        ]);

        $action = DailyLogResource::studentReportTableAction();
        $this->assertNotNull($action);

        Livewire::actingAs($teacher)
            ->test(ListDailyLogs::class)
            ->callTableAction('reportStudent', $dailyLog, [
                'student_id' => $student->id,
                'category' => 'disciplinary',
                'template_facts' => ['disruption', 'phone_usage'],
                'template_actions' => ['verbal_warning'],
                'template_recommendations' => ['guardian_summons'],
                'custom_notes' => 'تم ضبط الهاتف بحوزة التلميذ أثناء إنجاز النشاط.',
            ]);

        $this->assertDatabaseHas('behavior_observations', [
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'recorded_by_id' => $teacher->id,
            'category' => 'disciplinary',
        ]);

        $saved = BehaviorObservation::where('student_id', $student->id)->first();
        $this->assertNotNull($saved);
        $this->assertStringContainsString('أحمد زروقي', $saved->notes);
        $this->assertStringContainsString('تشويش مستمر', $saved->notes);
        $this->assertStringContainsString('استخدام الهاتف النقال', $saved->notes);
        $this->assertStringContainsString('استدعاء ولي أمر التلميذ(ة)', $saved->notes);
        $this->assertStringContainsString('تم ضبط الهاتف بحوزة التلميذ', $saved->notes);
    }
}
