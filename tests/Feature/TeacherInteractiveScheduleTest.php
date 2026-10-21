<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Filament\Resources\DailyLogResource;
use App\Livewire\TeacherInteractiveSchedule;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherInteractiveScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(): User
    {
        return User::factory()->create();
    }

    /**
     * Build a schedule owned by the given teacher on the given day.
     */
    private function makeSchedule(User $teacher, DayOfWeek $day, ?Subject $subject = null): Schedule
    {
        $classroom = Classroom::factory()->create();

        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => ($subject ?? Subject::factory()->create())->id,
            'classroom_id' => $classroom->id,
            'group_id' => null,
            'day_of_week' => $day->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
    }

    public function test_component_renders(): void
    {
        $user = $this->makeTeacher();

        $subject = Subject::factory()->create(['name' => 'الرياضيات', 'color' => '#4f46e5']);

        Schedule::factory()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->assertOk();
    }

    public function test_it_renders_native_filament_components(): void
    {
        $user = $this->makeTeacher();

        $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        $html = Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value)
            ->assertOk()
            ->html();

        // Native Filament markup is generated, not hand-rolled.
        $this->assertStringContainsString('fi-section', $html);
        $this->assertStringContainsString('fi-section-header', $html);
        $this->assertStringContainsString('fi-tabs', $html);
        $this->assertStringContainsString('fi-tabs-item', $html);
        $this->assertStringContainsString('fi-badge', $html);
        $this->assertStringContainsString('fi-input-wrp', $html);
        $this->assertStringContainsString('fi-btn', $html);
    }

    public function test_it_renders_subject_and_status_colours_through_filament_variables(): void
    {
        $user = $this->makeTeacher();

        $subject = Subject::factory()->create([
            'name' => 'الرياضيات',
            'color' => '#4f46e5',
        ]);

        $schedule = $this->makeSchedule($user, DayOfWeek::Monday, $subject);

        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => today(),
            'status' => DailyLogStatus::Completed,
        ]);

        $this->actingAs($user);

        $html = Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value)
            ->assertOk()
            ->html();

        // The subject's hex becomes Filament shade variables, so the badge
        // follows the panel palette in both themes.
        $this->assertStringContainsString('--c-50', $html);
        $this->assertStringContainsString('--c-400', $html);
        $this->assertStringContainsString('--c-600', $html);
        $this->assertStringContainsString('bg-custom-50', $html);
        $this->assertStringContainsString('text-custom-600', $html);
        $this->assertStringContainsString('dark:bg-custom-400/10', $html);
        $this->assertStringContainsString('dark:text-custom-400', $html);

        // The status badge uses Filament's named palette.
        $this->assertStringContainsString('Completed', $html);
    }

    public function test_it_falls_back_to_a_safe_colour_for_an_invalid_subject_colour(): void
    {
        $user = $this->makeTeacher();

        $subject = Subject::factory()->create([
            'name' => 'الرياضيات',
            'color' => 'not-a-colour',
        ]);

        $this->makeSchedule($user, DayOfWeek::Monday, $subject);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value)
            ->assertOk()
            ->assertSee('الرياضيات');
    }

    public function test_it_renders_the_empty_state_when_there_are_no_schedules(): void
    {
        $user = $this->makeTeacher();

        $this->actingAs($user);

        $html = Livewire::test(TeacherInteractiveSchedule::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('fi-ta-empty-state', $html);
        $this->assertStringContainsString('No lessons', $html);
    }

    public function test_schedule_component_translates_its_controls_into_french(): void
    {
        app()->setLocale('fr');

        $user = $this->makeTeacher();
        $this->actingAs($user);

        $html = Livewire::test(TeacherInteractiveSchedule::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('Nombre total de cours', $html);
        $this->assertStringContainsString('Choisir un jour', $html);
        $this->assertStringContainsString('Aucun cours', $html);
    }

    public function test_it_uses_theme_safe_classes_for_dark_mode(): void
    {
        $user = $this->makeTeacher();

        $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        $html = Livewire::test(TeacherInteractiveSchedule::class)
            ->assertOk()
            ->html();

        // Card surfaces must invert with the theme.
        $this->assertStringContainsString('dark:bg-gray-900', $html);
        $this->assertStringContainsString('dark:ring-white/10', $html);

        // Headings and secondary copy keep contrast in both themes.
        $this->assertStringContainsString('text-gray-950', $html);
        $this->assertStringContainsString('dark:text-white', $html);
        $this->assertStringContainsString('text-gray-500', $html);
        $this->assertStringContainsString('dark:text-gray-400', $html);
    }

    public function test_it_does_not_use_utility_classes_missing_from_the_loaded_filament_css(): void
    {
        $user = $this->makeTeacher();

        $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        $html = Livewire::test(TeacherInteractiveSchedule::class)
            ->assertOk()
            ->html();

        $css = $this->loadedPanelCss();

        // Collect every class token used in the rendered markup.
        preg_match_all('/class="([^"]*)"/', $html, $matches);

        $unknown = [];

        foreach ($matches[1] as $attribute) {
            // The rendered HTML is entity-encoded; decode before comparing.
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

        $this->assertSame([], array_values(array_unique($unknown)));
    }

    public function test_it_defaults_to_today(): void
    {
        $user = $this->makeTeacher();

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->assertSet('selectedDay', DayOfWeek::cases()[now()->dayOfWeek]->value);
    }

    public function test_it_switches_the_selected_day(): void
    {
        $user = $this->makeTeacher();

        $this->makeSchedule($user, DayOfWeek::Monday);
        $this->makeSchedule($user, DayOfWeek::Tuesday);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->call('setDay', DayOfWeek::Tuesday->value)
            ->assertSet('selectedDay', DayOfWeek::Tuesday->value);
    }

    public function test_it_filters_schedules_by_search_term(): void
    {
        $user = $this->makeTeacher();

        $math = Subject::factory()->create(['name' => 'الرياضيات']);
        $arabic = Subject::factory()->create(['name' => 'اللغة العربية']);

        $this->makeSchedule($user, DayOfWeek::Monday, $math);
        $this->makeSchedule($user, DayOfWeek::Monday, $arabic);

        $this->actingAs($user);

        $component = Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value)
            ->set('search', 'الرياضيات');

        $this->assertCount(1, $component->instance()->schedules);
        $this->assertSame('الرياضيات', $component->instance()->schedules->first()->subject->name);
    }

    public function test_it_toggles_the_view_mode(): void
    {
        $user = $this->makeTeacher();

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->assertSet('viewMode', 'grid')
            ->set('viewMode', 'list')
            ->assertSet('viewMode', 'list');
    }

    public function test_it_toggles_the_expanded_card(): void
    {
        $user = $this->makeTeacher();

        $schedule = $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->call('toggleCard', $schedule->id)
            ->assertSet('expandedCardId', $schedule->id)
            ->call('toggleCard', $schedule->id)
            ->assertSet('expandedCardId', null);
    }

    public function test_it_marks_a_schedule_as_completed(): void
    {
        $user = $this->makeTeacher();

        $schedule = $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->call('markCompleted', $schedule->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_logs', [
            'schedule_id' => $schedule->id,
            'status' => DailyLogStatus::Completed->value,
        ]);
    }

    public function test_it_cancels_a_schedule(): void
    {
        $user = $this->makeTeacher();

        $schedule = $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->call('cancelLog', $schedule->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_logs', [
            'schedule_id' => $schedule->id,
            'status' => DailyLogStatus::Cancelled->value,
        ]);
    }

    public function test_it_counts_completed_and_cancelled_logs_in_the_stats(): void
    {
        $user = $this->makeTeacher();

        $completed = $this->makeSchedule($user, DayOfWeek::Monday);
        $cancelled = $this->makeSchedule($user, DayOfWeek::Monday);
        $this->makeSchedule($user, DayOfWeek::Monday);

        DailyLog::factory()->create([
            'schedule_id' => $completed->id,
            'date' => DayOfWeek::Monday->dateInCurrentWeek(),
            'status' => DailyLogStatus::Completed,
        ]);

        DailyLog::factory()->create([
            'schedule_id' => $cancelled->id,
            'date' => DayOfWeek::Monday->dateInCurrentWeek(),
            'status' => DailyLogStatus::Cancelled,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value);

        $stats = $component->instance()->scheduleStats;

        $this->assertSame(3, $stats['total']);
        $this->assertSame(1, $stats['planned']);
        $this->assertSame(1, $stats['completed']);
        $this->assertSame(1, $stats['cancelled']);
    }

    public function test_it_only_shows_the_current_teachers_schedules(): void
    {
        $teacher = $this->makeTeacher();
        $other = $this->makeTeacher();

        $this->makeSchedule($teacher, DayOfWeek::Monday);
        $this->makeSchedule($other, DayOfWeek::Monday);

        $this->actingAs($teacher);

        $component = Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value);

        $this->assertCount(1, $component->instance()->schedules);
    }

    public function test_it_opens_the_existing_log_for_a_schedule(): void
    {
        $user = $this->makeTeacher();
        $schedule = $this->makeSchedule($user, DayOfWeek::Monday);

        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => today(),
        ]);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->call('openLog', $schedule->id)
            ->assertRedirect(DailyLogResource::getUrl('edit', [
                'record' => $log,
                'locale' => 'en',
            ]));
    }

    public function test_it_opens_the_prefilled_create_page_when_no_log_exists(): void
    {
        $user = $this->makeTeacher();
        $schedule = $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->call('openLog', $schedule->id)
            ->assertRedirect(DailyLogResource::getUrl('create', [
                'schedule_id' => $schedule->id,
                'date' => today()->toDateString(),
                'locale' => 'en',
            ]));
    }

    public function test_it_opens_the_create_page_for_the_selected_day(): void
    {
        $user = $this->makeTeacher();
        $schedule = $this->makeSchedule($user, DayOfWeek::Monday);

        $this->actingAs($user);

        $monday = DayOfWeek::Monday->dateInCurrentWeek();

        Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value)
            ->call('openLog', $schedule->id)
            ->assertRedirect(DailyLogResource::getUrl('create', [
                'schedule_id' => $schedule->id,
                'date' => $monday->toDateString(),
                'locale' => 'en',
            ]));
    }

    public function test_it_cannot_open_or_modify_another_teachers_schedule(): void
    {
        $teacher = $this->makeTeacher();
        $other = $this->makeTeacher();
        $otherSchedule = $this->makeSchedule($other, DayOfWeek::Monday);

        $this->actingAs($teacher);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->call('openLog', $otherSchedule->id)
            ->assertNoRedirect()
            ->call('markCompleted', $otherSchedule->id)
            ->call('cancelLog', $otherSchedule->id);

        $this->assertDatabaseCount('daily_logs', 0);
    }

    public function test_it_renders_a_group_name_when_present(): void
    {
        $user = $this->makeTeacher();

        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);

        Schedule::factory()->create([
            'user_id' => $user->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'day_of_week' => DayOfWeek::Monday->value,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $this->actingAs($user);

        Livewire::test(TeacherInteractiveSchedule::class)
            ->set('selectedDay', DayOfWeek::Monday->value)
            ->assertOk()
            ->assertSee('المجموعة أ');
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

        return str_contains($css, '.'.$escaped);
    }
}
