<?php

namespace Tests\Feature;

use App\Filament\Widgets\QuickLinks;
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

        // Check the widgets and stat cards we author, rather than Filament's
        // own panel layout (which contains Alpine expressions, not classes).
        $html = Livewire::test(QuickLinks::class)
            ->assertOk()
            ->html();

        $html .= Livewire::test(\App\Livewire\DashboardStats::class)
            ->assertOk()
            ->html();

        $unknown = $this->unknownCssClasses($html);

        $this->assertSame([], $unknown);
    }

    public function test_the_dashboard_uses_theme_safe_surfaces_and_text(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get('/auth')
            ->assertOk()
            ->getContent();

        // Cards invert with the theme.
        $this->assertStringContainsString('dark:bg-gray-900', $html);
        $this->assertStringContainsString('dark:ring-white/10', $html);

        // Headings and secondary copy keep contrast in both themes.
        $this->assertStringContainsString('text-gray-950', $html);
        $this->assertStringContainsString('dark:text-white', $html);
        $this->assertStringContainsString('text-gray-500', $html);
        $this->assertStringContainsString('dark:text-gray-400', $html);
    }

    public function test_the_quick_links_widget_uses_filament_colour_variables(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $html = Livewire::test(QuickLinks::class)
            ->assertOk()
            ->html();

        // The custom colour utilities resolve through Filament's variables.
        $this->assertStringContainsString('bg-custom-50', $html);
        $this->assertStringContainsString('text-custom-600', $html);
        $this->assertStringContainsString('dark:bg-custom-400/10', $html);
        $this->assertStringContainsString('--c-50', $html);
        $this->assertStringContainsString('--c-400', $html);
        $this->assertStringContainsString('--c-600', $html);

        // The broken hand-written variable usage must be gone.
        $this->assertStringNotContainsString('var(--white', $html);
        $this->assertStringNotContainsString('sajel-quick-link', $html);
    }

    public function test_the_dashboard_stats_component_uses_filament_colour_variables(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $html = Livewire::test(\App\Livewire\DashboardStats::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('bg-custom-50', $html);
        $this->assertStringContainsString('text-custom-600', $html);
        $this->assertStringContainsString('dark:bg-custom-400/10', $html);
        $this->assertStringContainsString('--c-50', $html);

        // Hard-coded Tailwind palette colours are not in the loaded CSS.
        $this->assertStringNotContainsString('bg-amber-100', $html);
        $this->assertStringNotContainsString('text-emerald-600', $html);
        $this->assertStringNotContainsString('text-gray-900', $html);
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

        preg_match_all('/class="([^"]*)"/', $html, $matches);

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

        return $css;
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

        return str_contains($css, '.' . $escaped);
    }
}
