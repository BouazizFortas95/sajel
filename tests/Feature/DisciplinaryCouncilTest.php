<?php

namespace Tests\Feature;

use App\Enums\DisciplinarySanction;
use App\Enums\DisciplinaryStatus;
use App\Enums\InfractionDegree;
use App\Filament\Pages\DisciplinaryCouncilHub;
use App\Models\DisciplinaryRecord;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\DisciplinaryCouncilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplinaryCouncilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::create([
            'academic_year' => '2026/2027',
            'school_name' => 'متوسطة الشهيد زبانة',
            'directorate_name' => 'الجزائر وسط',
        ]);
    }

    public function test_can_schedule_disciplinary_session(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $service = app(DisciplinaryCouncilService::class);
        $record = $service->scheduleSession(
            student: $student,
            hearingDate: '2026-11-10 14:00:00',
            degree: InfractionDegree::SecondDegree,
            incidentDescription: 'المشاركة في شجار داخل الفناء المدرسي وعرقلة السير العادي للدروس',
            incidentDate: '2026-11-06',
            reportedBy: $admin,
            sessionNumber: 'CD-2026-001',
            guardianSummoned: true,
            academicYear: '2026/2027'
        );

        $this->assertInstanceOf(DisciplinaryRecord::class, $record);
        $this->assertSame(DisciplinaryStatus::Scheduled, $record->status);
        $this->assertSame(InfractionDegree::SecondDegree, $record->degree);
        $this->assertTrue($record->guardian_summoned);
        $this->assertNotNull($record->guardian_summoned_at);

        $this->assertDatabaseHas('disciplinary_records', [
            'id' => $record->id,
            'student_id' => $student->id,
            'session_number' => 'CD-2026-001',
            'status' => DisciplinaryStatus::Scheduled->value,
            'infraction_degree' => InfractionDegree::SecondDegree->value,
        ]);
    }

    public function test_can_record_deliberation_with_temporary_exclusion_and_restitution(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $record = DisciplinaryRecord::factory()->scheduled()->create([
            'student_id' => $student->id,
            'degree' => InfractionDegree::SecondDegree,
            'academic_year' => '2026/2027',
        ]);

        $service = app(DisciplinaryCouncilService::class);
        $updated = $service->recordDeliberation(
            record: $record,
            sanction: DisciplinarySanction::TemporaryExclusion,
            exclusionDays: 3,
            exclusionStartDate: '2026-11-12',
            exclusionEndDate: '2026-11-14',
            restitutionAmount: 1850.50,
            defenseSummary: 'اعترف التلميذ بالخطأ وتعهد بالانضباط، وحضر الولي والتمس التخفيف',
            decisionNotes: 'إقصاء مؤقت لمدة 3 أيام مع إلزام الولي بتعويض الزجاج المكسور',
            president: $admin,
            deliberatedAt: '2026-11-11'
        );

        $this->assertSame(DisciplinaryStatus::Deliberated, $updated->status);
        $this->assertSame(DisciplinarySanction::TemporaryExclusion, $updated->sanction);
        $this->assertSame(3, $updated->exclusion_days);
        $this->assertEquals(1850.50, $updated->restitution_amount);
        $this->assertSame('2026-11-11', $updated->deliberated_at->format('Y-m-d'));

        $this->assertDatabaseHas('disciplinary_records', [
            'id' => $record->id,
            'status' => DisciplinaryStatus::Deliberated->value,
            'decision' => DisciplinarySanction::TemporaryExclusion->value,
            'exclusion_days' => 3,
        ]);
    }

    public function test_guardian_summoned_and_notified_toggles(): void
    {
        $record = DisciplinaryRecord::factory()->create([
            'guardian_summoned' => false,
            'guardian_notified' => false,
        ]);

        $service = app(DisciplinaryCouncilService::class);

        $service->markGuardianSummoned($record, '2026-11-05 10:00:00');
        $this->assertTrue($record->fresh()->guardian_summoned);
        $this->assertNotNull($record->fresh()->guardian_summoned_at);

        $service->markGuardianNotified($record, '2026-11-12 16:30:00');
        $this->assertTrue($record->fresh()->guardian_notified);
        $this->assertNotNull($record->fresh()->guardian_notified_at);
    }

    public function test_disciplinary_kpis_calculation(): void
    {
        $student = Student::factory()->create();

        // 1 scheduled
        DisciplinaryRecord::factory()->scheduled()->create([
            'student_id' => $student->id,
            'degree' => InfractionDegree::FirstDegree,
            'academic_year' => '2026/2027',
            'guardian_summoned' => true,
            'guardian_notified' => false,
        ]);

        // 1 warning
        DisciplinaryRecord::factory()->deliberated()->create([
            'student_id' => $student->id,
            'degree' => InfractionDegree::FirstDegree,
            'sanction' => DisciplinarySanction::Warning,
            'compensation_amount' => 0,
            'academic_year' => '2026/2027',
            'guardian_notified' => true,
        ]);

        // 1 temporary exclusion with restitution
        DisciplinaryRecord::factory()->deliberated()->create([
            'student_id' => $student->id,
            'degree' => InfractionDegree::SecondDegree,
            'sanction' => DisciplinarySanction::TemporaryExclusion,
            'exclusion_days' => 4,
            'restitution_amount' => 2500.00,
            'academic_year' => '2026/2027',
            'guardian_notified' => true,
        ]);

        $service = app(DisciplinaryCouncilService::class);
        $kpis = $service->getDisciplinaryKpis('2026/2027');

        $this->assertSame(3, $kpis['total_records']);
        $this->assertSame(2, $kpis['deliberated_count']);
        $this->assertSame(1, $kpis['scheduled_count']);
        $this->assertSame(1, $kpis['warning_count']);
        $this->assertSame(1, $kpis['temporary_exclusion_count']);
        $this->assertEquals(2500.00, $kpis['total_restitution_amount']);
        $this->assertEquals(66.7, $kpis['guardian_notified_rate']);
    }

    public function test_authorized_user_can_view_summons_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $record = DisciplinaryRecord::factory()->scheduled()->create([
            'academic_year' => '2026/2027',
            'session_number' => 'CD-TEST-100',
        ]);

        $response = $this->get(route('print.disciplinary.summons', [
            'locale' => 'ar',
            'record' => $record->id,
        ]));

        $response->assertOk();
        $response->assertSee($record->student->first_name);
        $response->assertSee('CD-TEST-100');
    }

    public function test_authorized_user_can_view_minutes_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $record = DisciplinaryRecord::factory()->deliberated()->create([
            'academic_year' => '2026/2027',
            'session_number' => 'PV-TEST-200',
            'sanction' => DisciplinarySanction::Warning,
        ]);

        $response = $this->get(route('print.disciplinary.minutes', [
            'locale' => 'ar',
            'record' => $record->id,
        ]));

        $response->assertOk();
        $response->assertSee('PV-TEST-200');
        $response->assertSee($record->student->first_name);
    }

    public function test_authorized_user_can_view_sanction_notification_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $record = DisciplinaryRecord::factory()->deliberated()->create([
            'academic_year' => '2026/2027',
            'sanction' => DisciplinarySanction::TemporaryExclusion,
            'exclusion_days' => 5,
        ]);

        $response = $this->get(route('print.disciplinary.notification', [
            'locale' => 'ar',
            'record' => $record->id,
        ]));

        $response->assertOk();
        $response->assertSee($record->student->first_name);
        $response->assertSee('5');
    }

    public function test_admin_can_view_annual_ledger(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        DisciplinaryRecord::factory()->count(2)->create([
            'academic_year' => '2026/2027',
        ]);

        $response = $this->get(route('print.disciplinary.ledger', [
            'locale' => 'ar',
            'academic_year' => '2026/2027',
        ]));

        $response->assertOk();
        $response->assertSee('2026/2027');
    }

    public function test_non_admin_cannot_view_annual_ledger(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);

        $response = $this->get(route('print.disciplinary.ledger', [
            'locale' => 'ar',
            'academic_year' => '2026/2027',
        ]));

        $response->assertForbidden();
    }

    public function test_pdf_download_routes_stream_when_mocked(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $record = DisciplinaryRecord::factory()->deliberated()->create([
            'academic_year' => '2026/2027',
        ]);

        $this->mock(DisciplinaryCouncilService::class, function ($mock): void {
            $mock->shouldReceive('assembleSummonsLetter')->andReturn([]);
            $mock->shouldReceive('generateSummonsPdf')
                ->once()
                ->with(\Mockery::type(DisciplinaryRecord::class))
                ->andReturn('%PDF-1.4 mock summons');

            $mock->shouldReceive('assembleCouncilMinutes')->andReturn([]);
            $mock->shouldReceive('generateCouncilMinutesPdf')
                ->once()
                ->with(\Mockery::type(DisciplinaryRecord::class))
                ->andReturn('%PDF-1.4 mock minutes');

            $mock->shouldReceive('assembleSanctionNotification')->andReturn([]);
            $mock->shouldReceive('generateSanctionNotificationPdf')
                ->once()
                ->with(\Mockery::type(DisciplinaryRecord::class))
                ->andReturn('%PDF-1.4 mock notification');

            $mock->shouldReceive('resolveAcademicYear')->andReturn('2026/2027');
            $mock->shouldReceive('generateAnnualDisciplinaryLedgerPdf')
                ->once()
                ->with('2026/2027')
                ->andReturn('%PDF-1.4 mock ledger');
        });

        $this->get(route('print.disciplinary.summons.pdf', ['locale' => 'ar', 'record' => $record->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('print.disciplinary.minutes.pdf', ['locale' => 'ar', 'record' => $record->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('print.disciplinary.notification.pdf', ['locale' => 'ar', 'record' => $record->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('print.disciplinary.ledger.pdf', ['locale' => 'ar', 'academic_year' => '2026/2027']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_filament_disciplinary_hub_page_can_render(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $student = Student::factory()->create();
        DisciplinaryRecord::factory()->scheduled()->create([
            'student_id' => $student->id,
            'academic_year' => '2026/2027',
        ]);

        Livewire::test(DisciplinaryCouncilHub::class)
            ->assertSuccessful()
            ->assertSee($student->first_name)
            ->set('activeTab', 'sanctions')
            ->assertSuccessful()
            ->set('activeTab', 'analytics')
            ->assertSuccessful();
    }
}
