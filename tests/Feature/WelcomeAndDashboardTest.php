<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_welcome_page_with_login_and_register_links(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('سجِّل');
        $response->assertSee('/auth/login', false);
        $response->assertSee('/auth/register', false);
        $response->assertSee('لوحة التحكم', false);
    }

    public function test_authenticated_user_sees_dashboard_link_on_welcome_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('ادخل إلى لوحة التحكم');
    }

    public function test_login_and_register_pages_are_accessible(): void
    {
        $this->get('/auth/login')->assertOk();
        $this->get('/auth/register')->assertOk();
    }

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get('/auth')->assertRedirect();
    }

    public function test_dashboard_shows_stats_and_quick_links(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/auth');

        $response->assertOk();
        $response->assertSee('سجلات اليوم');
        $response->assertSee('دفتر النصوص');
        $response->assertSee('الحصص الأسبوعية');
        $response->assertSee('الأساسات التعليمية');
    }
}
