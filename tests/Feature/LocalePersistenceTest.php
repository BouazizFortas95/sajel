<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LocalePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_a_localized_panel_route_persists_the_locale(): void
    {
        $this->get('/ar/auth/login')
            ->assertOk()
            ->assertSessionHas('locale', 'ar');
    }

    public function test_switching_language_updates_the_persisted_locale(): void
    {
        $this->get('/ar/auth/login')->assertSessionHas('locale', 'ar');
        $this->get('/en/auth/login')->assertSessionHas('locale', 'en');
        $this->get('/fr/auth/login')->assertSessionHas('locale', 'fr');
    }

    public function test_the_locale_survives_the_livewire_update_request_used_after_saving(): void
    {
        $this->get('/ar/auth/login')->assertOk();

        // Production boots a fresh app for every request; reset the state the
        // previous request left behind so the assertion proves the middleware
        // (not test leakage) restores the locale.
        app()->setLocale('en');
        URL::defaults(['locale' => 'en']);

        // Filament saves forms through this request. It is not wrapped in the
        // localized panel URL, so it previously fell back to the app default
        // (English) — including the redirect URL it returned after a save.
        // The endpoint rejects the empty payload, but the web middleware has
        // already run by then, which is what this test exercises.
        $this->post('/livewire/update');

        $this->assertSame('ar', app()->getLocale());
        $this->assertSame('ar', URL::getDefaultParameters()['locale']);
        $this->assertStringStartsWith(
            '/ar/auth',
            route('filament.auth.pages.dashboard', absolute: false),
        );
    }

    public function test_a_non_localized_url_uses_the_persisted_locale(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get('/')
            ->assertOk()
            ->assertSee('lang="fr"', false);

        $this->assertSame('fr', app()->getLocale());
        $this->assertSame('fr', URL::getDefaultParameters()['locale']);
    }

    public function test_an_unsupported_locale_does_not_override_the_persisted_one(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get('/xx')
            ->assertNotFound();

        $this->assertSame('ar', session('locale'));
    }
}
