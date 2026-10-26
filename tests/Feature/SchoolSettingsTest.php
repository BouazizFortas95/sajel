<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageSchoolSettings;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\SchoolBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_school_identity(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin)
            ->get('/en/auth/manage-school-settings')
            ->assertOk()
            ->assertSee('School Identity');

        $this->actingAs($admin);

        Livewire::test(ManageSchoolSettings::class)
            ->set('data.school_name', 'متوسطة الأمير عبد القادر')
            ->set('data.directorate_name', 'مديرية التربية لولاية الجزائر')
            ->set('data.municipality_name', 'باب الوادي')
            ->set('data.academic_year', '2026-2027')
            ->set('data.principal_name', 'أ. محمد بن يوسف')
            ->set('data.counselor_name', 'أ. علي بن سالم')
            ->set('data.school_code', '1600123')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('school_settings', [
            'school_name' => 'متوسطة الأمير عبد القادر',
            'directorate_name' => 'مديرية التربية لولاية الجزائر',
            'municipality_name' => 'باب الوادي',
            'principal_name' => 'أ. محمد بن يوسف',
            'counselor_name' => 'أ. علي بن سالم',
            'school_code' => '1600123',
        ]);

        $current = SchoolSetting::current();
        $this->assertSame('متوسطة الأمير عبد القادر', $current->school_name);
        $this->assertSame('مديرية التربية لولاية الجزائر', $current->directorate_name);
    }

    public function test_teacher_is_forbidden_from_school_settings(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($teacher)
            ->get('/en/auth/manage-school-settings')
            ->assertForbidden();
    }

    public function test_school_identity_renders_on_register_sheets(): void
    {
        $this->seedIdentity();

        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $classroom = Classroom::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $admin->id,
            'classroom_id' => $classroom->id,
            'group_id' => null,
        ]);
        $log = DailyLog::factory()->completed()->create(['schedule_id' => $schedule->id]);

        $this->actingAs($admin)
            ->get(route('print.register', ['ids' => [$log->id]]))
            ->assertOk()
            ->assertSee('متوسطة الأمير عبد القادر')
            ->assertSee('مديرية التربية لولاية الجزائر')
            ->assertSee('أ. محمد بن يوسف');
    }

    public function test_school_identity_renders_on_official_reports(): void
    {
        $this->seedIdentity();

        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $classroom = Classroom::factory()->create();

        $this->actingAs($admin)
            ->get(route('print.class-council', ['locale' => 'ar', 'classroom' => $classroom]))
            ->assertOk()
            ->assertSee('متوسطة الأمير عبد القادر')
            ->assertSee('مديرية التربية لولاية الجزائر')
            ->assertSee('أ. محمد بن يوسف');

        $this->actingAs($admin)
            ->get('/en/auth/official-reports')
            ->assertOk()
            ->assertSee('متوسطة الأمير عبد القادر')
            ->assertSee('مديرية التربية لولاية الجزائر');
    }

    private function seedIdentity(): SchoolSetting
    {
        return SchoolSetting::query()->create([
            'school_name' => 'متوسطة الأمير عبد القادر',
            'directorate_name' => 'مديرية التربية لولاية الجزائر',
            'municipality_name' => 'باب الوادي',
            'academic_year' => '2026-2027',
            'principal_name' => 'أ. محمد بن يوسف',
            'counselor_name' => 'أ. علي بن سالم',
            'school_code' => '1600123',
        ]);
    }

    public function test_admin_backup_download_excludes_password_hashes(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $this->actingAs($admin);

        $response = $this->get('/en/auth/manage-school-settings');
        $response->assertOk();

        $backup = app(SchoolBackupService::class)->snapshot();
        $this->assertArrayHasKey('metadata', $backup);
        $this->assertArrayHasKey('institutional_config', $backup);
        $this->assertArrayHasKey('table_row_counts', $backup['metadata']);

        $json = json_encode($backup);
        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('remember_token', $json);
        $this->assertStringNotContainsString('session', $json);
    }
}
