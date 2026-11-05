<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\TransferReason;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Filament\Pages\StudentMobilityHub;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentMobilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMobilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_outward_transfer_updates_student_departure_and_inactivity(): void
    {
        $admin = User::factory()->admin()->create();
        $group = Group::factory()->create();
        $student = Student::factory()->create([
            'group_id' => $group->id,
            'is_active' => true,
            'departure_date' => null,
        ]);

        $service = app(StudentMobilityService::class);
        $transfer = $service->registerTransfer($student, [
            'transfer_type' => TransferType::Outward,
            'transfer_date' => '2026-10-15',
            'other_school_name' => 'متوسطة الإخوة بوحجة',
            'other_directorate_name' => 'مديرية التربية لولاية قسنطينة',
            'reason' => TransferReason::ChangeOfResidence,
            'reference_number' => 'ر.ت/2026/105',
            'status' => TransferStatus::Approved,
        ], $admin);

        $this->assertTrue($transfer->isOutward());
        $this->assertSame(TransferStatus::Approved, $transfer->status);
        $this->assertDatabaseHas('student_transfers', [
            'id' => $transfer->id,
            'student_id' => $student->id,
            'other_school_name' => 'متوسطة الإخوة بوحجة',
            'transfer_type' => 'outward',
        ]);

        // Student is marked inactive with departure date
        $student->refresh();
        $this->assertFalse($student->is_active);
        $this->assertSame('2026-10-15', $student->departure_date?->format('Y-m-d'));
    }

    public function test_register_inward_transfer_activates_student(): void
    {
        $admin = User::factory()->admin()->create();
        $group = Group::factory()->create();
        $student = Student::factory()->create([
            'group_id' => $group->id,
            'is_active' => false,
            'enrollment_date' => null,
        ]);

        $service = app(StudentMobilityService::class);
        $transfer = $service->registerTransfer($student, [
            'transfer_type' => TransferType::Inward,
            'transfer_date' => '2026-10-20',
            'other_school_name' => 'متوسطة ابن خلدون',
            'reason' => TransferReason::ParentEmployment,
            'status' => TransferStatus::Approved,
        ], $admin);

        $this->assertTrue($transfer->isInward());
        $student->refresh();
        $this->assertTrue($student->is_active);
        $this->assertSame('2026-10-20', $student->enrollment_date?->format('Y-m-d'));
    }

    public function test_update_transfer_status_syncs_student_state(): void
    {
        $admin = User::factory()->admin()->create();
        $service = app(StudentMobilityService::class);
        $group = Group::factory()->create();
        $student = Student::factory()->create([
            'group_id' => $group->id,
            'is_active' => true,
        ]);

        // 1. Pending transfer should NOT mark student inactive yet
        $transfer = $service->registerTransfer($student, [
            'transfer_type' => TransferType::Outward,
            'transfer_date' => '2026-10-25',
            'status' => TransferStatus::Pending,
        ], $admin);

        $this->assertTrue($student->fresh()->is_active);

        // 2. Approving it marks student inactive
        $service->updateTransferStatus($transfer, TransferStatus::Approved, $admin);
        $this->assertFalse($student->fresh()->is_active);
        $this->assertSame('2026-10-25', $student->fresh()->departure_date?->format('Y-m-d'));

        // 3. Cancelling it restores student to active
        $service->updateTransferStatus($transfer, TransferStatus::Cancelled, $admin);
        $this->assertTrue($student->fresh()->is_active);
    }

    public function test_mark_school_file_forwarded(): void
    {
        $service = app(StudentMobilityService::class);
        $transfer = StudentTransfer::factory()->create([
            'school_file_forwarded' => false,
            'school_file_forwarded_at' => null,
        ]);

        $service->markSchoolFileForwarded($transfer, '2026-10-28');
        $transfer->refresh();

        $this->assertTrue($transfer->school_file_forwarded);
        $this->assertSame('2026-10-28', $transfer->school_file_forwarded_at?->format('Y-m-d'));
    }

    public function test_mobility_kpis_computation(): void
    {
        $service = app(StudentMobilityService::class);

        // 3 inward approved, 1 outward approved, 2 pending
        StudentTransfer::factory()->count(3)->inward()->create([
            'academic_year' => '2026/2027',
            'status' => TransferStatus::Approved,
        ]);
        StudentTransfer::factory()->count(1)->outward()->create([
            'academic_year' => '2026/2027',
            'status' => TransferStatus::Approved,
        ]);
        StudentTransfer::factory()->count(2)->pending()->create([
            'academic_year' => '2026/2027',
        ]);

        $kpis = $service->getMobilityKpis('2026/2027');

        $this->assertSame(6, $kpis['total_transfers']);
        $this->assertSame(3, $kpis['inward_count']);
        $this->assertSame(1, $kpis['outward_count']);
        $this->assertSame(2, $kpis['pending_count']);
        $this->assertSame(2, $kpis['net_flow']); // 3 - 1 = +2
    }

    public function test_student_mobility_hub_accessible_to_admin_and_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(StudentMobilityHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('إدارة حركية وتحويلات التلاميذ وسجل القيد العام');

        $this->actingAs($teacher)
            ->get(StudentMobilityHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('إدارة حركية وتحويلات التلاميذ وسجل القيد العام');

        auth()->logout();
        $this->get(StudentMobilityHub::getUrl(['locale' => 'ar']))
            ->assertRedirect();
    }

    public function test_print_routes_render_certificate_ledger_and_forwarding_slip(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '3 م 2']);
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'طارق',
            'last_name' => 'بن زياد',
        ]);

        $transfer = StudentTransfer::factory()->outward()->create([
            'student_id' => $student->id,
            'other_school_name' => 'متوسطة الأمير عبد القادر',
            'reason' => TransferReason::ChangeOfResidence,
        ]);

        // 1. Certificate route
        $this->get(route('print.student-transfer.certificate', [
            'locale' => 'ar',
            'transfer' => $transfer,
        ]))
            ->assertOk()
            ->assertSee('شَهَادَةُ تَحْوِيلٍ وَانْتِقَالٍ مَدْرَسِيَّةٍ')
            ->assertSee('طارق')
            ->assertSee('بن زياد')
            ->assertSee('متوسطة الأمير عبد القادر');

        // 2. Annual Mobility Ledger route
        $this->get(route('print.student-transfer.ledger', [
            'locale' => 'ar',
        ]))
            ->assertOk()
            ->assertSee('سِجِلُّ حَرَكَةِ التَّلَامِيذِ (الْوَافِدُونَ وَالْمُغَادِرُونَ)')
            ->assertSee('متوسطة الأمير عبد القادر');

        // 3. School File Forwarding Slip route
        $this->get(route('print.student-transfer.forwarding-slip', [
            'locale' => 'ar',
            'transfer' => $transfer,
        ]))
            ->assertOk()
            ->assertSee('إرسال الملف المدرسي الأصلي للتلميذ(ة):')
            ->assertSee('الدفتر المدرسي / دفتر الملاحظات والمتابعة البيداغوجية')
            ->assertSee('وصل استلام الملف المدرسي');
    }

    public function test_teacher_authorization_on_print_routes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $unassignedTeacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create();
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->create(['group_id' => $group->id]);
        $subject = Subject::factory()->create();

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Tuesday,
        ]);

        $transfer = StudentTransfer::factory()->create(['student_id' => $student->id]);

        // Assigned teacher gets 200 on student certificate
        $this->actingAs($teacher)
            ->get(route('print.student-transfer.certificate', ['locale' => 'ar', 'transfer' => $transfer]))
            ->assertOk();

        // Unassigned teacher gets 403 on student certificate
        $this->actingAs($unassignedTeacher)
            ->get(route('print.student-transfer.certificate', ['locale' => 'ar', 'transfer' => $transfer]))
            ->assertForbidden();

        // Teacher cannot access full institutional ledger (admin only)
        $this->actingAs($teacher)
            ->get(route('print.student-transfer.ledger', ['locale' => 'ar']))
            ->assertForbidden();
    }

    public function test_pdf_download_routes_stream_when_mocked(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $transfer = StudentTransfer::factory()->create();

        $this->mock(StudentMobilityService::class, function ($mock): void {
            $mock->shouldReceive('generateTransferCertificatePdf')
                ->once()
                ->andReturn('%PDF-1.4 mock transfer certificate');

            $mock->shouldReceive('generateMobilityLedgerPdf')
                ->once()
                ->andReturn('%PDF-1.4 mock mobility ledger');

            $mock->shouldReceive('generateForwardingSlipPdf')
                ->once()
                ->andReturn('%PDF-1.4 mock forwarding slip');
        });

        $this->get(route('print.student-transfer.certificate.pdf', ['locale' => 'ar', 'transfer' => $transfer]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('print.student-transfer.ledger.pdf', ['locale' => 'ar']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('print.student-transfer.forwarding-slip.pdf', ['locale' => 'ar', 'transfer' => $transfer]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
