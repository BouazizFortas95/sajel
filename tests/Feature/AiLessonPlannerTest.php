<?php

namespace Tests\Feature;

use App\Exceptions\AiLessonPlannerException;
use App\Filament\Resources\DailyLogResource\Pages\CreateDailyLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiLessonPlannerService;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AiLessonPlannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('ar');
        config()->set('ai.provider', 'gemini');
        config()->set('ai.gemini.key', 'test-gemini-key');
        config()->set('ai.retry_times', 2);
        config()->set('ai.retry_delay', 0);
    }

    /**
     * A representative provider answer for the structured lesson plan.
     *
     * @return array{competence: string, lesson_phases: list<array{phase_name: string, content: string}>}
     */
    private function plan(): array
    {
        return [
            'competence' => 'يحلّ التلميذ مسائل على الكسور في وضعيات من الحياة اليومية.',
            'lesson_phases' => [
                ['phase_name' => 'وضعية الانطلاق', 'content' => 'يطرح الأستاذ وضعية مشكلة قصيرة.'],
                ['phase_name' => 'بناء التعلمات', 'content' => 'يعمل التلاميذ في أفواج على تمثيل الكسور.'],
                ['phase_name' => 'التقويم', 'content' => 'يحلّ كل تلميذ تمريناً فردياً مع التصحيح الجماعي.'],
            ],
        ];
    }

    private function fakeGemini(string $text): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $text]]]],
                ],
            ]),
        ]);
    }

    public function test_it_returns_a_filament_ready_lesson_structure_block(): void
    {
        $this->fakeGemini(json_encode($this->plan(), JSON_UNESCAPED_UNICODE));

        $block = app(AiLessonPlannerService::class)->generateLessonPlan(
            topic: 'الكسور',
            subject: 'الرياضيات',
            level: 'السنة الأولى متوسطة',
        );

        $this->assertSame('lesson_structure', $block['type']);
        $this->assertSame($this->plan()['competence'], $block['data']['competence']);
        $this->assertCount(3, $block['data']['lesson_phases']);

        $uuids = array_keys($block['data']['lesson_phases']);

        $this->assertCount(3, array_filter($uuids, fn (string $uuid): bool => Str::isUuid($uuid)));

        $phases = array_values($block['data']['lesson_phases']);

        $this->assertSame('وضعية الانطلاق', $phases[0]['phase_name']);
        $this->assertSame('يحلّ كل تلميذ تمريناً فردياً مع التصحيح الجماعي.', $phases[2]['content']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'models/gemini-3.8-flash:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && $request['generationConfig']['responseMimeType'] === 'application/json'
            && str_contains($request['contents'][0]['parts'][0]['text'], 'الكسور')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'الرياضيات'));
    }

    public function test_it_builds_the_prompt_in_the_requested_locale_with_arabic_fallback(): void
    {
        $this->fakeGemini(json_encode($this->plan(), JSON_UNESCAPED_UNICODE));

        $planner = app(AiLessonPlannerService::class);

        $planner->generateLessonPlan('Fractions', 'Mathematics', 'First year', locale: 'en');
        $planner->generateLessonPlan('Fractions', 'Mathématiques', 'Première année', locale: 'fr');
        $planner->generateLessonPlan('الكسور', 'الرياضيات', 'الأولى متوسط', locale: 'de');

        Http::assertSent(fn (Request $request): bool => str_contains($request['contents'][0]['parts'][0]['text'], 'Write a complete lesson plan in clear, age-appropriate English')
            && $request['generationConfig']['responseSchema']['properties']['competence']['description'] === 'Target competence in one sentence.');

        Http::assertSent(fn (Request $request): bool => str_contains($request['contents'][0]['parts'][0]['text'], 'Rédigez un plan de leçon complet en français clair')
            && $request['generationConfig']['responseSchema']['properties']['competence']['description'] === 'Compétence visée en une phrase.');

        Http::assertSent(fn (Request $request): bool => str_contains($request['contents'][0]['parts'][0]['text'], 'حرّر مخطط تحضير كامل بالعربية الفصحى المبسّطة')
            && $request['generationConfig']['responseSchema']['properties']['competence']['description'] === 'الكفاءة المستهدفة في جملة واحدة.');
    }

    public function test_it_parses_markdown_fenced_json_responses(): void
    {
        $this->fakeGemini('```json'."\n".json_encode($this->plan(), JSON_UNESCAPED_UNICODE)."\n".'```');

        $block = app(AiLessonPlannerService::class)->generateLessonPlan('الكسور', 'الرياضيات', 'الأولى متوسط');

        $this->assertSame('lesson_structure', $block['type']);
        $this->assertCount(3, $block['data']['lesson_phases']);
    }

    public function test_it_supports_the_openai_driver(): void
    {
        config()->set('ai.provider', 'openai');
        config()->set('ai.openai.key', 'test-openai-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($this->plan(), JSON_UNESCAPED_UNICODE)]],
                ],
            ]),
        ]);

        $block = app(AiLessonPlannerService::class)->generateLessonPlan(
            'الكسور',
            'الرياضيات',
            'الأولى متوسط',
            locale: 'fr',
        );

        $this->assertCount(3, $block['data']['lesson_phases']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'chat/completions')
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && str_contains($request['messages'][0]['content'], 'expert pédagogique algérien du cycle moyen')
            && $request['response_format'] === ['type' => 'json_object']);
    }

    public function test_it_reports_provider_errors_with_the_provider_message(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'API key not valid.'],
            ], 400),
        ]);

        $this->expectException(AiLessonPlannerException::class);
        $this->expectExceptionMessage('API key not valid.');

        app(AiLessonPlannerService::class)->generateLessonPlan('الكسور', 'الرياضيات', 'الأولى متوسط');
    }

    public function test_it_retries_a_transient_provider_error_before_succeeding(): void
    {
        config()->set('ai.retry_times', 2);

        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push(['error' => ['message' => 'Service temporarily unavailable.']], 503)
            ->push([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($this->plan(), JSON_UNESCAPED_UNICODE)]]]],
                ],
            ]);

        $block = app(AiLessonPlannerService::class)->generateLessonPlan(
            topic: 'الكسور',
            subject: 'الرياضيات',
            level: 'الأولى متوسط',
        );

        $this->assertCount(3, $block['data']['lesson_phases']);
        Http::assertSentCount(2);
    }

    public function test_it_falls_back_to_another_gemini_model_when_the_primary_is_overloaded(): void
    {
        config()->set('ai.retry_times', 2);
        config()->set('ai.gemini.fallback_model', 'gemini-3.5-flash');

        $success = [
            'candidates' => [
                ['content' => ['parts' => [['text' => json_encode($this->plan(), JSON_UNESCAPED_UNICODE)]]]],
            ],
        ];

        Http::fake(function (Request $request) use ($success) {
            if (str_contains($request->url(), 'models/gemini-3.8-flash:generateContent')) {
                return Http::response([
                    'error' => ['message' => 'This model is currently experiencing high demand.'],
                ], 503);
            }

            if (str_contains($request->url(), 'models/gemini-3.5-flash:generateContent')) {
                return Http::response($success);
            }

            return Http::response(['error' => ['message' => 'Unexpected model.']], 404);
        });

        $block = app(AiLessonPlannerService::class)->generateLessonPlan(
            topic: 'الكسور',
            subject: 'الرياضيات',
            level: 'الأولى متوسط',
        );

        $this->assertCount(3, $block['data']['lesson_phases']);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'models/gemini-3.5-flash:generateContent'));
    }

    public function test_it_reports_a_clear_arabic_error_when_retries_are_exhausted(): void
    {
        config()->set('ai.retry_times', 2);
        config()->set('ai.gemini.fallback_model', null);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'This model is currently experiencing high demand.'],
            ], 503),
        ]);

        try {
            app(AiLessonPlannerService::class)->generateLessonPlan('الكسور', 'الرياضيات', 'الأولى متوسط');

            $this->fail('The service should have thrown an AiLessonPlannerException.');
        } catch (AiLessonPlannerException $exception) {
            $this->assertStringContainsString('مشغولة', $exception->getMessage());
        }

        Http::assertSentCount(2);
    }

    public function test_it_fails_fast_when_the_api_key_is_missing(): void
    {
        config()->set('ai.gemini.key', null);

        Http::fake();

        try {
            app(AiLessonPlannerService::class)->generateLessonPlan('الكسور', 'الرياضيات', 'الأولى متوسط');

            $this->fail('The service should have thrown an AiLessonPlannerException.');
        } catch (AiLessonPlannerException) {
            // Expected: the missing key is reported before any HTTP call.
        }

        Http::assertNothingSent();
    }

    public function test_it_rejects_unreadable_responses(): void
    {
        $this->fakeGemini('عذراً، لا أستطيع مساعدتك في هذا الطلب.');

        $this->expectException(AiLessonPlannerException::class);

        app(AiLessonPlannerService::class)->generateLessonPlan('الكسور', 'الرياضيات', 'الأولى متوسط');
    }

    public function test_the_filament_action_injects_the_generated_block_into_the_form(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);

        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
        ]);

        $this->fakeGemini(json_encode($this->plan(), JSON_UNESCAPED_UNICODE));

        $this->actingAs($teacher);

        Livewire::test(CreateDailyLog::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'title' => 'درس الكسور',
            ])
            ->callFormComponentAction('title', 'generateLessonPlan')
            ->assertOk()
            ->assertHasNoFormErrors()
            ->assertSet('data.title', 'درس الكسور')
            ->assertSet('data.content_data', function (array $state): bool {
                $block = array_values($state)[0] ?? null;

                return is_array($block)
                    && $block['type'] === 'lesson_structure'
                    && $block['data']['competence'] === $this->plan()['competence']
                    && count($block['data']['lesson_phases']) === 3;
            });

        Http::assertSent(fn (Request $request): bool => str_contains($request['contents'][0]['parts'][0]['text'], 'درس الكسور')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'الرياضيات'));

        Notification::assertNotified(__('تم توليد التحضير'));
    }

    public function test_the_filament_action_passes_the_active_locale_to_the_planner(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
        ]);

        $this->fakeGemini(json_encode($this->plan(), JSON_UNESCAPED_UNICODE));
        app()->setLocale('fr');
        $this->actingAs($teacher);

        Livewire::test(CreateDailyLog::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'title' => 'درس الكسور',
            ])
            ->callFormComponentAction('title', 'generateLessonPlan')
            ->assertOk()
            ->assertHasNoFormErrors();

        Http::assertSent(fn (Request $request): bool => str_contains($request['contents'][0]['parts'][0]['text'], 'Rédigez un plan de leçon complet en français clair'));
    }

    public function test_the_action_requires_a_title_and_a_schedule(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateDailyLog::class)
            ->callFormComponentAction('title', 'generateLessonPlan')
            ->assertOk()
            ->assertSet('data.content_data', fn (mixed $state): bool => blank($state));

        Http::assertNothingSent();

        Notification::assertNotified(__('معلومات ناقصة'));
    }

    public function test_the_action_is_scoped_to_the_current_teachers_schedules(): void
    {
        Http::fake();

        $teacher = User::factory()->create();
        $otherSchedule = Schedule::factory()->create();

        $this->actingAs($teacher);

        Livewire::test(CreateDailyLog::class)
            ->fillForm([
                'schedule_id' => $otherSchedule->id,
                'title' => 'درس الكسور',
            ])
            ->callFormComponentAction('title', 'generateLessonPlan')
            ->assertOk();

        Http::assertNothingSent();

        Notification::assertNotified(__('الحصة غير متاحة'));
    }
}
