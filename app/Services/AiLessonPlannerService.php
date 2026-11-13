<?php

namespace App\Services;

use App\Exceptions\AiLessonPlannerException;
use App\Models\ActiveLearningStrategy;
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
     * @return array{type: string, data: array{competence: string, lesson_phases: array<string, array{phase_name: string, strategy: ?string, content: string}>}}
     *
     * @throws AiLessonPlannerException
     */
    public function generateLessonPlan(
        string $topic,
        string $subject,
        string $level,
        ?string $locale = null,
        ?string $preferredStrategy = null,
    ): array {
        $requestedLocale = $locale ?? app()->getLocale();
        $locale = in_array($requestedLocale, ['ar', 'en', 'fr'], true)
            ? $requestedLocale
            : 'ar';

        $plan = $this->normalizePlan(
            $this->decodePlan(
                $this->requestPlan($this->buildPrompt($topic, $subject, $level, $locale, $preferredStrategy), $locale),
            ),
        );

        $lessonPhases = [];

        foreach ($plan['lesson_phases'] as $phase) {
            $lessonPhases[(string) Str::uuid()] = [
                'phase_name' => $phase['phase_name'],
                'strategy' => $phase['strategy'] ?? null,
                'content' => $phase['content'],
            ];
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
     * Generate an authentic Algerian Pedagogical Memo (Fiche pédagogique)
     * adhering to competency-based education and active learning standards.
     *
     * @return array{
     *     competence: string,
     *     starting_situation: string,
     *     activities: list<array{name: string, duration: int, strategy: ?string, content: string}>,
     *     resources: string,
     *     evaluation: string,
     *     notes: string
     * }
     *
     * @throws AiLessonPlannerException
     */
    public function generatePedagogicalMemo(
        string $topic,
        string $subject,
        string $level,
        ?string $phase = null,
        ?string $locale = null,
        ?string $preferredStrategy = null,
        ?string $customInstructions = null,
    ): array {
        $requestedLocale = $locale ?? app()->getLocale();
        $locale = in_array($requestedLocale, ['ar', 'en', 'fr'], true)
            ? $requestedLocale
            : 'ar';

        $prompt = $this->buildMemoPrompt(
            $topic,
            $subject,
            $level,
            $phase,
            $locale,
            $preferredStrategy,
            $customInstructions,
        );

        $schema = $this->memoSchema($locale);
        $text = $this->requestPlan($prompt, $locale, $schema);
        $decoded = $this->decodePlan($text);

        return $this->normalizeMemoPlan($decoded, $topic, $subject, $level);
    }

    /**
     * Send the prompt to the configured provider and return its text answer.
     *
     * @throws AiLessonPlannerException
     */
    protected function requestPlan(string $prompt, string $locale, ?array $schema = null): string
    {
        return match (config('ai.provider', 'gemini')) {
            'openai' => $this->requestOpenAi($prompt, $locale),
            default => $this->requestGemini($prompt, $locale, $schema),
        };
    }

    /**
     * Google AI Studio (Gemini) generateContent call with structured output.
     *
     * @throws AiLessonPlannerException
     */
    protected function requestGemini(string $prompt, string $locale, ?array $schema = null): string
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
                'responseSchema' => $schema ?? $this->planSchema($locale),
            ],
        ];

        foreach ($models as $index => $model) {
            try {
                $response = $this->post(
                    $this->http()
                        ->baseUrl((string) config('ai.gemini.base_url'))
                        ->withHeaders(['x-goog-api-key' => $key]),
                    'models/'.$model.':generateContent',
                    $payload,
                );
            } catch (AiLessonPlannerException $exception) {
                if (isset($models[$index + 1])) {
                    Log::warning('AI lesson planner connection failed on model, falling back.', [
                        'primary_model' => $model,
                        'fallback_model' => $models[$index + 1],
                        'error' => $exception->getMessage(),
                    ]);

                    continue;
                }

                throw $exception;
            }

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
            && in_array($response->status(), [404, 429, 500, 502, 503, 504], true);
    }

    /**
     * OpenAI Chat Completions call with JSON output mode.
     *
     * @throws AiLessonPlannerException
     */
    protected function requestOpenAi(string $prompt, string $locale): string
    {
        $key = config('ai.openai.key');

        if (blank($key)) {
            throw new AiLessonPlannerException(__('لم يتم ضبط مفتاح OpenAI. أضف OPENAI_API_KEY إلى ملف .env.'));
        }

        $systemPrompt = match ($locale) {
            'en' => 'You are an Algerian middle-school pedagogical expert. Always return a valid JSON object without any additional text.',
            'fr' => 'Vous êtes un expert pédagogique algérien du cycle moyen. Retournez toujours un objet JSON valide sans texte supplémentaire.',
            default => 'أنت مستشار بيداغوجي متخصص في المناهج الجزائرية. تعيد دائماً كائن JSON صالحاً دون أي نص إضافي.',
        };

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
                        'content' => $systemPrompt,
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
     * Active learning strategies available in Algerian middle schools.
     *
     * @return list<string>
     */
    protected function activeLearningStrategies(): array
    {
        try {
            $strategies = ActiveLearningStrategy::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('name')
                ->all();

            if (! empty($strategies)) {
                return $strategies;
            }
        } catch (Throwable) {
            // Table might not exist yet or in isolated test environments
        }

        return [
            'التفكير — المشاركة — العرض (Think-Pair-Share)',
            'بطاقة الخروج (Exit Ticket)',
            'الجِيجسو (Jigsaw)',
            'المشي في المعرض (Gallery Walk)',
            'القرعة العشوائية (Random Picker)',
            'التعليم بالأقران (Peer Instruction)',
            'الخرائط المفاهيمية والذهنية (Mind / Concept Mapping)',
            'لعب الأدوار (Role Play)',
            'حل المشكلات والتعلم بالاكتشاف (PBL)',
            'العصف الذهني المنظم (Brainstorming)',
        ];
    }

    /**
     * Build a localized prompt for the exact JSON contract Filament expects.
     */
    protected function buildPrompt(string $topic, string $subject, string $level, string $locale, ?string $preferredStrategy = null): string
    {
        $strategiesList = implode(', ', $this->activeLearningStrategies());

        [$role, $lesson, $competence, $stages, $practicalContent, $activeLearning, $plainText, $instructionHeading, $jsonInstruction, $competenceLabel, $phaseLabel, $strategyLabel, $contentLabel, $unknown] = match ($locale) {
            'en' => [
                'You are an Algerian middle-school pedagogical inspector and an expert in competency-based teaching, active learning strategies, and the official curriculum.',
                'Write a complete lesson plan in clear, age-appropriate English for subject ":subject", level ":level", on the topic ":topic".',
                'Write the target competence in one clear sentence that includes an action, knowledge, and context.',
                'Divide the lesson into 3 to 6 stages in chronological order (for example: warm-up, guided learning, practice, and assessment).',
                'Give practical teacher-facing content for each stage, including instructions, questions, activities, and a suggested duration in minutes.',
                'Apply learner-centered active learning strategies (such as: '.$strategiesList.'). In each stage, specify the strategy used in the "strategy" field.'.($preferredStrategy ? ' Emphasize applying the strategy: '.$preferredStrategy.'.' : ''),
                'Do not use HTML or Markdown; write plain English and use line breaks where helpful.',
                'Instructions:',
                'Return only a valid JSON object, without any additional explanation, using exactly this structure:',
                'Target competence',
                'Stage name',
                'Active learning strategy used in this stage (e.g. Think-Pair-Share)',
                'Stage content',
                'unspecified',
            ],
            'fr' => [
                'Vous êtes un inspecteur pédagogique algérien du cycle moyen, expert en approche par compétences, stratégies d’apprentissage actif et programmes officiels.',
                'Rédigez un plan de leçon complet en français clair et adapté à l’âge des élèves pour la matière « :subject », le niveau « :level » et le thème « :topic ».',
                'Formulez la compétence visée en une phrase claire comprenant une action, un savoir et un contexte.',
                'Divisez la leçon en 3 à 6 étapes dans l’ordre chronologique (par exemple : mise en situation, construction des apprentissages, réinvestissement et évaluation).',
                'Pour chaque étape, proposez un contenu pratique destiné à l’enseignant : consignes, questions, activités et durée suggérée en minutes.',
                'Mettez en œuvre des stratégies d’apprentissage actif (ex. : '.$strategiesList.'). Pour chaque étape, précisez la stratégie retenue dans le champ « strategy ».'.($preferredStrategy ? ' Mettez particulièrement l’accent sur : '.$preferredStrategy.'.' : ''),
                'N’utilisez ni HTML ni Markdown ; rédigez en français courant et utilisez des sauts de ligne si nécessaire.',
                'Consignes :',
                'Retournez uniquement un objet JSON valide, sans explication supplémentaire, en respectant exactement cette structure :',
                'Compétence visée',
                'Nom de l’étape',
                'Stratégie d’apprentissage actif appliquée (ex. Jigsaw)',
                'Contenu de l’étape',
                'non précisé',
            ],
            default => [
                'أنت مفتش تربوي جزائري في التعليم المتوسط، خبير في المقاربة بالكفاءات، استراتيجيات التعلم النشط، والمناهج الرسمية.',
                'حرّر مخطط تحضير كامل بالعربية الفصحى المبسّطة لمادة «:subject» ومستوى «:level» حول الموضوع «:topic».',
                'اكتب الكفاءة المستهدفة في جملة واحدة واضحة تتضمن الفعل والمعرفة والسياق.',
                'قسّم سير الدرس إلى 3 إلى 6 مراحل بيداغوجية مرتّبة زمنياً (مثل: وضعية الانطلاق، بناء التعلمات، استثمار المكتسبات، التقويم).',
                'اكتب في كل مرحلة محتوى عملياً للمدرّس: تعليمات، أسئلة، نشاطات، مع الإشارة إلى المدة المقترحة بالدقائق.',
                'وظّف استراتيجيات التعلم النشط (مثل: '.$strategiesList.'). اذكر الاستراتيجية المعتمدة في حقل "strategy" لكل مرحلة لجعل التلميذ فاعلاً ومحوراً للعملية التعليمية.'.($preferredStrategy ? ' ركّز خصوصاً على توظيف استراتيجية: '.$preferredStrategy.'.' : ''),
                'لا تستعمل وسوم HTML ولا تنسيق Markdown؛ اكتب نصاً عربياً عادياً مع فواصل أسطر عند الحاجة.',
                'التعليمات:',
                'أعد النتيجة على شكل كائن JSON صالح فقط، دون أي شرح إضافي، بهذه البنية بالضبط:',
                'نص الكفاءة المستهدفة',
                'اسم المرحلة',
                'استراتيجية التعلم النشط المطبقة (مثل: التفكير — المشاركة — العرض)',
                'محتوى المرحلة',
                'غير محدد',
            ],
        };
        $subject = filled($subject) ? $subject : $unknown;
        $level = filled($level) ? $level : $unknown;
        $lesson = strtr($lesson, [
            ':subject' => $subject,
            ':level' => $level,
            ':topic' => $topic,
        ]);

        return <<<PROMPT
        {$role}

        {$lesson}

        {$instructionHeading}
        - {$competence}
        - {$stages}
        - {$practicalContent}
        - {$activeLearning}
        - {$plainText}
        - {$jsonInstruction}

        {
          "competence": "{$competenceLabel}",
          "lesson_phases": [
            { "phase_name": "{$phaseLabel}", "strategy": "{$strategyLabel}", "content": "{$contentLabel}" }
          ]
        }
        PROMPT;
    }

    /**
     * Gemini structured-output schema for a full Algerian Pedagogical Memo.
     *
     * @return array<string, mixed>
     */
    protected function memoSchema(string $locale): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'competence' => [
                    'type' => 'STRING',
                    'description' => 'Target competence in one clear sentence.',
                ],
                'starting_situation' => [
                    'type' => 'STRING',
                    'description' => 'Starting problem situation (context, support, and challenging question).',
                ],
                'activities' => [
                    'type' => 'ARRAY',
                    'description' => 'Lesson activities and pedagogical phases in chronological order.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name' => [
                                'type' => 'STRING',
                                'description' => 'Activity name (e.g. وضعية الانطلاق، بناء التعلمات، استثمار المكتسبات، التقويم)',
                            ],
                            'duration' => [
                                'type' => 'INTEGER',
                                'description' => 'Suggested duration in minutes',
                            ],
                            'strategy' => [
                                'type' => 'STRING',
                                'description' => 'Active learning strategy applied in this phase',
                            ],
                            'teacher_role' => [
                                'type' => 'STRING',
                                'description' => 'Teacher instructions, facilitation, guidance, and questions',
                            ],
                            'student_role' => [
                                'type' => 'STRING',
                                'description' => 'Learner activities, inquiry, collaborative work, and answers',
                            ],
                            'written_trace' => [
                                'type' => 'STRING',
                                'description' => 'Written trace recorded on board and notebook',
                            ],
                            'evaluation_indicator' => [
                                'type' => 'STRING',
                                'description' => 'Formative assessment criteria and checkpoint indicators',
                            ],
                            'content' => [
                                'type' => 'STRING',
                                'description' => 'Detailed pedagogical tasks for teacher and learners',
                            ],
                        ],
                        'required' => ['name', 'content'],
                    ],
                ],
                'resources' => [
                    'type' => 'STRING',
                    'description' => 'Didactic resources and educational materials',
                ],
                'evaluation' => [
                    'type' => 'STRING',
                    'description' => 'Formative and summative evaluation criteria',
                ],
                'notes' => [
                    'type' => 'STRING',
                    'description' => 'Pedagogical advice and teacher notes',
                ],
            ],
            'required' => ['competence', 'starting_situation', 'activities', 'resources', 'evaluation'],
        ];
    }

    /**
     * Build prompt for generating a complete Algerian Pedagogical Memo.
     */
    protected function buildMemoPrompt(
        string $topic,
        string $subject,
        string $level,
        ?string $phase,
        string $locale,
        ?string $preferredStrategy = null,
        ?string $customInstructions = null,
    ): string {
        $strategiesList = implode(', ', $this->activeLearningStrategies());
        $phase = filled($phase) ? $phase : 'المقطع التعلمي المعني';

        $customPart = filled($customInstructions)
            ? "توجيهات وإضافات خاصة بالأستاذ: {$customInstructions}"
            : '';

        $strategyPart = filled($preferredStrategy)
            ? "الاستراتيجية البيداغوجية الواجب التركيز عليها وتطبيقها: {$preferredStrategy}."
            : "وظّف استراتيجيات التعلم النشط المناسبة لجعل المتعلم محوراً للعملية التعليمية (مثل: {$strategiesList}).";

        return <<<PROMPT
        أنت مفتش تربوي ومستشار بيداغوجي متخصص في مناهج وزارة التربية الوطنية الجزائرية (التعليم المتوسط — المقاربة بالكفاءات ومنهاج الجيل الثاني).
        المطلوب تحرير مذكرة بيداغوجية رسمية متكاملة ومنهجية حديثة لمادة «{$subject}» — المستوى «{$level}» — المقطع التعلمي: «{$phase}» حول موضوع الدرس: «{$topic}».

        المعايير المنهجية الرسمية وفق دليل المفتشية العامة للبيداغوجيا:
        1. الكفاءة المستهدفة (competence): صياغة إجرائية دقيقة محددة بسلوك قابل للملاحظة والتقويم يربط بين الفعل، المعرفة والسياق (المركبة المستهدفة ومؤشر الكفاءة).
        2. الوضعية المشكلة الانطلاقية (starting_situation): وضعية دالة من واقع التلميذ تشتمل على السياق، السند، والمهمة/التعليمة وتثير تساؤل ودافعية المتعلمين.
        3. سيرورة التعلمات والأنشطة (activities): تتوزع الحصة (60 دقيقة) بدقة منهجية على 3 إلى 4 محطات رسمية:
           - مرحلة الانطلاق (وضعية الانطلاق والتهيئة النفسية واستحضار المكتسبات) ~10 دقائق
           - مرحلة بناء التعلمات (النشاط الأول: البحث والاستكشاف والتحليل) ~20 دقيقة
           - مرحلة بناء التعلمات (النشاط الثاني: التركيب وإرساء المورد المعرفي) ~15 دقيقة
           - مرحلة استثمار المكتسبات والتقويم (وضعية إدماج جزئي فردي/فوجي) ~15 دقيقة
           لكل مرحلة اذكر بدقة:
           - name: اسم المحطة البيداغوجية
           - duration: المدة المقترحة بالدقائق (رقم صحيح مثلاً: 10، 20، 15، 15)
           - strategy: استراتيجية التعلم النشط المطبقة ونمط التفويج (فردي، ثنائي، أفواج)
           - teacher_role: دور ونشاط الأستاذ (التعليمات، التوجيه، التيسير، إلقاء الأسئلة الهادفة)
           - student_role: نشاط ومهمات المتعلم (الملاحظة، الاستكشاف، التحليل، التناظر، الاستنتاج)
           - written_trace: الأثر الكتابي المستخلص (الخلاصة أو النتيجة التي تُسجل في كراس الدروس وعلى السبورة)
           - evaluation_indicator: مؤشر التقويم والتعديل المرحلي
           - content: ملخص شامل يجمع الأنشطة لسيرورة المحطة
        4. الموارد والسندات (resources): الوسائل الديداكتيكية والأدوات الرقمية والمادية (الكتاب المدرسي، المنهاج، السبورة، الداتاشو، بطاقات المهام، البرمجيات).
        5. التقويم (evaluation): معايير ومؤشرات التحقق الرسمية: م1 الوجاهة، م2 الاستعمال السليم لأدوات المادة، م3 الانسجام، م4 الإتقان.
        6. توجيهات الأستاذ والمعالجة (notes): توصيات بيداغوجية لتسيير القسم، مراعاة الفروق الفردية، وخطة المرافقة والدعم للمتعثرين.

        {$strategyPart}
        {$customPart}

        التعليمات:
        أعد النتيجة على شكل كائن JSON صالح فقط، دون أي شرح أو وسوم markdown، بالبنية التالية:
        {
          "competence": "نص الكفاءة المستهدفة الإجرائية",
          "starting_situation": "نص الوضعية المشكلة الانطلاقية (السياق، السند، المهمة)",
          "activities": [
            {
              "name": "اسم المحطة البيداغوجية",
              "duration": 15,
              "strategy": "استراتيجية التعلم النشط ونمط العمل",
              "teacher_role": "دور ونشاط الأستاذ وإلقاء التعليمات",
              "student_role": "نشاط ومهمات المتعلم والأداء المطلوب",
              "written_trace": "الأثر الكتابي المستخلص في الكراس والسبورة",
              "evaluation_indicator": "مؤشر التقويم والتحقق المرحلي",
              "content": "ملخص متكامل لسيرورة المحطة ودور الأستاذ والمتعلم"
            }
          ],
          "resources": "الوسائل والسندات التعليمية والديداكتيكية",
          "evaluation": "معايير ومؤشرات التقويم الرسمية (م1، م2، م3، م4)",
          "notes": "المعالجة والتوجيهات البيداغوجية ومراعاة الفروق الفردية"
        }
        PROMPT;
    }

    /**
     * Validate and normalize the pedagogical memo plan.
     *
     * @param  array<string, mixed>  $plan
     * @return array{
     *     competence: string,
     *     starting_situation: string,
     *     activities: list<array{name: string, duration: int, strategy: ?string, content: string}>,
     *     resources: string,
     *     evaluation: string,
     *     notes: string
     * }
     *
     * @throws AiLessonPlannerException
     */
    protected function normalizeMemoPlan(array $plan, string $topic, string $subject, string $level): array
    {
        $activities = [];
        $rawActivities = $plan['activities'] ?? $plan['lesson_phases'] ?? [];

        if (is_array($rawActivities)) {
            foreach ($rawActivities as $act) {
                if (! is_array($act)) {
                    continue;
                }

                $name = trim((string) ($act['name'] ?? $act['phase_name'] ?? ''));
                $duration = (int) ($act['duration'] ?? 15);
                if ($duration <= 0) {
                    $duration = 15;
                }
                $strategy = trim((string) ($act['strategy'] ?? $act['active_learning_strategy'] ?? ''));
                $content = trim((string) ($act['content'] ?? ''));

                $teacherRole = trim((string) ($act['teacher_role'] ?? ''));
                $studentRole = trim((string) ($act['student_role'] ?? ''));
                $writtenTrace = trim((string) ($act['written_trace'] ?? ''));
                $evalIndicator = trim((string) ($act['evaluation_indicator'] ?? ''));

                if ($content === '' && ($teacherRole !== '' || $studentRole !== '')) {
                    $content = trim("{$teacherRole}\n{$studentRole}");
                }

                if ($name === '' && $content === '') {
                    continue;
                }

                $activityItem = [
                    'name' => $name ?: __('مرحلة تعلمية'),
                    'duration' => $duration,
                    'strategy' => $strategy !== '' ? $strategy : null,
                    'content' => $content,
                ];

                if ($teacherRole !== '') {
                    $activityItem['teacher_role'] = $teacherRole;
                }
                if ($studentRole !== '') {
                    $activityItem['student_role'] = $studentRole;
                }
                if ($writtenTrace !== '') {
                    $activityItem['written_trace'] = $writtenTrace;
                }
                if ($evalIndicator !== '') {
                    $activityItem['evaluation_indicator'] = $evalIndicator;
                }

                $activities[] = $activityItem;
            }
        }

        if (empty($activities)) {
            $activities = [
                [
                    'name' => 'وضعية الانطلاق (التهيئة واستحضار المكتسبات)',
                    'duration' => 10,
                    'strategy' => 'العصف الذهني المنظم (حوار موجه)',
                    'teacher_role' => "طرح وضعية مشكلة دالة حول «{$topic}»، توجيه أسئلة لاستثارة الدافعية وتسيير الحوار لحصر تمثلات التلاميذ.",
                    'student_role' => 'الملاحظة والتعبير الحر عن التصورات الأولية واقتراح فرضيات تفسيرية للإشكالية المطروحة.',
                    'written_trace' => 'تدوين الإشكالية والفرضيات الأولية المقترحة على السبورة.',
                    'evaluation_indicator' => 'يسترجع المكتسبات القبلية ذات الصلة ويصوغ فرضيات منطقية وجيهة.',
                    'content' => "طرح وضعية مشكلة دالة حول «{$topic}» لاسترجاع المكتسبات القبلية ووضع المتعلم في صلب الإشكالية وصياغة الفرضيات.",
                ],
                [
                    'name' => 'بناء التعلمات (النشاط الأول: البحث والاستكشاف والتحليل)',
                    'duration' => 20,
                    'strategy' => 'التفكير — المشاركة — العرض (عمل في مجموعات)',
                    'teacher_role' => 'تقديم السندات والتعليمات بدقة، تيسير العمل الفوجي ومرافقة التلاميذ لرصد الصعوبات وتوجيه المناقشة.',
                    'student_role' => 'تحليل السندات والتناظر داخل الفوج، تفكيك عناصر الإشكالية واستخلاص المفاهيم والعلاقات الأساسية.',
                    'written_trace' => 'استخلاص العناصر الأساسية للنشاط وتدوين النتائج الجزئية المتوصل إليها.',
                    'evaluation_indicator' => 'يحلل السند ويوظف أدوات ومصطلحات المادة بدقة ويتعاون بفاعلية مع أقرانه.',
                    'content' => "ملاحظة السندات واستنتاج المفاهيم الرئيسية حول «{$topic}»، وتدوين الحلول والنتائج في أفواج عمل.",
                ],
                [
                    'name' => 'بناء التعلمات (النشاط الثاني: التركيب والتعميم وإرساء المورد)',
                    'duration' => 15,
                    'strategy' => 'الخرائط المفاهيمية والذهنية (إرساء الموارد)',
                    'teacher_role' => 'قيادة الحوار نحو تركيب التعلمات وتصويب المفاهيم، والإشراف على بناء الخلاصة والقاعدة المعرفية.',
                    'student_role' => 'المشاركة في هيكلة المعارف ومقارنة الفرضيات الأولية بالنتائج المثبتة وصياغة الخلاصة التركيبية.',
                    'written_trace' => 'إرساء المورد: تدوين القاعدة / النتيجة المهيكلة والمخطط المفاهيمي في كراس الدروس.',
                    'evaluation_indicator' => 'يميز بين المفاهيم بدقة ويصوغ استنتاجاً سليماً يثبته في الأثر الكتابي المنظم.',
                    'content' => 'تركيب التعلمات المستخلصة وهيكلة المورد المعرفي في خلاصة واضحة يدونها التلميذ على كراسه.',
                ],
                [
                    'name' => 'استثمار المكتسبات والتقويم (وضعية إدماج جزئي)',
                    'duration' => 15,
                    'strategy' => 'بطاقة الخروج (عمل فردي موجه)',
                    'teacher_role' => 'اقتراح وضعية تطبيقية جزئية هادفة، متابعة الإنجاز الفردي ورصد التعثرات لتقديم المعالجة البيداغوجية الفورية.',
                    'student_role' => 'حل الوضعية التطبيقية فردياً لتوظيف الموارد المكتسبة، التحقق الذاتي، والمشاركة في التصحيح النموذجي.',
                    'written_trace' => 'حل الوضعية التطبيقية وتسليم بطاقة التحقق والتقويم الفردي.',
                    'evaluation_indicator' => 'يوظف المورد المعرفي بنجاح في سياق جديد ويحقق معايير الوجاهة والإتقان.',
                    'content' => 'إنجاز تطبيق فردي أو جماعي للتأكد من التحكم في المعارف مع تقديم تغذية راجعة فورية وتصحيح جماعي.',
                ],
            ];
        }

        $competence = trim((string) ($plan['competence'] ?? ''));
        if ($competence === '') {
            $competence = "يتحكم التلميذ في المفاهيم الأساسية لدرس «{$topic}» ويوظفها بكفاءة في حل وضعيات بيداغوجية مناسبة لمادة {$subject}.";
        }

        $startingSituation = trim((string) ($plan['starting_situation'] ?? ''));
        if ($startingSituation === '') {
            $startingSituation = "انطلاقاً من سياق دال ومكتسبات التلاميذ القبلية، يُطرح التساؤل الإشكالي حول «{$topic}» لتحفيز التفكير وبناء الحل.";
        }

        $resources = trim((string) ($plan['resources'] ?? ''));
        if ($resources === '') {
            $resources = 'الكتاب المدرسي، المنهاج الرسمي، السبورة، عارض البيانات (Data Show)، بطاقات الأنشطة الفوجية.';
        }

        $evaluation = trim((string) ($plan['evaluation'] ?? ''));
        if ($evaluation === '') {
            $evaluation = 'تقويم تكويني مرحلي من خلال متابعة إنجاز الأنشطة، وتقويم ختامي بحل تمرين تطبيقي فردي.';
        }

        $notes = trim((string) ($plan['notes'] ?? ''));
        if ($notes === '') {
            $notes = 'مراعاة الفروق الفردية بين المتعلمين، تشجيع المبادرة الذاتية، والحرص على تدبير التوقيت المخصص لكل مرحلة.';
        }

        return [
            'competence' => $competence,
            'starting_situation' => $startingSituation,
            'activities' => $activities,
            'resources' => $resources,
            'evaluation' => $evaluation,
            'notes' => $notes,
        ];
    }

    /**
     * Gemini structured-output schema matching the lesson plan contract.
     *
     * @return array<string, mixed>
     */
    protected function planSchema(string $locale): array
    {
        [$competenceDescription, $phasesDescription, $strategyDescription] = match ($locale) {
            'en' => [
                'Target competence in one sentence.',
                'Lesson stages in chronological order.',
                'Active learning strategy applied in this stage (e.g. Think-Pair-Share, Exit Ticket, Jigsaw).',
            ],
            'fr' => [
                'Compétence visée en une phrase.',
                'Étapes de la leçon dans l’ordre chronologique.',
                'Stratégie d’apprentissage actif appliquée à cette étape (ex. Jigsaw, Billet de sortie).',
            ],
            default => [
                'الكفاءة المستهدفة في جملة واحدة.',
                'مراحل سير الدرس بالترتيب الزمني.',
                'استراتيجية التعلم النشط المطبقة في هذه المرحلة (مثل: التفكير — المشاركة — العرض، بطاقة الخروج).',
            ],
        };

        return [
            'type' => 'OBJECT',
            'properties' => [
                'competence' => [
                    'type' => 'STRING',
                    'description' => $competenceDescription,
                ],
                'lesson_phases' => [
                    'type' => 'ARRAY',
                    'description' => $phasesDescription,
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'phase_name' => ['type' => 'STRING'],
                            'strategy' => [
                                'type' => 'STRING',
                                'description' => $strategyDescription,
                            ],
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
     * @return array{competence: string, lesson_phases: list<array{phase_name: string, strategy: ?string, content: string}>}
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
            $strategy = trim((string) ($phase['strategy'] ?? $phase['active_learning_strategy'] ?? ''));
            $content = trim((string) ($phase['content'] ?? ''));

            if ($name === '' || $content === '') {
                continue;
            }

            $lessonPhases[] = [
                'phase_name' => $name,
                'strategy' => $strategy !== '' ? $strategy : null,
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
