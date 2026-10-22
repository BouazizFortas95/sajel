<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\QuickLinks;
use App\Filament\Widgets\UnloggedSessionsWidget;
use App\Livewire\DashboardStats;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_renders_without_missing_utility_classes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->createTodaysLogFor($user);

        // Check the widgets and stat cards we author, rather than Filament's
        // own panel layout (which contains Alpine expressions, not classes).
        $html = Livewire::test(QuickLinks::class)
            ->assertOk()
            ->html();

        $html .= Livewire::test(DashboardStats::class)
            ->assertOk()
            ->html();

        $unknown = $this->unknownCssClasses($html);

        $this->assertSame([], $unknown);
    }

    public function test_the_dashboard_supports_dark_mode_and_registers_its_stylesheet(): void
    {
        $user = User::factory()->create();
        $this->createTodaysLogFor($user);

        $html = $this->actingAs($user)
            ->get('/en/auth')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("localStorage.getItem('theme')", $html);
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $themeAsset = $manifest['resources/css/filament.css']['file'] ?? null;

        $this->assertIsString($themeAsset);
        $this->assertStringContainsString($themeAsset, $html);
        $this->assertStringContainsString('bg-amber-500', $html);
        $this->assertStringContainsString('text-gray-950', $html);
        $this->assertStringContainsString('text-gray-500', $html);
        $this->assertStringContainsString('dark .fi-body', file_get_contents(resource_path('css/filament.css')));
    }

    public function test_arabic_panel_route_renders_filament_in_rtl_and_switches_locales_in_the_existing_topbar(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get('/ar/auth')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('/fr/auth', $html);
        $this->assertStringContainsString('/en/auth', $html);
        $this->assertStringContainsString('id="filament-language-switcher"', $html);
        $this->assertStringContainsString('fi-topbar', $html);
        $this->assertStringContainsString('fi-sidebar', $html);
        $this->assertStringContainsString('$store.sidebar.close()', $html);
    }

    public function test_the_quick_links_widget_uses_sajel_brand_colours(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $html = Livewire::test(QuickLinks::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('bg-amber-50', $html);
        $this->assertStringContainsString('text-amber-600', $html);
        $this->assertStringContainsString('rounded-2xl', $html);
        $this->assertStringContainsString('hover:shadow-lg', $html);
        $this->assertStringContainsString('grid-cols-1', $html);
        $this->assertStringContainsString('sm:grid-cols-2', $html);
        $this->assertStringContainsString('lg:grid-cols-3', $html);
        $this->assertStringContainsString('xl:grid-cols-4', $html);
        $this->assertStringContainsString('w-full', $html);
        $this->assertStringContainsString('flex-col', $html);
        $this->assertStringContainsString('break-words', $html);
        $this->assertStringNotContainsString('line-clamp-', $html);
        $this->assertStringNotContainsString('truncate', $html);

        // The broken hand-written variable usage must be gone.
        $this->assertStringNotContainsString('var(--white', $html);
        $this->assertStringNotContainsString('sajel-quick-link', $html);
    }

    public function test_quick_links_widget_spans_the_dashboard_grid(): void
    {
        $this->assertSame('full', (new QuickLinks)->getColumnSpan());
        $this->assertSame(1, (new Dashboard)->getColumns());
    }

    public function test_dashboard_shows_only_navigation_cards_without_the_account_widget(): void
    {
        $this->assertSame(
            [UnloggedSessionsWidget::class, QuickLinks::class],
            (new Dashboard)->getWidgets(),
        );
    }

    public function test_the_dashboard_stats_component_renders_the_smart_log_tabs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->createTodaysLogFor($user);

        $html = Livewire::test(DashboardStats::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('Smart log', $html);
        $this->assertStringContainsString('Preparation', $html);
        $this->assertStringContainsString('Completion', $html);
        $this->assertStringContainsString('bg-amber-500', $html);
        $this->assertStringContainsString('dark:bg-gray-900', $html);
    }

    /**
     * Return the class tokens in the markup that have no rule in the CSS
     * bundles the Filament panel loads.
     *
     * @return list<string>
     */
    private function unknownCssClasses(string $html): array
    {
        $css = $this->loadedPanelCss();

        preg_match_all('/\sclass="([^"]*)"/', $html, $matches);

        $unknown = [];

        foreach ($matches[1] as $attribute) {
            $attribute = html_entity_decode($attribute, ENT_QUOTES);

            foreach (preg_split('/\s+/', $attribute) as $class) {
                $class = trim($class);

                // Filament's own `fi-*` classes are behavioural hooks, not styles.
                if ($class === '' || str_starts_with($class, 'fi-')) {
                    continue;
                }

                if (! $this->cssHasClass($css, $class)) {
                    $unknown[] = $class;
                }
            }
        }

        return array_values(array_unique($unknown));
    }

    /**
     * Read the CSS bundles that the Filament panel actually loads.
     */
    private function loadedPanelCss(): string
    {
        $files = [
            'public/css/filament/filament/app.css',
            'public/css/filament/support/support.css',
            'public/css/filament/forms/forms.css',
        ];

        $css = '';

        foreach ($files as $file) {
            $path = base_path($file);

            if (is_file($path)) {
                $css .= file_get_contents($path)."\n";
            }
        }

        $manifestPath = public_path('build/manifest.json');

        if (is_file($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $themeAsset = $manifest['resources/css/filament.css']['file'] ?? null;

            if (is_string($themeAsset) && is_file(public_path('build/'.$themeAsset))) {
                $css .= file_get_contents(public_path('build/'.$themeAsset))."\n";
            }
        }

        return $css;
    }

    private function createTodaysLogFor(User $user): DailyLog
    {
        $schedule = Schedule::factory()->for($user)->create();

        return DailyLog::factory()
            ->for($schedule)
            ->create(['date' => today()]);
    }

    /**
     * Determine whether the stylesheets contain a rule for the given class,
     * escaping the class name the way Tailwind escapes CSS selectors.
     */
    private function cssHasClass(string $css, string $class): bool
    {
        $escaped = preg_replace(
            '/([!"#$%&\'()*+,.\/:;<=>?@\[\\\\\]^`{|}~])/',
            '\\\\$1',
            $class,
        );

        return str_contains($css, '.'.$escaped);
    }
}
