<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\DeveloperSettingsHub;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DeveloperSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_settings_table_is_present_and_has_default_singleton(): void
    {
        $this->assertTrue(Schema::hasTable('platform_settings'));

        $setting = PlatformSetting::current();

        $this->assertNotNull($setting);
        $this->assertStringContainsString('سَجِّل', $setting->platform_name);
        $this->assertFalse($setting->is_maintenance_mode);
        $this->assertTrue($setting->enable_ai_assistant);
        $this->assertEquals('v3.0.0-LTS', $setting->version_tag);
    }

    public function test_developer_and_principal_can_access_developer_settings_hub(): void
    {
        $developer = User::factory()->create(['role' => Role::Developer]);
        $this->actingAs($developer);

        $this->assertTrue(DeveloperSettingsHub::canAccess());
        $this->assertTrue(DeveloperSettingsHub::shouldRegisterNavigation());

        $principal = User::factory()->create(['role' => Role::Principal]);
        $this->actingAs($principal);

        $this->assertTrue(DeveloperSettingsHub::canAccess());
        $this->assertTrue(DeveloperSettingsHub::shouldRegisterNavigation());

        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin);

        $this->assertTrue(DeveloperSettingsHub::canAccess());
        $this->assertTrue(DeveloperSettingsHub::shouldRegisterNavigation());
    }

    public function test_teacher_counselor_and_censor_cannot_access_developer_settings_hub(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->actingAs($teacher);
        $this->assertFalse(DeveloperSettingsHub::canAccess());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());

        $counselor = User::factory()->create(['role' => Role::Counselor]);
        $this->actingAs($counselor);
        $this->assertFalse(DeveloperSettingsHub::canAccess());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());

        $censor = User::factory()->create(['role' => Role::Censor]);
        $this->actingAs($censor);
        $this->assertFalse(DeveloperSettingsHub::canAccess());
        $this->assertFalse(DeveloperSettingsHub::shouldRegisterNavigation());
    }

    public function test_platform_settings_service_can_update_branding_and_identity(): void
    {
        $service = app(PlatformSettingsService::class);

        $updated = $service->updateSettings([
            'platform_name' => 'منظومة سَجِّل الذكية 2027',
            'platform_slogan' => 'المنصة الرقمية الرائدة في الجزائر',
            'developer_name' => 'فريق سَجِّل للبرمجيات المتقدمة',
            'developer_website' => 'https://innovate.sajel.dz',
            'version_tag' => 'v3.5.0-Enterprise',
            'logo_path' => 'platform/test_logo.png',
            'dark_logo_path' => 'platform/test_dark_logo.png',
            'favicon_path' => 'platform/test_favicon.ico',
        ]);

        $this->assertEquals('منظومة سَجِّل الذكية 2027', $updated->platform_name);
        $this->assertEquals('v3.5.0-Enterprise', $updated->version_tag);

        $current = PlatformSetting::current();
        $this->assertEquals('منظومة سَجِّل الذكية 2027', $current->platform_name);
        $this->assertStringContainsString('platform/test_logo.png', (string) $current->getLogoUrl());
        $this->assertStringContainsString('platform/test_dark_logo.png', (string) $current->getDarkLogoUrl());
        $this->assertStringContainsString('platform/test_favicon.ico', (string) $current->getFaviconUrl());
    }

    public function test_platform_settings_service_can_toggle_maintenance_mode(): void
    {
        $service = app(PlatformSettingsService::class);

        $this->assertFalse(PlatformSetting::current()->isMaintenanceActive());

        $service->toggleMaintenance(true, 'نقوم حالياً بترقية خوادم قاعدة البيانات.');

        $setting = PlatformSetting::current();
        $this->assertTrue($setting->isMaintenanceActive());
        $this->assertEquals('نقوم حالياً بترقية خوادم قاعدة البيانات.', $setting->maintenance_message);

        $service->toggleMaintenance(false);
        $this->assertFalse(PlatformSetting::current()->isMaintenanceActive());
    }

    public function test_platform_settings_service_can_toggle_announcement_banner(): void
    {
        $service = app(PlatformSettingsService::class);

        $service->updateSettings([
            'announcement_active' => true,
            'system_announcement' => 'يرجى من جميع الأساتذة استكمال صب النقاط قبل نهاية الأسبوع.',
            'announcement_type' => 'warning',
        ]);

        $current = PlatformSetting::current();
        $this->assertTrue($current->isAnnouncementVisible());
        $this->assertEquals('warning', $current->announcement_type);
    }

    public function test_platform_settings_service_provides_system_telemetry(): void
    {
        $service = app(PlatformSettingsService::class);
        $telemetry = $service->getSystemTelemetry();

        $this->assertIsArray($telemetry);
        $this->assertArrayHasKey('php_version', $telemetry);
        $this->assertArrayHasKey('laravel_version', $telemetry);
        $this->assertArrayHasKey('db_tables_count', $telemetry);
        $this->assertGreaterThanOrEqual(110, $telemetry['db_tables_count']);
        $this->assertArrayHasKey('memory_usage_mb', $telemetry);
    }

    public function test_platform_settings_service_can_clear_caches(): void
    {
        $service = app(PlatformSettingsService::class);
        $results = $service->clearAllCaches();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('cache', $results);
        $this->assertArrayHasKey('views', $results);
    }

    public function test_filament_page_developer_settings_hub_mounts_and_saves(): void
    {
        $developer = User::factory()->create(['role' => Role::Developer]);
        $this->actingAs($developer);

        Livewire::test(DeveloperSettingsHub::class)
            ->assertSuccessful()
            ->set('data.platform_name', 'سَجِّل — النسخة المحدثة')
            ->set('data.developer_name', 'المهندس المشرف')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('سَجِّل — النسخة المحدثة', PlatformSetting::current()->platform_name);
        $this->assertEquals('المهندس المشرف', PlatformSetting::current()->developer_name);
    }
}
