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
                    'description' => 'Target competence in one clear, concise sentence (max 25 words).',
                ],
                'starting_situation' => [
                    'type' => 'STRING',
                    'description' => 'Short starting problem situation (context and guiding question, max 40 words).',
                ],
                'activities' => [
                    'type' => 'ARRAY',
                    'description' => 'Chronological 3 official lesson phases: 1) وضعية الانطلاق (10 min), 2) العرض وإرساء الموارد (35 min), 3) التقويم والمعالجة (15 min).',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name' => [
                                'type' => 'STRING',
                                'description' => 'Phase name (وضعية الانطلاق / العرض - إرساء الموارد / التقويم والمعالجة)',
                            ],
                            'duration' => [
                                'type' => 'INTEGER',
                                'description' => 'Duration in minutes (10, 35, or 15)',
                            ],
                            'strategy' => [
                                'type' => 'STRING',
                                'description' => 'Active learning strategy (e.g. الحوار الموجه / التفكير-المشاركة-العرض / بطاقة الخروج)',
                            ],
                            'teacher_role' => [
                                'type' => 'STRING',
                                'description' => 'Teacher instructions and guiding questions (max 2 short sentences).',
                            ],
                            'student_role' => [
                                'type' => 'STRING',
                                'description' => 'Learner activity and tasks (max 2 short sentences).',
                            ],
                            'written_trace' => [
                                'type' => 'STRING',
                                'description' => 'Concise bullet points of knowledge trace for board and notebook (max 4 short bullets, NO long paragraphs).',
                            ],
                            'evaluation_indicator' => [
                                'type' => 'STRING',
                                'description' => 'Observable behavioral indicator in one concise sentence (max 15 words).',
                            ],
                            'content' => [
                                'type' => 'STRING',
                                'description' => 'Brief overview of the activity (max 30 words).',
                            ],
                        ],
                        'required' => ['name', 'content'],
                    ],
                ],
                'resources' => [
                    'type' => 'STRING',
                    'description' => 'Short list of used tools (الكتاب، السبورة، الحواسيب، الداتاشو)',
                ],
                'evaluation' => [
                    'type' => 'STRING',
                    'description' => 'Short practical evaluation exercise (max 25 words).',
                ],
                'notes' => [
                    'type' => 'STRING',
                    'description' => 'Brief pedagogical references (المنهاج، الوثيقة المرافقة)',
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
        أنت مفتش تربوي متخصص في مناهج وزارة التربية الوطنية الجزائرية (التعليم المتوسط — المقاربة بالكفاءات ومنهاج الجيل الثاني).
        المطلوب تحرير مذكرة بيداغوجية رسمية متكاملة ومنهجية حديثة لمادة «{$subject}» — المستوى «{$level}» — المقطع التعلمي: «{$phase}» حول موضوع الدرس: «{$topic}».

        تنبيه بيداغوجي صارم لمنع الإطناب والتكرار:
        - المذكرة بطاقة تقنية إجرائية موجزة وعملية للأستاذ، وليست مقالاً إنشائياً.
        - اعتمد أسلوب رؤوس الأقلام والنقاط التلغرافية المركزة والمحددة (1، 2، 3).
        - ممنوع منعاً باتاً تكرار العبارات أو حشو الكلام أو العبارات العامة والشعارات.

        هيكلة سيرورة الدرس (الحصة 60 دقيقة موزعة على 3 محطات رسمية حصراً):
        1. وضعية الانطلاق (10 د):
           - سياق مشكلة واقعي وموجز (سطران أو ثلاثة) ينتهي بتساؤل إشكالي دقيق.
           - الأثر الأولي: تدوين الإشكالية والفرضيات الأولية على السبورة.
        2. العرض (إرساء الموارد) (35 د):
           - النشاط والتعليمات: نص النشاط التفاعلي والمهمة المطلوبة من الأفواج (سطران).
           - الأثر الكتابي (written_trace): إرساء المورد المعرفي في نقاط تلغرافية مهيكلة ومخطط سيرورة رقمي/مفاهيمي بسيط يسهل نقله على كراس الدروس (أقصى حد 5 أسطر).
           - مؤشر الكفاءة: مؤشر سلوكي دقيق وقابل للملاحظة.
        3. التقويم والمعالجة (15 د):
           - تمرين تطبيقي عملي محدد (مثل: تصنيف عناصر، إكمال مخطط، أو حل تمرين وجيز).
           - المعالجة الفورية: آلية التصويب وتثبيت الحل النموذجي.

        {$strategyPart}
        {$customPart}

        التعليمات:
        أعد النتيجة على شكل كائن JSON صالح فقط، دون أي شرح أو وسوم markdown، بالبنية التالية:
        {
          "competence": "الكفاءة المستهدفة في جملة إجرائية دقيقة وموجزة (أقل من 20 كلمة)",
          "starting_situation": "سياق المشكلة والتساؤل الإشكالي (سطران إلى 3 أسطر فقط)",
          "activities": [
            {
              "name": "وضعية الانطلاق والتهيئة النفسية",
              "duration": 10,
              "strategy": "العصف الذهني والحوار الموجه",
              "teacher_role": "طرح الوضعية وتوجيه الأسئلة واستثارة الدافعية (سطران)",
              "student_role": "الملاحظة والتعبير الحر واقتراح الفرضيات (سطران)",
              "written_trace": "تدوين الإشكالية والفرضيات الأولية المقترحة على السبورة",
              "evaluation_indicator": "يستحضر المكتسبات القبلية ذات الصلة ويصوغ فرضيات منطقية",
              "content": "طرح وضعية مشكلة دالة حول موضوع الدرس لاسترجاع المكتسبات القبلية"
            },
            {
              "name": "العرض (إرساء الموارد وبناء التعلمات)",
              "duration": 35,
              "strategy": "التفكير — المشاركة — العرض",
              "teacher_role": "تقديم السندات وإلقاء التعليمات وتيسير المناقشة الفوجية",
              "student_role": "تحليل السند والتناظر داخل الفوج واستخلاص القواعد والمفاهيم",
              "written_trace": "1- ضبط المفهوم الأساسي\\n2- الخصائص والمكونات\\n3- مخطط السيرورة",
              "evaluation_indicator": "يحلل ويوظف أدوات ومصطلحات المادة بدقة ويستنتج القاعدة",
              "content": "تنفيذ نشاط تعلمي تفاعلي واستخلاص المفاهيم الأساسية وإرساء المورد المعرفي"
            },
            {
              "name": "التقويم والمعالجة (استثمار المكتسبات)",
              "duration": 15,
              "strategy": "بطاقة الخروج (عمل فردي موجه)",
              "teacher_role": "طرح تمرين تطبيقي محدد ومتابعة الحل وتقديم التغذية الراجعة",
              "student_role": "حل الوضعية التطبيقية فردياً والتحقق من صحة الإجابة",
              "written_trace": "تسجيل الحل النموذجي للتمرين التطبيقي في كراس الدروس",
              "evaluation_indicator": "يوظف المورد المكتسب بنجاح في حل التمرين التطبيقي",
              "content": "تطبيق فوري لتقويم التحكم في المورد وتصويب التعثرات الفردية فورياً"
            }
          ],
          "resources": "الوسائل والسندات (الكتاب، السبورة، عارض البيانات، الحواسيب)",
          "evaluation": "تمرين تطبيقي فوري محدد لتقويم التحكم في المورد المعرفي",
          "notes": "المنهاج الرسمي، الوثيقة المرافقة، مراعاة الفروق الفردية"
        }
        PROMPT;
    }

    /**
     * Clean up repeating hallucinations, infinite loops, and enforce concise length limits.
     */
    protected function sanitizeMemoText(?string $text, int $maxChars = 400): string
    {
        if (blank($text)) {
            return '';
        }

        $clean = trim((string) $text);

        // Remove immediate repetition loops of phrases (10-80 chars) repeating 2+ times
        $clean = preg_replace('/(\b.{10,80}\b)(?:\s*\1){2,}/u', '$1', $clean) ?? $clean;

        // Strip hallucinated religious/patriotic looping filler
        $patterns = [
            '/(?:دائماً وأبداً\s*)+(?:آم[يـ]ن يا رب العالمين)?/u',
            '/(?:بكل فخر واعتزاز|لا يتزعزع أبداً|عبر ربوع الوطن الحبيب والغالي|في متوسطات الوطن الحبيب)\s*/u',
            '/(?:وصلى الله وسلم على سيدنا محمد وعلى آله وصحبه وسلم تسليما كثيرا)\s*/u',
            '/(?:والحمد لله رب العالمين)\s*/u',
        ];
        $clean = preg_replace($patterns, ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s{2,}/u', ' ', $clean) ?? $clean;

        return Str::limit(trim($clean), $maxChars, '...');
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

                $name = $this->sanitizeMemoText($act['name'] ?? $act['phase_name'] ?? '', 120);
                $duration = (int) ($act['duration'] ?? 15);
                if ($duration <= 0) {
                    $duration = 15;
                }
                $strategy = $this->sanitizeMemoText($act['strategy'] ?? $act['active_learning_strategy'] ?? '', 120);
                $content = $this->sanitizeMemoText($act['content'] ?? '', 350);

                $teacherRole = $this->sanitizeMemoText($act['teacher_role'] ?? '', 250);
                $studentRole = $this->sanitizeMemoText($act['student_role'] ?? '', 250);
                $writtenTrace = $this->sanitizeMemoText($act['written_trace'] ?? '', 450);
                $evalIndicator = $this->sanitizeMemoText($act['evaluation_indicator'] ?? '', 200);

                if ($content === '' && ($teacherRole !== '' || $studentRole !== '')) {
                    $content = $this->sanitizeMemoText("{$teacherRole}\n{$studentRole}", 350);
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

        $competence = $this->sanitizeMemoText($plan['competence'] ?? '', 200);
        if ($competence === '') {
            $competence = "يتحكم التلميذ في المفاهيم الأساسية لدرس «{$topic}» ويوظفها بكفاءة في حل وضعيات بيداغوجية مناسبة لمادة {$subject}.";
        }

        $startingSituation = $this->sanitizeMemoText($plan['starting_situation'] ?? '', 350);
        if ($startingSituation === '') {
            $startingSituation = "انطلاقاً من سياق دال ومكتسبات التلاميذ القبلية، يُطرح التساؤل الإشكالي حول «{$topic}» لتحفيز التفكير وبناء الحل.";
        }

        $resources = $this->sanitizeMemoText($plan['resources'] ?? '', 250);
        if ($resources === '') {
            $resources = 'الكتاب المدرسي، المنهاج الرسمي، السبورة، عارض البيانات (Data Show)، بطاقات الأنشطة الفوجية.';
        }

        $evaluation = $this->sanitizeMemoText($plan['evaluation'] ?? '', 250);
        if ($evaluation === '') {
            $evaluation = 'تقويم تكويني مرحلي من خلال متابعة إنجاز الأنشطة، وتقويم ختامي بحل تمرين تطبيقي فردي.';
        }

        $notes = $this->sanitizeMemoText($plan['notes'] ?? '', 250);
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
