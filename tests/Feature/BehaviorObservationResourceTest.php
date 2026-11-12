<?php

namespace Tests\Feature;

use App\Enums\InfractionDegree;
use App\Enums\Role;
use App\Filament\Resources\BehaviorObservationResource\Pages\ListBehaviorObservations;
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
use App\Services\DisciplinaryCouncilService;
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
            ->assertSee(__('معالجة داخلية بيداغوجية'));
    }

    public function test_administrative_action_select_column_updates_and_executes_directly(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $student = Student::factory()->create(['first_name' => 'طارق', 'last_name' => 'عباسي']);
        $classroom = Classroom::factory()->create(['name' => '3 م 2']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student->update(['group_id' => $group->id]);

        $observation = BehaviorObservation::create([
            'student_id' => $student->id,
            'category' => 'disciplinary',
            'notes' => 'عرقلة الشرح والتشويش داخل القسم.',
            'date' => today(),
            'is_followed_up' => false,
        ]);

        $this->assertEquals('pending', $observation->administrative_action);

        // 1. Select guardian summons directly from SelectColumn
        Livewire::actingAs($admin)
            ->test(ListBehaviorObservations::class)
            ->assertTableColumnExists('administrative_action')
            ->call('updateTableColumnState', 'administrative_action', (string) $observation->getKey(), 'guardian_summons');

        $fresh = $observation->fresh();
        $this->assertTrue($fresh->hasGuardianSummons());
        $this->assertTrue($fresh->is_followed_up);
        $this->assertEquals('guardian_summons', $fresh->administrative_action);

        // 2. Select disciplinary council directly from SelectColumn
        Livewire::actingAs($admin)
            ->test(ListBehaviorObservations::class)
            ->call('updateTableColumnState', 'administrative_action', (string) $observation->getKey(), 'disciplinary_council');

        $fresh = $observation->fresh();
        $this->assertTrue($fresh->hasDisciplinaryRecord());
        $this->assertEquals('disciplinary_council', $fresh->administrative_action);
        $this->assertDatabaseHas('disciplinary_records', [
            'id' => $fresh->disciplinary_record_id,
            'student_id' => $student->id,
        ]);

        // 3. Select internal follow-up directly from SelectColumn
        Livewire::actingAs($admin)
            ->test(ListBehaviorObservations::class)
            ->call('updateTableColumnState', 'administrative_action', (string) $observation->getKey(), 'internal_followup');

        $fresh = $observation->fresh();
        $this->assertTrue($fresh->is_followed_up);
        $this->assertEquals('internal_followup', $fresh->administrative_action);

        // 4. Reset to pending directly from SelectColumn
        Livewire::actingAs($admin)
            ->test(ListBehaviorObservations::class)
            ->call('updateTableColumnState', 'administrative_action', (string) $observation->getKey(), 'pending');

        $fresh = $observation->fresh();
        $this->assertFalse($fresh->is_followed_up);
        $this->assertEquals('pending', $fresh->administrative_action);
    }

    public function test_teacher_cannot_change_administrative_action_of_another_teachers_report(): void
    {
        $teacher1 = User::factory()->create(['role' => Role::Teacher->value]);
        $teacher2 = User::factory()->create(['role' => Role::Teacher->value]);
        $student = Student::factory()->create();

        $observation = BehaviorObservation::create([
            'student_id' => $student->id,
            'recorded_by_id' => $teacher1->id,
            'category' => 'disciplinary',
            'notes' => 'مخالفة انضباطية.',
            'date' => today(),
            'is_followed_up' => false,
        ]);

        $test = Livewire::actingAs($teacher2)
            ->test(ListBehaviorObservations::class);

        $column = $test->instance()->getTable()->getColumn('administrative_action');
        $column->record($observation);
        $this->assertTrue($column->isDisabled());
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
        $this->assertTrue($saved->hasGuardianSummons());
    }

    public function test_behavior_observation_referral_to_disciplinary_council(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $student = Student::factory()->create(['first_name' => 'ياسين', 'last_name' => 'قادري']);
        $classroom = Classroom::factory()->create(['name' => '4 م 2']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student->update(['group_id' => $group->id]);

        $observation = BehaviorObservation::create([
            'student_id' => $student->id,
            'category' => 'disciplinary',
            'notes' => 'شجار عنيف واعتداء لفظي داخل القسم.',
            'date' => today(),
            'is_followed_up' => false,
        ]);

        $this->assertFalse($observation->hasDisciplinaryRecord());

        $councilService = app(DisciplinaryCouncilService::class);
        $discRecord = $councilService->scheduleSession(
            student: $student,
            data: [
                'session_date' => now()->addDays(2)->setTime(11, 0),
                'infraction_degree' => InfractionDegree::ThirdDegree,
                'incident_description' => $observation->notes,
                'incident_date' => $observation->date->toDateString(),
                'guardian_summoned' => true,
            ]
        );

        $observation->update([
            'disciplinary_record_id' => $discRecord->id,
            'is_followed_up' => true,
            'follow_up_notes' => 'تمت الإحالة إلى مجلس التأديب (جلسة رقم: '.$discRecord->session_number.')',
        ]);

        $this->assertTrue($observation->fresh()->hasDisciplinaryRecord());
        $this->assertEquals($discRecord->id, $observation->fresh()->disciplinary_record_id);

        // Disciplinary summons route works and shows official contents
        $response = $this->actingAs($admin)
            ->get(route('print.disciplinary.summons', [
                'locale' => 'ar',
                'record' => $discRecord->id,
            ]));

        $response->assertOk()
            ->assertSee('استدعاء للمثول أمام مجلس التأديب')
            ->assertSee('ياسين قادري')
            ->assertSee($discRecord->session_number)
            ->assertSee('الجمهورية الجزائرية الديمقراطية الشعبية');
    }

    public function test_guardian_summons_generation_and_official_print_view(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value, 'name' => 'الأستاذ بن سالم']);
        $student = Student::factory()->create([
            'first_name' => 'سارة',
            'last_name' => 'منصوري',
            'guardian_name' => 'منصوري كمال',
            'guardian_phone' => '0661234567',
        ]);
        $classroom = Classroom::factory()->create(['name' => '2 م 1']);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $student->update(['group_id' => $group->id]);

        $observation = BehaviorObservation::create([
            'student_id' => $student->id,
            'recorded_by_id' => $teacher->id,
            'category' => 'disciplinary',
            'notes' => 'تكرار التشويش وعدم إحضار الكراس والكتب بصفة مستمرة.',
            'date' => today(),
            'guardian_summons_sent_at' => today(),
            'guardian_summons_appointment_at' => now()->addDay()->setTime(10, 0),
            'guardian_summons_notes' => 'مقابلة السيد الأستاذ ومستشار التربية لبحث أسباب الإهمال المدرسي.',
            'is_followed_up' => true,
        ]);

        $this->assertTrue($observation->hasGuardianSummons());

        $response = $this->actingAs($teacher)
            ->get(route('print.student-behavior-report.guardian-summons', [
                'locale' => 'ar',
                'behaviorObservation' => $observation,
            ]));

        $response->assertOk()
            ->assertSee('استدعاء ولي أمر التلميذ(ة)')
            ->assertSee('سارة منصوري')
            ->assertSee('منصوري كمال')
            ->assertSee('0661234567')
            ->assertSee('2 م 1')
            ->assertSee('إمضاء وتأشيرة الأستاذ(ة)')
            ->assertSee('تأشيرة مستشار(ة) التربية / ناظر(ة) الدروس')
            ->assertSee('تأشيرة وختم السيد(ة) مدير(ة) المؤسسة')
            ->assertSee('وصل استلام استدعاء ولي الأمر');
    }

    public function test_guardian_summons_pdf_download(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $student = Student::factory()->create();
        $observation = BehaviorObservation::create([
            'student_id' => $student->id,
            'recorded_by_id' => $teacher->id,
            'category' => 'disciplinary',
            'notes' => 'مخالفة انضباطية.',
            'date' => today(),
            'guardian_summons_sent_at' => today(),
        ]);

        $mockReports = $this->mock(OfficialReportService::class);
        $mockReports->shouldReceive('pdfResponse')
            ->once()
            ->andReturn(response()->streamDownload(fn () => print ('PDF_BINARY_CONTENT'), 'summons.pdf'));

        $this->actingAs($teacher)
            ->get(route('print.student-behavior-report.guardian-summons.pdf', [
                'locale' => 'ar',
                'behaviorObservation' => $observation,
            ]))
            ->assertDownload();
    }

    public function test_guardian_receipt_and_attendance_tracking(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $student = Student::factory()->create();
        $observation = BehaviorObservation::create([
            'student_id' => $student->id,
            'category' => 'disciplinary',
            'notes' => 'مخالفة انضباطية تستوجب استدعاء الولي.',
            'date' => today(),
            'administrative_action' => 'guardian_summons',
            'guardian_summons_sent_at' => today(),
            'guardian_summons_appointment_at' => now()->addDay(),
        ]);

        $this->assertFalse($observation->hasGuardianReceiptReturned());
        $this->assertSame('pending', $observation->guardian_attendance_status);

        // Mark receipt returned
        $observation->markReceiptReturned(true);
        $observation->refresh();
        $this->assertTrue($observation->hasGuardianReceiptReturned());
        $this->assertNotNull($observation->guardian_receipt_returned_at);

        // Update attendance to attended with notes
        $observation->updateGuardianAttendance('attended', 'تعهد الولي بمراقبة كراس التلميذ يومياً.');
        $observation->refresh();
        $this->assertSame('attended', $observation->guardian_attendance_status);
        $this->assertSame('حضر الولي', $observation->guardianAttendanceStatusLabel());
        $this->assertSame('success', $observation->guardianAttendanceStatusColor());
        $this->assertTrue($observation->is_followed_up);
        $this->assertStringContainsString('تعهد الولي', $observation->guardian_attendance_notes);

        // Test Livewire table interaction
        Livewire::actingAs($admin)
            ->test(ListBehaviorObservations::class)
            ->assertCanSeeTableRecords([$observation])
            ->assertTableColumnExists('is_guardian_receipt_returned')
            ->assertTableColumnExists('guardian_attendance_status')
            ->call('updateTableColumnState', 'is_guardian_receipt_returned', (string) $observation->getKey(), false)
            ->call('updateTableColumnState', 'guardian_attendance_status', (string) $observation->getKey(), 'absent');

        $observation->refresh();
        $this->assertFalse($observation->hasGuardianReceiptReturned());
        $this->assertSame('absent', $observation->guardian_attendance_status);
    }
}
