<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Livewire\DataShowMode;
use App\Models\DailyLog;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DataShowModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_selects_today_log(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'day_of_week' => DayOfWeek::cases()[today()->dayOfWeek],
        ]);
        DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'date' => today(),
            'title' => 'درس اليوم',
        ]);

        Livewire::actingAs($teacher)
            ->test(DataShowMode::class)
            ->assertSuccessful()
            ->call('pickRandomStudent')
            ->assertSuccessful()
            ->assertSet('content', fn ($c) => count($c) > 0);
    }

    public function test_teacher_scoped_log_access(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        Livewire::actingAs($teacher)
            ->test(DataShowMode::class)
            ->assertSuccessful();
    }

    public function test_each_slide_has_clean_plain_text_narration(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create(['user_id' => $teacher->id]);
        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'title' => 'عنوان السجل',
            'content_data' => [
                [
                    'type' => 'middle_school_lesson',
                    'data' => [
                        'lesson_title' => '<strong>درس الجمع</strong>',
                        'competence' => '<p>يجمع &amp; يطرح</p>',
                        'pedagogical_situation' => 'مسألة تطبيقية',
                        'phases' => [
                            [
                                'type' => 'warmup',
                                'description' => '<p>راجع&nbsp;المثال السابق.</p><p>واذكر القاعدة.</p>',
                            ],
                            [
                                'phase_name' => 'التطبيق',
                                'content' => '<div>حلّ التمارين &amp; ناقشها.</div>&lt;em&gt;جيد&lt;/em&gt;',
                            ],
                        ],
                    ],
                ],
            ],
            'homework' => '<p>أنجز التمرين &amp; راجعه.</p>',
            'observations' => '<p>شارك معظم التلاميذ.</p>',
        ]);

        $slides = Livewire::actingAs($teacher)
            ->test(DataShowMode::class, ['logId' => $log->id])
            ->get('content');

        $this->assertCount(4, $slides);
        foreach ($slides as $slide) {
            $this->assertArrayHasKey('narration_text', $slide);
            $this->assertNotSame('', $slide['narration_text']);
            $this->assertSame($slide['narration_text'], trim($slide['narration_text']));
            $this->assertDoesNotMatchRegularExpression('/<[^>]*>/', $slide['narration_text']);
            $this->assertStringNotContainsString('&amp;', $slide['narration_text']);
            $this->assertDoesNotMatchRegularExpression('/\s{2,}/u', $slide['narration_text']);
        }

        $this->assertStringContainsString('درس الجمع', $slides[0]['narration_text']);
        $this->assertStringContainsString('يجمع & يطرح', $slides[0]['narration_text']);
        $this->assertStringContainsString('مسألة تطبيقية', $slides[0]['narration_text']);
        $this->assertStringContainsString('راجع المثال السابق. واذكر القاعدة.', $slides[1]['narration_text']);
        $this->assertStringContainsString('حلّ التمارين & ناقشها. جيد', $slides[2]['narration_text']);
        $this->assertStringContainsString('أنجز التمرين & راجعه.', $slides[3]['narration_text']);
        $this->assertStringContainsString('شارك معظم التلاميذ.', $slides[3]['narration_text']);
    }

    public function test_voice_narration_controls_are_localized_for_each_supported_locale(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $labels = [
            'ar' => ['قراءة الشريحة صوتياً', 'إيقاف القراءة', 'المتصفح الحالي لا يدعم القراءة الصوتية', 'ar-SA'],
            'en' => ['Read slide aloud', 'Stop narration', 'This browser does not support voice narration', 'en-US'],
            'fr' => ['Lire la diapositive à voix haute', 'Arrêter la lecture', 'Ce navigateur ne prend pas en charge la lecture vocale', 'fr-FR'],
        ];

        foreach ($labels as $locale => [$play, $stop, $unsupported, $speechLocale]) {
            app()->setLocale($locale);

            $html = Livewire::actingAs($teacher)
                ->test(DataShowMode::class)
                ->html();

            $this->assertStringContainsString($play, html_entity_decode($html));
            $this->assertStringContainsString($stop, html_entity_decode($html));
            $this->assertStringContainsString($unsupported, html_entity_decode($html));
            $this->assertStringContainsString($speechLocale, $html);
            $this->assertStringContainsString('0.8x', $html);
            $this->assertStringContainsString('1.0x', $html);
            $this->assertStringContainsString('1.2x', $html);
            $this->assertStringContainsString('sajel.datashow.speech_rate', $html);
            $this->assertStringContainsString('localService', $html);
        }
    }

    public function test_filament_page_renders_data_show_as_a_separate_livewire_component(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $page = Livewire::actingAs($teacher)
            ->test(\App\Filament\Pages\DataShowMode::class)
            ->assertSeeLivewire(DataShowMode::class);

        $html = $page->html();
        $this->assertStringContainsString('class="contents"', $html);
        $this->assertSame(2, substr_count($html, 'wire:id='));
    }
}
