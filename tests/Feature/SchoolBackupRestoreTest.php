<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\EducationPhase;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\SchoolBackupService;
use App\Services\SchoolRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchoolBackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function full_snapshot_exports_all_academic_tables_and_metadata(): void
    {
        $backupService = app(SchoolBackupService::class);
        $snapshot = $backupService->fullSnapshot();

        $this->assertArrayHasKey('metadata', $snapshot);
        $this->assertArrayHasKey('institutional_config', $snapshot);
        $this->assertArrayHasKey('tables', $snapshot);
        $this->assertArrayHasKey('table_row_counts', $snapshot['metadata']);

        foreach ($backupService->getTables() as $table) {
            $this->assertArrayHasKey($table, $snapshot['tables'], "Table {$table} should be present in full snapshot tables.");
        }

        $json = json_encode($snapshot);
        $this->assertStringNotContainsString('password_hash', $json);
        $this->assertStringNotContainsString('remember_token', $json);
    }

    #[Test]
    public function inspect_validates_backup_payload_and_reports_accurate_counts(): void
    {
        $restoreService = app(SchoolRestoreService::class);

        // Invalid payload
        $invalidResult = $restoreService->inspect('invalid-json');
        $this->assertFalse($invalidResult['valid']);
        $this->assertNotEmpty($invalidResult['errors']);

        // Empty structure
        $emptyResult = $restoreService->inspect([]);
        $this->assertFalse($emptyResult['valid']);

        // Valid payload
        $validPayload = [
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'institutional_config' => [
                'school_name' => 'متوسطة الشهيد العربي بن مهيدي',
                'academic_year' => '2026-2027',
                'directorate_name' => 'مديرية التربية لولاية الوادي',
            ],
            'tables' => [
                'subjects' => [
                    ['id' => 1, 'name' => 'الرياضيات', 'color' => 'blue'],
                    ['id' => 2, 'name' => 'اللغة العربية', 'color' => 'emerald'],
                ],
            ],
        ];

        $report = $restoreService->inspect($validPayload);
        $this->assertTrue($report['valid']);
        $this->assertSame('متوسطة الشهيد العربي بن مهيدي', $report['school_name']);
        $this->assertSame('2026-2027', $report['academic_year']);
        $this->assertTrue($report['has_tables']);
        $this->assertSame(2, $report['table_counts']['subjects']);
        $this->assertSame(2, $report['total_rows']);
    }

    #[Test]
    public function dry_run_simulates_restore_without_altering_database(): void
    {
        $phase = EducationPhase::factory()->create(['name' => 'التعليم المتوسط', 'phase_abbreviation' => 'م']);
        $initialPhasesCount = EducationPhase::count();

        $restoreService = app(SchoolRestoreService::class);
        $payload = [
            'metadata' => ['generated_at' => now()->toIso8601String()],
            'institutional_config' => [
                'school_name' => 'متوسطة جديدة تجريبية',
                'academic_year' => '2027-2028',
            ],
            'tables' => [
                'education_phases' => [
                    ['id' => 999, 'name' => 'طور وهمي', 'phase_abbreviation' => 'و', 'created_at' => now(), 'updated_at' => now()],
                ],
            ],
        ];

        $result = $restoreService->restore($payload, dryRun: true);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['dry_run']);
        $this->assertDatabaseMissing('education_phases', ['id' => 999]);
        $this->assertSame($initialPhasesCount, EducationPhase::count());
    }

    #[Test]
    public function restore_repopulates_institutional_config_and_table_records(): void
    {
        $restoreService = app(SchoolRestoreService::class);

        $payload = [
            'metadata' => ['generated_at' => now()->toIso8601String()],
            'institutional_config' => [
                'school_name' => 'متوسطة الأمير عبد القادر المسترجعة',
                'directorate_name' => 'مديرية التربية لولاية الجزائر',
                'municipality_name' => 'باب الوادي',
                'academic_year' => '2026-2027',
                'principal_name' => 'أ. عبد القادر',
                'counselor_name' => 'أ. جمال',
                'school_code' => '1600999',
            ],
            'tables' => [
                'subjects' => [
                    ['id' => 500, 'name' => 'التاريخ والجغرافيا', 'color' => 'amber', 'created_at' => now(), 'updated_at' => now()],
                ],
            ],
        ];

        $result = $restoreService->restore($payload, dryRun: false, wipeExisting: false);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['dry_run']);

        $current = SchoolSetting::current();
        $this->assertSame('متوسطة الأمير عبد القادر المسترجعة', $current->school_name);
        $this->assertSame('1600999', $current->school_code);

        $this->assertDatabaseHas('subjects', [
            'id' => 500,
            'name' => 'التاريخ والجغرافيا',
        ]);
    }

    #[Test]
    public function restore_with_wipe_cleans_and_replaces_dataset(): void
    {
        $restoreService = app(SchoolRestoreService::class);

        // Pre-create some data
        Subject::factory()->create(['name' => 'مادة قديمة سيتم مسحها']);
        $this->assertDatabaseHas('subjects', ['name' => 'مادة قديمة سيتم مسحها']);

        $payload = [
            'metadata' => ['generated_at' => now()->toIso8601String()],
            'institutional_config' => [
                'school_name' => 'متوسطة مسترجعة نظيفة',
                'academic_year' => '2026-2027',
            ],
            'tables' => [
                'subjects' => [
                    ['id' => 700, 'name' => 'المعلوماتية الحديثة', 'color' => 'cyan', 'created_at' => now(), 'updated_at' => now()],
                ],
            ],
        ];

        $result = $restoreService->restore($payload, dryRun: false, wipeExisting: true);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('subjects', ['name' => 'مادة قديمة سيتم مسحها']);
        $this->assertDatabaseHas('subjects', ['name' => 'المعلوماتية الحديثة']);
    }

    #[Test]
    public function restore_artisan_command_supports_dry_run_and_force(): void
    {
        $tempFile = storage_path('temp_test_backup.json');
        $payload = [
            'metadata' => ['generated_at' => now()->toIso8601String()],
            'institutional_config' => [
                'school_name' => 'متوسطة القيادة والأوامر',
                'academic_year' => '2026-2027',
            ],
            'tables' => [
                'subjects' => [
                    ['id' => 888, 'name' => 'التربية المدنية', 'color' => 'emerald', 'created_at' => now(), 'updated_at' => now()],
                ],
            ],
        ];

        File::put($tempFile, json_encode($payload));

        try {
            // Dry run
            $this->artisan('sajel:restore', [
                'file' => $tempFile,
                '--dry-run' => true,
            ])
                ->expectsOutputToContain('DRY-RUN')
                ->assertSuccessful();

            $this->assertDatabaseMissing('subjects', ['id' => 888]);

            // Live restore with force
            $this->artisan('sajel:restore', [
                'file' => $tempFile,
                '--force' => true,
            ])
                ->expectsOutputToContain('LIVE RESTORE')
                ->assertSuccessful();

            $this->assertDatabaseHas('subjects', ['id' => 888]);
        } finally {
            if (File::exists($tempFile)) {
                File::delete($tempFile);
            }
        }
    }

    #[Test]
    public function manage_school_settings_header_actions_accessible_to_admin_and_denied_to_teacher(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($admin)
            ->get('/en/auth/manage-school-settings')
            ->assertOk()
            ->assertSee('Download a school backup (JSON)')
            ->assertSee('Restore school backup (JSON)');

        $this->actingAs($teacher)
            ->get('/en/auth/manage-school-settings')
            ->assertForbidden();
    }
}
