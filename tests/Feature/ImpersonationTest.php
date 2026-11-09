<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\MySchedule;
use App\Filament\Pages\TeacherReceiptsHub;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected User $developer;

    protected User $principal;

    protected User $teacher1;

    protected User $teacher2;

    protected User $adminUser;

    protected Subject $math;

    protected function setUp(): void
    {
        parent::setUp();

        SchoolSetting::updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'متوسطة حي 900 مسكن',
                'academic_year' => '2026-2027',
            ]
        );

        $this->math = Subject::firstOrCreate(
            ['name' => 'الرياضيات'],
            ['color' => '#2563eb']
        );

        $this->developer = User::factory()->create([
            'name' => 'مطور النظام',
            'email' => 'dev.test@sajel.test',
            'role' => Role::Developer->value,
        ]);

        $this->principal = User::factory()->create([
            'name' => 'المدير بوزقاق',
            'email' => 'principal.test@sajel.test',
            'role' => Role::Principal->value,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'المشرف العام',
            'email' => 'admin.test@sajel.test',
            'role' => Role::Admin->value,
        ]);

        $this->teacher1 = User::factory()->create([
            'name' => 'الأستاذ بن عيسى',
            'email' => 'teacher1.test@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->math->id,
        ]);

        $this->teacher2 = User::factory()->create([
            'name' => 'الأستاذة خولة',
            'email' => 'teacher2.test@sajel.test',
            'role' => Role::Teacher->value,
            'primary_subject_id' => $this->math->id,
        ]);
    }

    public function test_developer_can_impersonate_a_teacher(): void
    {
        $response = $this->actingAs($this->developer)
            ->post(route('impersonate.start', [
                'locale' => 'ar',
                'user' => $this->teacher1->id,
            ]));

        $response->assertRedirect(route('filament.auth.pages.dashboard', ['locale' => 'ar']));
        $this->assertEquals($this->teacher1->id, auth()->id());
        $this->assertEquals($this->developer->id, session()->get(ImpersonationService::SESSION_KEY));
    }

    public function test_impersonated_session_can_access_teacher_schedule_and_receipts(): void
    {
        $this->actingAs($this->developer)
            ->post(route('impersonate.start', [
                'locale' => 'ar',
                'user' => $this->teacher1->id,
            ]));

        $this->assertTrue(MySchedule::canAccess());
        $this->assertTrue(TeacherReceiptsHub::canAccess());

        $hubResponse = $this->get(TeacherReceiptsHub::getUrl());
        $hubResponse->assertSuccessful();
        $hubResponse->assertSee('الأستاذ بن عيسى');
    }

    public function test_leaving_impersonation_restores_developer_account(): void
    {
        $this->actingAs($this->developer)
            ->post(route('impersonate.start', [
                'locale' => 'ar',
                'user' => $this->teacher1->id,
            ]));

        $this->assertEquals($this->teacher1->id, auth()->id());

        $leaveResponse = $this->post(route('impersonate.leave', ['locale' => 'ar']));
        $leaveResponse->assertRedirect(route('filament.auth.pages.dashboard', ['locale' => 'ar']));

        $this->assertEquals($this->developer->id, auth()->id());
        $this->assertNull(session()->get(ImpersonationService::SESSION_KEY));
    }

    public function test_teacher_cannot_impersonate_another_user(): void
    {
        $response = $this->actingAs($this->teacher1)
            ->post(route('impersonate.start', [
                'locale' => 'ar',
                'user' => $this->teacher2->id,
            ]));

        $response->assertForbidden();
        $this->assertEquals($this->teacher1->id, auth()->id());
    }

    public function test_developer_cannot_impersonate_non_teacher(): void
    {
        $response = $this->actingAs($this->developer)
            ->post(route('impersonate.start', [
                'locale' => 'ar',
                'user' => $this->adminUser->id,
            ]));

        $response->assertForbidden();
        $this->assertEquals($this->developer->id, auth()->id());
    }

    public function test_impersonation_banner_renders_when_active(): void
    {
        $this->actingAs($this->developer)
            ->post(route('impersonate.start', [
                'locale' => 'ar',
                'user' => $this->teacher1->id,
            ]));

        $dashboardResponse = $this->get(route('filament.auth.pages.dashboard', ['locale' => 'ar']));
        $dashboardResponse->assertSuccessful();
        $dashboardResponse->assertSee('وضع المعاينة الفنية');
        $dashboardResponse->assertSee('الأستاذ بن عيسى');
        $dashboardResponse->assertSee('إنهاء المعاينة والعودة لحسابي');
    }
}

