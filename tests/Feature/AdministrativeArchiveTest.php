<?php

namespace Tests\Feature;

use App\Enums\HandoverRole;
use App\Enums\MailConfidentiality;
use App\Enums\MailStatus;
use App\Enums\OfficialMailType;
use App\Enums\Role;
use App\Filament\Pages\AdministrativeArchiveHub;
use App\Models\AdministrativeHandover;
use App\Models\OfficialMail;
use App\Models\StaffInstallation;
use App\Models\User;
use App\Services\AdministrativeArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 46 — Administrative Archive Feature Tests
 *
 * Covers: installations, handovers, official mail registry, service layer,
 * print routes access control, enum integrity, and health check schema.
 */
class AdministrativeArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $principal;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->principal = User::factory()->create(['role' => Role::Principal]);
        $this->teacher = User::factory()->create(['role' => Role::Teacher]);
    }

    // ─── 1. Database Schema ───────────────────────────────────────────────────

    public function test_administrative_archive_tables_exist(): void
    {
        $this->assertTrue(\Schema::hasTable('staff_installations'));
        $this->assertTrue(\Schema::hasTable('administrative_handovers'));
        $this->assertTrue(\Schema::hasTable('official_mails'));
    }

    // ─── 2. Enum Integrity ────────────────────────────────────────────────────

    public function test_official_mail_type_enum_prefix(): void
    {
        $this->assertSame('ARR', OfficialMailType::Incoming->prefix());
        $this->assertSame('DEP', OfficialMailType::Outgoing->prefix());
    }

    public function test_mail_confidentiality_colors(): void
    {
        $this->assertSame('danger', MailConfidentiality::Confidential->color());
        $this->assertSame('warning', MailConfidentiality::Urgent->color());
        $this->assertSame('gray', MailConfidentiality::Normal->color());
    }

    public function test_mail_status_labels(): void
    {
        $this->assertNotEmpty(MailStatus::Pending->label());
        $this->assertNotEmpty(MailStatus::Processed->label());
        $this->assertNotEmpty(MailStatus::Archived->label());
    }

    public function test_handover_role_labels(): void
    {
        foreach (HandoverRole::cases() as $role) {
            $this->assertNotEmpty($role->label());
            $this->assertNotEmpty($role->labelFr());
        }
    }

    // ─── 3. Installation Service ─────────────────────────────────────────────

    public function test_service_records_staff_installation(): void
    {
        $this->actingAs($this->principal);
        $staff = User::factory()->create(['role' => Role::Teacher]);

        $service = app(AdministrativeArchiveService::class);
        $inst = $service->recordInstallation([
            'user_id' => $staff->id,
            'installation_date' => '2026-09-01',
            'role_label' => 'أستاذ تعليم متوسط',
            'statutory_grade' => 'أستاذ رئيسي',
        ]);

        $this->assertInstanceOf(StaffInstallation::class, $inst);
        $this->assertDatabaseHas('staff_installations', [
            'user_id' => $staff->id,
            'role_label' => 'أستاذ تعليم متوسط',
        ]);
    }

    public function test_installation_assemble_data_contains_school_and_user(): void
    {
        $this->actingAs($this->principal);
        $staff = User::factory()->create(['role' => Role::Teacher]);

        $inst = StaffInstallation::create([
            'user_id' => $staff->id,
            'installation_date' => '2026-09-01',
            'role_label' => 'ناظر',
            'recorded_by' => $this->principal->id,
        ]);

        $service = app(AdministrativeArchiveService::class);
        $data = $service->assembleInstallationSheetData($inst);

        $this->assertArrayHasKey('installation', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('school', $data);
        $this->assertArrayHasKey('printed_at', $data);
    }

    // ─── 4. Handover Service ─────────────────────────────────────────────────

    public function test_service_records_administrative_handover(): void
    {
        $this->actingAs($this->principal);
        $incoming = User::factory()->create(['role' => Role::Principal]);

        $service = app(AdministrativeArchiveService::class);
        $handover = $service->recordHandover([
            'role' => 'principal',
            'handover_date' => '2026-10-01',
            'outgoing_user_id' => $this->principal->id,
            'incoming_user_id' => $incoming->id,
            'inventory_summary' => 'جرد كامل بدون ملاحظات',
        ]);

        $this->assertInstanceOf(AdministrativeHandover::class, $handover);
        $this->assertDatabaseHas('administrative_handovers', [
            'role' => 'principal',
            'outgoing_user_id' => $this->principal->id,
            'incoming_user_id' => $incoming->id,
        ]);
    }

    public function test_service_signs_handover(): void
    {
        $this->actingAs($this->principal);

        $handover = AdministrativeHandover::create([
            'role' => 'censor',
            'handover_date' => '2026-10-15',
            'outgoing_name' => 'المُسلِّم',
            'incoming_name' => 'المُتسلِّم',
            'outgoing_signed' => false,
            'incoming_signed' => false,
            'director_signed' => false,
            'recorded_by' => $this->principal->id,
        ]);

        $service = app(AdministrativeArchiveService::class);
        $updated = $service->signHandover($handover, 'outgoing');

        $this->assertTrue($updated->outgoing_signed);
        $this->assertFalse($updated->incoming_signed);
        $this->assertFalse($updated->isFullySigned());
    }

    // ─── 5. Mail Service ─────────────────────────────────────────────────────

    public function test_service_generates_sequential_mail_numbers(): void
    {
        $this->actingAs($this->principal);
        $service = app(AdministrativeArchiveService::class);

        $first = $service->generateMailNumber(OfficialMailType::Incoming, 2026);
        $second = $service->generateMailNumber(OfficialMailType::Outgoing, 2026);

        $this->assertStringStartsWith('ARR/2026/', $first);
        $this->assertStringStartsWith('DEP/2026/', $second);
    }

    public function test_service_registers_incoming_mail(): void
    {
        $this->actingAs($this->principal);
        $service = app(AdministrativeArchiveService::class);

        $mail = $service->registerMail([
            'mail_type' => 'incoming',
            'mail_date' => '2026-10-10',
            'sender_name' => 'مديرية التربية',
            'subject' => 'تعليمة رقم 15',
            'confidentiality' => 'normal',
        ]);

        $this->assertInstanceOf(OfficialMail::class, $mail);
        $this->assertStringStartsWith('ARR/', $mail->mail_number);
        $this->assertDatabaseHas('official_mails', [
            'mail_type' => 'incoming',
            'sender_name' => 'مديرية التربية',
        ]);
    }

    public function test_service_updates_mail_status(): void
    {
        $this->actingAs($this->principal);
        $service = app(AdministrativeArchiveService::class);

        $mail = OfficialMail::create([
            'mail_number' => 'ARR/2026/999',
            'mail_type' => 'incoming',
            'mail_date' => '2026-10-01',
            'subject' => 'اختبار الحالة',
            'confidentiality' => 'normal',
            'status' => 'pending',
            'recorded_by' => $this->principal->id,
        ]);

        $updated = $service->updateMailStatus($mail, 'processed');

        $this->assertSame(MailStatus::Processed, $updated->status);
    }

    public function test_mail_overdue_detection(): void
    {
        $this->actingAs($this->principal);

        $overdueMail = OfficialMail::create([
            'mail_number' => 'ARR/2026/888',
            'mail_type' => 'incoming',
            'mail_date' => '2026-09-01',
            'subject' => 'بريد منتهي الأجل',
            'confidentiality' => 'normal',
            'status' => 'pending',
            'response_deadline' => '2026-09-15',  // in the past
            'recorded_by' => $this->principal->id,
        ]);

        $this->assertTrue($overdueMail->isOverdue());
    }

    public function test_mail_assemble_ledger_data(): void
    {
        $this->actingAs($this->principal);

        OfficialMail::create([
            'mail_number' => 'ARR/2026/777',
            'mail_type' => 'incoming',
            'mail_date' => '2026-10-20',
            'subject' => 'وثيقة اختبار',
            'confidentiality' => 'urgent',
            'status' => 'pending',
            'recorded_by' => $this->principal->id,
        ]);

        $service = app(AdministrativeArchiveService::class);
        $data = $service->assembleMailLedgerData('incoming', 2026);

        $this->assertArrayHasKey('mails', $data);
        $this->assertArrayHasKey('mail_type', $data);
        $this->assertArrayHasKey('school', $data);
        $this->assertInstanceOf(OfficialMailType::class, $data['mail_type']);
        $this->assertGreaterThan(0, $data['mails']->count());
    }

    // ─── 6. Print Routes Access Control ─────────────────────────────────────

    public function test_installation_print_requires_auth(): void
    {
        $inst = StaffInstallation::create([
            'user_id' => $this->teacher->id,
            'installation_date' => '2026-09-01',
            'role_label' => 'أستاذ',
            'recorded_by' => $this->principal->id,
        ]);

        $response = $this->get(route('print.admin-archive.installation-sheet', [
            'locale' => 'ar',
            'installation' => $inst->id,
        ]));

        $response->assertRedirect();
    }

    public function test_installation_print_accessible_to_principal(): void
    {
        $this->actingAs($this->principal);

        $inst = StaffInstallation::create([
            'user_id' => $this->teacher->id,
            'installation_date' => '2026-09-01',
            'role_label' => 'أستاذ',
            'recorded_by' => $this->principal->id,
        ]);

        $response = $this->get(route('print.admin-archive.installation-sheet', [
            'locale' => 'ar',
            'installation' => $inst->id,
        ]));

        $response->assertOk();
    }

    public function test_handover_print_accessible_to_principal(): void
    {
        $this->actingAs($this->principal);

        $handover = AdministrativeHandover::create([
            'role' => 'censor',
            'handover_date' => '2026-09-10',
            'outgoing_name' => 'السابق',
            'incoming_name' => 'اللاحق',
            'recorded_by' => $this->principal->id,
        ]);

        $response = $this->get(route('print.admin-archive.handover-sheet', [
            'locale' => 'ar',
            'handover' => $handover->id,
        ]));

        $response->assertOk();
    }

    public function test_mail_ledger_print_accessible_to_principal(): void
    {
        $this->actingAs($this->principal);

        $response = $this->get(route('print.admin-archive.mail-ledger', [
            'locale' => 'ar',
            'type' => 'incoming',
            'year' => 2026,
        ]));

        $response->assertOk();
    }

    public function test_mail_ledger_print_forbidden_to_teacher(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('print.admin-archive.mail-ledger', [
            'locale' => 'ar',
            'type' => 'incoming',
            'year' => 2026,
        ]));

        $response->assertForbidden();
    }

    public function test_administrative_archive_hub_filament_page_accessible_to_principal(): void
    {
        $this->actingAs($this->principal);

        $response = $this->get(AdministrativeArchiveHub::getUrl());
        $response->assertOk();
    }

    public function test_administrative_archive_hub_filament_page_forbidden_to_teacher(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(AdministrativeArchiveHub::getUrl());
        $response->assertForbidden();
    }

    // ─── 7. Health Check ──────────────────────────────────────────────────────

    public function test_health_check_includes_archive_tables(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0)
            ->expectsOutputToContain('[PASS] schema: All required tables present');
    }
}
