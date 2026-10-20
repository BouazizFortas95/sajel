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
        $response->assertSee('/en/auth/login', false);
        $response->assertSee('/en/auth/register', false);
        $response->assertSee('Dashboard', false);
    }

    public function test_authenticated_user_sees_dashboard_link_on_welcome_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Open dashboard');
    }

    public function test_login_and_register_pages_are_accessible(): void
    {
        $this->get('/en/auth/login')->assertOk();
        $this->get('/en/auth/register')->assertOk();
    }

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get('/en/auth')->assertRedirect();
    }

    public function test_dashboard_shows_stats_and_quick_links(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/en/auth');

        $response->assertOk();
        $response->assertSee("Today's logs");
        $response->assertSee('Class register');
        $response->assertSee('Weekly lessons');
        $response->assertSee('Education levels');
        $response->assertSee('filament-language-switcher');
    }

    public function test_french_home_and_dashboard_content_is_translated(): void
    {
        $this->get('/fr')
            ->assertOk()
            ->assertSee('lang="fr"', false)
            ->assertSee('Tableau de bord');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/fr/auth')
            ->assertOk()
            ->assertSee('Journaux du jour')
            ->assertSee('Registre de classe')
            ->assertSee('Niveaux');
    }

    public function test_arabic_homepage_uses_right_to_left_and_dropdown_switcher(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('welcome-language-switcher');
    }
}
