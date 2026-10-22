<?php

namespace App\Services;

use App\Exceptions\AiLessonPlannerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Generates a full lesson preparation with a generative AI provider and
 * returns it shaped exactly like a Filament v3 Builder state entry, so it
 * can be injected into the `content_data` form field with `$set()`.
 */
class AiLessonPlannerService
{
    /**
     * Build a Filament-ready `lesson_structure` block for the given topic.
     *
     * Filament v3 keys Builder blocks and Repeater items by UUID, so the
     * phases below already carry UUID keys: the returned block can be
     * injected as `$set('content_data', [(string) Str::uuid() => $block])`
     * and renders immediately, without a page reload. Dehydration strips
     * the keys again before the value is persisted as JSON.
     *
     * @return array{type: string, data: array{competence: string, lesson_phases: array<string, array{phase_name: string, content: string}>}}
     *
     * @throws AiLessonPlannerException
     */
    public function generateLessonPlan(string $topic, string $subject, string $level): array
    {
        $plan = $this->normalizePlan(
            $this->decodePlan(
                $this->requestPlan($this->buildPrompt($topic, $subject, $level)),
            ),
        );

        $lessonPhases = [];

        foreach ($plan['lesson_phases'] as $phase) {
            $lessonPhases[(string) Str::uuid()] = $phase;
        }

        return [
            'type' => 'lesson_structure',
            'data' => [
                'competence' => $plan['competence'],
                'lesson_phases' => $lessonPhases,
            ],
        ];
    }

    /**
     * Send the prompt to the configured provider and return its text answer.
     *
     * @throws AiLessonPlannerException
     */
    protected function requestPlan(string $prompt): string
    {
        return match (config('ai.provider', 'gemini')) {
            'openai' => $this->requestOpenAi($prompt),
            default => $this->requestGemini($prompt),
        };
    }

    /**
     * Google AI Studio (Gemini) generateContent call with structured output.
     *
     * @throws AiLessonPlannerException
     */
    protected function requestGemini(string $prompt): string
    {
        $key = config('ai.gemini.key');

        if (blank($key)) {
            throw new AiLessonPlannerException(__('لم يتم ضبط مفتاح Gemini. أضف GEMINI_API_KEY إلى ملف .env.'));
        }

        $models = $this->geminiModels();
        $lastResponse = null;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->planSchema(),
            ],
        ];

        foreach ($models as $index => $model) {
            $response = $this->post(
                $this->http()
                    ->baseUrl((string) config('ai.gemini.base_url'))
                    ->withHeaders(['x-goog-api-key' => $key]),
                'models/'.$model.':generateContent',
                $payload,
            );

            $lastResponse = $response;

            if (! $response->failed()) {
                return $this->extractText(data_get($response->json(), 'candidates.0.content.parts.0.text'));
            }

            if (! $this->shouldUseFallback($response, $models, $index)) {
                break;
            }

            Log::warning('AI lesson planner falling back to another Gemini model.', [
                'status' => $response->status(),
                'primary_model' => $model,
                'fallback_model' => $models[$index + 1],
            ]);
        }

        if (! $lastResponse instanceof Response) {
            throw new AiLessonPlannerException(__('لم يتم ضبط نموذج Gemini. أضف GEMINI_MODEL إلى ملف .env.'));
        }

        throw new AiLessonPlannerException($this->providerError($lastResponse));
    }

    /**
     * The primary Gemini model plus the optional overload fallback, without
     * duplicates or empty configuration values.
     *
     * @return list<string>
     */
    protected function geminiModels(): array
    {
        $models = array_map(
            static fn (mixed $model): string => trim((string) $model),
            [
                config('ai.gemini.model'),
                config('ai.gemini.fallback_model'),
            ],
        );

        return array_values(array_unique(array_filter(
            $models,
            static fn (string $model): bool => $model !== '',
        )));
    }

    /**
     * Only fail over for capacity or upstream-server errors. A bad request or
     * an exhausted project quota will fail on every model, so don't switch
     * models for those responses.
     *
     * @param  list<string>  $models
     */
    protected function shouldUseFallback(Response $response, array $models, int $index): bool
    {
        return isset($models[$index + 1])
            && in_array($response->status(), [500, 502, 503, 504], true);
    }

    /**
     * OpenAI Chat Completions call with JSON output mode.
     *
     * @throws AiLessonPlannerException
     */
    protected function requestOpenAi(string $prompt): string
    {
        $key = config('ai.openai.key');

        if (blank($key)) {
            throw new AiLessonPlannerException(__('لم يتم ضبط مفتاح OpenAI. أضف OPENAI_API_KEY إلى ملف .env.'));
        }

        $response = $this->post(
            $this->http()
                ->baseUrl((string) config('ai.openai.base_url'))
                ->withToken($key),
            'chat/completions',
            [
                'model' => config('ai.openai.model'),
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مستشار بيداغوجي متخصص في المناهج الجزائرية. تعيد دائماً كائن JSON صالحاً دون أي نص إضافي.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ],
        );

        if ($response->failed()) {
            throw new AiLessonPlannerException($this->providerError($response));
        }

        return $this->extractText(data_get($response->json(), 'choices.0.message.content'));
    }

    /**
     * The shared HTTP client configuration for provider calls.
     */
    protected function http(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('ai.timeout', 60))
            ->connectTimeout((int) config('ai.connect_timeout', 10))
            ->retry(
                times: max(1, (int) config('ai.retry_times', 2)),
                sleepMilliseconds: max(0, (int) config('ai.retry_delay', 500)),
                when: fn (Throwable $exception): bool => $this->isTransientException($exception),
            );
    }

    /**
     * Network blips and temporary provider capacity errors are worth retrying.
     */
    protected function isTransientException(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        return in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
    }

    /**
     * Post a payload, converting transport failures into a readable error.
     *
     * Laravel's HTTP retry throws the final failed response as an exception.
     * Return that response so the caller can inspect the provider's error.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws AiLessonPlannerException
     */
    protected function post(PendingRequest $request, string $url, array $payload): Response
    {
        try {
            return $request->post($url, $payload);
        } catch (RequestException $exception) {
            return $exception->response;
        } catch (ConnectionException $exception) {
            throw new AiLessonPlannerException(
                __('تعذر الاتصال بمزود الذكاء الاصطناعي. تحقق من اتصال الشبكة ثم أعد المحاولة.'),
                previous: $exception,
            );
        }
    }

    /**
     * The Arabic prompt that pins the answer to the exact JSON contract
     * Filament's `lesson_structure` block expects.
     */
    protected function buildPrompt(string $topic, string $subject, string $level): string
    {
        $subject = filled($subject) ? $subject : 'غير محددة';
        $level = filled($level) ? $level : 'غير محدد';

        return <<<PROMPT
        أنت مفتش تربوي جزائري في التعليم المتوسط، خبير في المقاربة بالكفاءات والمناهج الرسمية.

        حرّر مخطط تحضير كامل بالعربية الفصحى المبسّطة لدرس في مادة «{$subject}» يستهدف «{$level}»، حول الموضوع: «{$topic}».

        التعليمات:
        - اكتب الكفاءة المستهدفة في جملة واحدة واضحة تتضمن الفعل والمعرفة والسياق.
        - قسّم سير الدرس إلى 3 إلى 6 مراحل بيداغوجية مرتّبة زمنياً (مثل: وضعية الانطلاق، بناء التعلمات، استثمار المكتسبات، التقويم...).
        - اكتب في كل مرحلة محتوى عملياً للمدرّس: تعليمات، أسئلة، نشاطات، مع الإشارة إلى المدة المقترحة بالدقائق.
        - لا تستعمل وسوم HTML ولا تنسيق Markdown؛ اكتب نصاً عربياً عادياً مع فواصل أسطر عند الحاجة.
        - أعد النتيجة على شكل كائن JSON صالح فقط، دون أي شرح إضافي، بهذه البنية بالضبط:

        {
          "competence": "نص الكفاءة المستهدفة",
          "lesson_phases": [
            { "phase_name": "اسم المرحلة", "content": "محتوى المرحلة" }
          ]
        }
        PROMPT;
    }

    /**
     * Gemini structured-output schema matching the lesson plan contract.
     *
     * @return array<string, mixed>
     */
    protected function planSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'competence' => [
                    'type' => 'STRING',
                    'description' => 'الكفاءة المستهدفة في جملة واحدة.',
                ],
                'lesson_phases' => [
                    'type' => 'ARRAY',
                    'description' => 'مراحل سير الدرس بالترتيب الزمني.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'phase_name' => ['type' => 'STRING'],
                            'content' => ['type' => 'STRING'],
                        ],
                        'required' => ['phase_name', 'content'],
                    ],
                ],
            ],
            'required' => ['competence', 'lesson_phases'],
        ];
    }

    /**
     * Ensure the provider actually answered with text.
     *
     * @throws AiLessonPlannerException
     */
    protected function extractText(mixed $text): string
    {
        if (! is_string($text) || trim($text) === '') {
            throw new AiLessonPlannerException(__('لم يُرجع مزود الذكاء الاصطناعي أي محتوى. جرّب مرة أخرى.'));
        }

        return $text;
    }

    /**
     * Decode the model answer, tolerating Markdown fences or surrounding prose.
     *
     * @return array<string, mixed>
     *
     * @throws AiLessonPlannerException
     */
    protected function decodePlan(string $text): array
    {
        $text = trim($text);
        $text = trim((string) preg_replace('/^```[a-zA-Z]*\s*|```$/m', '', $text));

        $plan = json_decode($text, true);

        if (! is_array($plan)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');

            $plan = ($start !== false && $end !== false && $end > $start)
                ? json_decode(substr($text, $start, $end - $start + 1), true)
                : null;
        }

        if (! is_array($plan)) {
            Log::warning('AI lesson planner returned invalid JSON.', [
                'response' => Str::limit($text, 1000),
            ]);

            throw new AiLessonPlannerException(__('أعاد مزود الذكاء الاصطناعي استجابة غير قابلة للقراءة. أعد المحاولة.'));
        }

        return $plan;
    }

    /**
     * Validate and clean the decoded plan.
     *
     * @param  array<string, mixed>  $plan
     * @return array{competence: string, lesson_phases: list<array{phase_name: string, content: string}>}
     *
     * @throws AiLessonPlannerException
     */
    protected function normalizePlan(array $plan): array
    {
        $lessonPhases = [];

        foreach ((array) ($plan['lesson_phases'] ?? []) as $phase) {
            if (! is_array($phase)) {
                continue;
            }

            // "name" is accepted as a fallback for providers that drift from the schema.
            $name = trim((string) ($phase['phase_name'] ?? $phase['name'] ?? ''));
            $content = trim((string) ($phase['content'] ?? ''));

            if ($name === '' || $content === '') {
                continue;
            }

            $lessonPhases[] = [
                'phase_name' => $name,
                'content' => $content,
            ];
        }

        if ($lessonPhases === []) {
            throw new AiLessonPlannerException(__('لم يُرجع مزود الذكاء الاصطناعي أي مراحل درس صالحة. أعد المحاولة.'));
        }

        return [
            'competence' => trim((string) ($plan['competence'] ?? '')),
            'lesson_phases' => $lessonPhases,
        ];
    }

    /**
     * Turn a failed provider response into a user-readable message.
     */
    protected function providerError(Response $response): string
    {
        Log::warning('AI lesson planner request failed.', [
            'status' => $response->status(),
            'body' => Str::limit($response->body(), 1000),
        ]);

        $status = $response->status();

        if ($status === 503) {
            return __('خدمة الذكاء الاصطناعي مشغولة مؤقتاً بسبب الضغط. أعد المحاولة بعد قليل.');
        }

        if ($status === 429) {
            return __('تم بلوغ حد استخدام الذكاء الاصطناعي مؤقتاً. أعد المحاولة بعد قليل.');
        }

        if (in_array($status, [500, 502, 504], true)) {
            return __('تعذر الوصول إلى مزود الذكاء الاصطناعي مؤقتاً. أعد المحاولة بعد قليل.');
        }

        $message = data_get($response->json(), 'error.message');

        return is_string($message) && filled($message)
            ? __('فشل طلب الذكاء الاصطناعي: :message', ['message' => Str::limit($message, 200)])
            : __('فشل طلب الذكاء الاصطناعي (رمز :status).', ['status' => $status]);
    }
}
