<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\CurriculumProgress;
use App\Filament\Resources\PedagogicalMemoResource\Pages\CreatePedagogicalMemo;
use App\Filament\Resources\PedagogicalMemoResource\Pages\ListPedagogicalMemos;
use App\Models\ActiveLearningStrategy;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\Level;
use App\Models\PedagogicalMemo;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiLessonPlannerService;
use App\Services\AnnualCurriculumDistributionService;
use App\Services\CurriculumPlanGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CurriculumProgressAiLessonPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('ar');
        config()->set('ai.provider', 'gemini');
        config()->set('ai.gemini.key', 'test-gemini-key');
        config()->set('ai.gemini.model', 'gemini-3.8-flash');
        config()->set('ai.retry_times', 1);
        config()->set('ai.retry_delay', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleAiMemoPlan(): array
    {
        return [
            'competence' => 'يتحكم التلميذ في مفهوم خوارزمية التكرار ويوظفها لحل مسائل برمجية.',
            'starting_situation' => 'طرح إشكالية رسم أشكال هندسية مكررة لاكتشاف الحاجة إلى حلقة التكرار.',
            'activities' => [
                [
                    'name' => 'وضعية الانطلاق',
                    'duration' => 10,
                    'strategy' => 'العصف الذهني المنظم',
                    'content' => 'استدراج التلاميذ عبر أمثلة حية عن التكرار في الحياة اليومية.',
                ],
                [
                    'name' => 'بناء التعلمات',
                    'duration' => 25,
                    'strategy' => 'التفكير — المشاركة — العرض',
                    'content' => 'كتابة برنامج بسيط وتجربته في أفواج ثنائية في قاعة الإعلام الآلي.',
                ],
                [
                    'name' => 'استثمار المكتسبات والتقويم',
                    'duration' => 15,
                    'strategy' => 'بطاقة الخروج',
                    'content' => 'حل مسألة فردية وتدوين الاستنتاج في كراس الدروس.',
                ],
            ],
            'resources' => 'حواسيب المخبر، برمجية سكراتش، السبورة التفاعلية، بطاقات المهام.',
            'evaluation' => 'تقويم تكويني عبر بطاقة ملاحظة الأداء الفوجي، واختبار قصير ختامي.',
            'notes' => 'مراعاة سرعة استيعاب التلاميذ ومرافقة الأفواج التي تواجه صعوبات في البرمجة.',
        ];
    }

    private function fakeGeminiWithPlan(?array $plan = null): void
    {
        $plan ??= $this->sampleAiMemoPlan();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($plan, JSON_UNESCAPED_UNICODE)]]]],
                ],
            ]),
        ]);
    }

    public function test_ai_lesson_planner_service_generates_authentic_pedagogical_memo(): void
    {
        $this->fakeGeminiWithPlan();

        $service = app(AiLessonPlannerService::class);
        $memoData = $service->generatePedagogicalMemo(
            topic: 'خوارزمية التكرار',
            subject: 'الإعلام الآلي',
            level: 'السنة الثالثة متوسط',
            phase: 'المخطط الانسيابي والخوارزميات',
            locale: 'ar',
            preferredStrategy: 'التفكير — المشاركة — العرض',
            customInstructions: 'التركيز على التطبيق العملي في المخبر',
        );

        $this->assertSame('يتحكم التلميذ في مفهوم خوارزمية التكرار ويوظفها لحل مسائل برمجية.', $memoData['competence']);
        $this->assertSame('طرح إشكالية رسم أشكال هندسية مكررة لاكتشاف الحاجة إلى حلقة التكرار.', $memoData['starting_situation']);
        $this->assertCount(3, $memoData['activities']);
        $this->assertSame('وضعية الانطلاق', $memoData['activities'][0]['name']);
        $this->assertSame(10, $memoData['activities'][0]['duration']);
        $this->assertSame('العصف الذهني المنظم', $memoData['activities'][0]['strategy']);
        $this->assertStringContainsString('حواسيب المخبر', $memoData['resources']);
        $this->assertStringContainsString('تقويم تكويني', $memoData['evaluation']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'models/gemini-3.8-flash:generateContent')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'خوارزمية التكرار')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'الإعلام الآلي')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'التركيز على التطبيق العملي في المخبر'));
    }

    public function test_curriculum_progress_supports_annual_and_monthly_views(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'العلوم الفيزيائية']);
        $level = Level::factory()->create(['name' => 'السنة الثانية متوسط']);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '2 م 1']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
        ]);

        $domain = CurriculumDomain::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'name' => 'المادة وتحولاتها',
        ]);

        $sequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $domain->id,
            'name' => 'التحول الفيزيائي والكيميائي',
        ]);

        $resource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
            'name' => 'انحفاظ الكتلة في التحولات',
        ]);

        $this->actingAs($teacher);

        Livewire::test(CurriculumProgress::class)
            ->assertSet('curriculumView', 'annual')
            ->call('setCurriculumView', 'monthly')
            ->assertSet('curriculumView', 'monthly')
            ->call('setCurriculumView', 'annual')
            ->assertSet('curriculumView', 'annual')
            ->assertSee('التدرج السنوي')
            ->assertSee('المخطط الشهري')
            ->assertSee('انحفاظ الكتلة في التحولات')
            ->assertSee('إنجاز مذكرة بالذكاء الاصطناعي');
    }

    public function test_curriculum_progress_generates_and_saves_pedagogical_memo_with_ai(): void
    {
        $this->fakeGeminiWithPlan();

        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1 م 1']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
        ]);

        $domain = CurriculumDomain::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'name' => 'نظام التشغيل والمكتبية',
        ]);

        $sequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $domain->id,
            'name' => 'معالج النصوص',
        ]);

        $resource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
            'name' => 'إدراج وتنسيق الجداول',
        ]);

        ActiveLearningStrategy::factory()->create([
            'name' => 'التفكير — المشاركة — العرض',
            'is_active' => true,
        ]);

        $this->actingAs($teacher);

        $this->assertDatabaseMissing('pedagogical_memos', [
            'user_id' => $teacher->id,
            'title' => 'إدراج وتنسيق الجداول',
        ]);

        Livewire::test(CurriculumProgress::class)
            ->call('openAiMemoModal', $resource->id)
            ->callAction('generateAiMemo', [
                'resource_id' => $resource->id,
                'lesson_title' => 'إدراج وتنسيق الجداول',
                'phase' => 'معالج النصوص',
                'preferred_strategy' => 'التفكير — المشاركة — العرض',
                'custom_instructions' => 'تمرين تطبيقي لإنشاء جدول توقيت أسبوعي',
            ])
            ->assertHasNoActionErrors();

        $memo = PedagogicalMemo::where('user_id', $teacher->id)
            ->where('title', 'إدراج وتنسيق الجداول')
            ->first();

        $this->assertNotNull($memo);
        $this->assertSame($subject->id, $memo->subject_id);
        $this->assertSame($classroom->level_id, $memo->level_id);
        $this->assertSame('معالج النصوص', $memo->phase);
        $this->assertSame('يتحكم التلميذ في مفهوم خوارزمية التكرار ويوظفها لحل مسائل برمجية.', $memo->competence);
        $this->assertIsArray($memo->activities);
        $this->assertCount(3, $memo->activities);
        $this->assertSame('وضعية الانطلاق', $memo->activities[0]['name']);

        // Test the print view renders successfully
        $response = $this->get(route('print.pedagogical-memo', [
            'locale' => 'ar',
            'memo' => $memo->id,
        ]));

        $response->assertOk();
        $response->assertSee('إدراج وتنسيق الجداول');
        $response->assertSee('الكفاءة المستهدفة');
        $response->assertSee('وضعية الانطلاق');
    }

    public function test_pedagogical_memo_resource_generates_memo_with_ai(): void
    {
        $this->fakeGeminiWithPlan();

        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $level = Level::factory()->create(['name' => 'السنة الرابعة متوسط']);

        $domain = CurriculumDomain::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'name' => 'الحساب الحرفي',
        ]);

        $sequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $domain->id,
            'name' => 'المتطابقات الشهيرة',
        ]);

        $resource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
            'name' => 'مربع مجموع ومربع فرق',
        ]);

        $this->actingAs($teacher);

        Livewire::test(ListPedagogicalMemos::class)
            ->callTableAction('generateWithAi', data: [
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'curriculum_resource_id' => $resource->id,
                'title' => 'مربع مجموع ومربع فرق',
                'phase' => 'المتطابقات الشهيرة',
                'preferred_strategy' => null,
                'custom_instructions' => null,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('pedagogical_memos', [
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'title' => 'مربع مجموع ومربع فرق',
            'phase' => 'المتطابقات الشهيرة',
        ]);
    }

    public function test_create_pedagogical_memo_page_form_action_populates_fields_with_ai(): void
    {
        $this->fakeGeminiWithPlan();

        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'العلوم الفيزيائية']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);

        $this->actingAs($teacher);

        Livewire::test(CreatePedagogicalMemo::class)
            ->fillForm([
                'title' => 'الخلائط المتجانسة وغير المتجانسة',
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'phase' => 'المادة وتحولاتها',
            ])
            ->callFormComponentAction('title', 'generateAiContent', data: [
                'preferred_strategy' => null,
                'custom_instructions' => null,
            ])
            ->assertHasNoFormComponentActionErrors()
            ->assertFormSet([
                'competence' => 'يتحكم التلميذ في مفهوم خوارزمية التكرار ويوظفها لحل مسائل برمجية.',
                'starting_situation' => 'طرح إشكالية رسم أشكال هندسية مكررة لاكتشاف الحاجة إلى حلقة التكرار.',
            ]);
    }

    public function test_annual_curriculum_distribution_page_renders_authentic_ministerial_layout(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $this->actingAs($teacher);

        $response = $this->get(route('print.annual-curriculum-distribution', [
            'locale' => 'ar',
        ]));

        $response->assertOk();
        $response->assertSee('التوزيع السنوي للسنة الأولى من التعليم المتوسط');
        $response->assertSee('استخدام الحاسوب');
        $response->assertSee('معالج النصوص');
        $response->assertSee('فرض الثلاثي الأول');
        $response->assertSee('فرض الثلاثي الثاني');
        $response->assertSee('فرض الثلاثي الثالث');
        $response->assertSee('تصحيح الاختبار والتقويم والمعالجة');
        $response->assertSee('عطلة الخريف');
        $response->assertSee('عطلة الشتاء');
        $response->assertSee('عطلة الربيع');
        $response->assertSee('اختبارات الفصل الأول');
        $response->assertSee('اختبارات الفصل الثاني');
        $response->assertSee('اختبارات الفصل الثالث');
        $response->assertSee('أستاذ(ة) المادة');
        $response->assertSee('مدير(ة) المؤسسة');
        $response->assertSee('مفتش(ة) مادة المعلوماتية');
    }

    public function test_annual_curriculum_distribution_pdf_route_streams_response(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $this->actingAs($teacher);

        $response = $this->get(route('print.annual-curriculum-distribution.pdf', [
            'locale' => 'ar',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('annual-curriculum-distribution', (string) $response->headers->get('content-disposition'));
    }

    public function test_annual_curriculum_distribution_supports_all_middle_school_levels(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $this->actingAs($teacher);

        // Level 2 (2AM)
        $res2 = $this->get(route('print.annual-curriculum-distribution', ['locale' => 'ar', 'level' => 2]));
        $res2->assertOk();
        $res2->assertSee('التوزيع السنوي للسنة الثانية من التعليم المتوسط');
        $res2->assertSee('الجداول');
        $res2->assertSee('مدخل إلى سكراتش');
        $res2->assertSee('الشبكة');

        // Level 3 (3AM)
        $res3 = $this->get(route('print.annual-curriculum-distribution', ['locale' => 'ar', 'level' => 3]));
        $res3->assertOk();
        $res3->assertSee('التوزيع السنوي للسنة الثالثة من التعليم المتوسط');
        $res3->assertSee('الأصوات في سكراتش');
        $res3->assertSee('المجدول');
        $res3->assertSee('العروض التقديمية');
        $res3->assertSee('البريد الإلكتروني');

        // Level 4 (4AM)
        $res4 = $this->get(route('print.annual-curriculum-distribution', ['locale' => 'ar', 'level' => 4]));
        $res4->assertOk();
        $res4->assertSee('التوزيع السنوي للسنة الرابعة من التعليم المتوسط');
        $res4->assertSee('المتغيرات في سكراتش');
        $res4->assertSee('المخططات البيانية');
        $res4->assertSee('شهادة BEM');
    }

    public function test_curriculum_progress_page_contains_annual_distribution_print_link(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'المعلوماتية']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1 م 1']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($teacher);

        Livewire::test(CurriculumProgress::class)
            ->assertSee('طباعة التوزيع السنوي')
            ->assertSee('طباعة المخططات الشهرية')
            ->assertSee(route('print.annual-curriculum-distribution', ['locale' => 'ar']));
    }

    public function test_monthly_curriculum_plans_can_be_viewed_and_streamed(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $this->actingAs($teacher);

        // Test monthly view with specific month
        $response = $this->get(route('print.annual-curriculum-distribution', [
            'locale' => 'ar',
            'level' => 1,
            'mode' => 'monthly',
            'month' => '10',
        ]));

        $response->assertOk();
        $response->assertSee('المخطط الشهري للتعلمات');
        $response->assertSee('سبتمبر وأكتوبر');
        $response->assertSee('بيئة التعامل مع الحاسوب');
        $response->assertSee('مفهوم نظام التشغيل');

        // Test monthly PDF route
        $pdfResponse = $this->get(route('print.annual-curriculum-distribution.pdf', [
            'locale' => 'ar',
            'level' => 1,
            'mode' => 'monthly',
            'month' => '10',
        ]));

        $pdfResponse->assertOk();
    }

    public function test_collected_monthly_plan_gathers_all_teacher_active_levels_on_single_page(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value, 'name' => 'فرطاس بوعزيز']);
        $subject = Subject::factory()->create(['name' => 'المعلوماتية']);

        $lvl1 = Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $lvl3 = Level::factory()->create(['name' => 'السنة الثالثة متوسط']);
        $lvl4 = Level::factory()->create(['name' => 'السنة الرابعة متوسط']);

        $c1 = Classroom::factory()->create(['level_id' => $lvl1->id, 'name' => '1 م 1']);
        $c3 = Classroom::factory()->create(['level_id' => $lvl3->id, 'name' => '3 م 1']);
        $c4 = Classroom::factory()->create(['level_id' => $lvl4->id, 'name' => '4 م 1']);

        Schedule::factory()->create(['user_id' => $teacher->id, 'subject_id' => $subject->id, 'classroom_id' => $c1->id]);
        Schedule::factory()->create(['user_id' => $teacher->id, 'subject_id' => $subject->id, 'classroom_id' => $c3->id]);
        Schedule::factory()->create(['user_id' => $teacher->id, 'subject_id' => $subject->id, 'classroom_id' => $c4->id]);

        $this->actingAs($teacher);

        $response = $this->get(route('print.annual-curriculum-distribution', [
            'locale' => 'ar',
            'mode' => 'monthly',
            'month' => '09_10',
            'level' => 'all',
        ]));

        $response->assertOk();
        $response->assertSee('المخطط الشهري للتعلمات');
        $response->assertSee('سبتمبر وأكتوبر');
        // Separated by level banner rows
        $response->assertSee('السنة الأولى من التعليم المتوسط (1AM)');
        $response->assertSee('السنة الثالثة من التعليم المتوسط (3AM)');
        $response->assertSee('السنة الرابعة من التعليم المتوسط (4AM)');

        // Test single A4 page fit
        $service = app(AnnualCurriculumDistributionService::class);
        $data = $service->getDistributionData(
            teacherId: $teacher->id,
            mode: 'monthly',
            month: '09_10',
            level: 'all'
        );
        $pdf = $service->generatePdf($data);
        preg_match_all('/\/Type\s*\/Page[^s]/', $pdf, $matches);
        $this->assertSame(1, count($matches[0]), 'Collected monthly plan for teacher active levels must fit strictly on 1 single A4 page.');
    }

    public function test_annual_curriculum_distribution_pdf_is_single_a4_page_for_levels(): void
    {
        $service = app(AnnualCurriculumDistributionService::class);

        // Test boundary levels: Level 1 and Level 4
        foreach ([1, 4] as $level) {
            $data = $service->getDistributionData(level: $level);
            $pdf = $service->generatePdf($data);
            preg_match_all('/\/Type\s*\/Page[^s]/', $pdf, $matches);
            $this->assertSame(1, count($matches[0]), "Level {$level} PDF must fit strictly on 1 single A4 page.");
        }
    }

    public function test_curriculum_progress_page_auto_seeds_and_displays_informatics_curriculum(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $classroom = Classroom::factory()->create(['level_id' => $level->id, 'name' => '1 م 1']);

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($teacher);

        $this->assertDatabaseMissing('curriculum_domains', [
            'subject_id' => $subject->id,
            'level_id' => $level->id,
        ]);

        Livewire::test(CurriculumProgress::class)
            ->set('subjectId', $subject->id)
            ->set('classroomId', $classroom->id)
            ->assertDontSee('لا يوجد منهاج معرف لهذه المادة والمستوى')
            ->assertSee('استخدام الحاسوب')
            ->assertSee('بيئة التعامل مع الحاسوب')
            ->assertSee('مفهوم نظام التشغيل')
            ->assertSee('معالج النصوص')
            ->assertSee('إنجاز مذكرة بالذكاء الاصطناعي');

        $this->assertDatabaseHas('curriculum_domains', [
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'name' => 'استخدام الحاسوب',
        ]);
    }

    public function test_artisan_seed_curriculum_command_seeds_subjects_and_teachers(): void
    {
        $subject = Subject::firstOrCreate(['name' => 'الرياضيات', 'code' => 'MATH']);
        $teacher = User::factory()->create([
            'role' => Role::Teacher->value,
            'name' => 'أستاذ الرياضيات الجديد',
            'primary_subject_id' => $subject->id,
        ]);

        $this->artisan('sajel:seed-curriculum', [
            '--subject' => 'الرياضيات',
            '--level' => '1',
            '--teacher' => (string) $teacher->id,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('curriculum_domains', [
            'subject_id' => $subject->id,
            'name' => 'أنشطة عددية',
        ]);

        $this->assertDatabaseHas('schedules', [
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_other_teachers_can_generate_and_own_curriculum_plans(): void
    {
        $subject = Subject::firstOrCreate(['name' => 'العلوم الفيزيائية والتكنولوجيا', 'code' => 'PHYS']);
        $teacher = User::factory()->create([
            'role' => Role::Teacher->value,
            'name' => 'أستاذ الفيزياء',
            'primary_subject_id' => $subject->id,
        ]);

        $level = Level::where('name', 'like', '%الأولى%')->first()
            ?? Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $classroom = Classroom::where('level_id', $level->id)->first()
            ?? Classroom::factory()->create(['level_id' => $level->id, 'name' => '1 م 1']);

        Schedule::create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-20',
        ]);

        $this->actingAs($teacher);

        Livewire::test(CurriculumProgress::class)
            ->set('subjectId', $subject->id)
            ->set('classroomId', $classroom->id)
            ->call('generateCurriculumPlan')
            ->assertNotified()
            ->assertSee('المادة وتحولاتها')
            ->assertDontSee('لا يوجد منهاج معرف لهذه المادة والمستوى');

        $this->assertDatabaseHas('curriculum_domains', [
            'subject_id' => $subject->id,
            'name' => 'المادة وتحولاتها',
        ]);
    }

    public function test_annual_and_monthly_distribution_prints_dynamic_subject_data(): void
    {
        $subject = Subject::firstOrCreate(['name' => 'الرياضيات', 'code' => 'MATH']);
        $service = app(AnnualCurriculumDistributionService::class);

        $annualData = $service->getDistributionData(subjectId: $subject->id, level: 1, mode: 'annual');
        $this->assertSame('الرياضيات', $annualData['subject_name']);
        $this->assertStringContainsString('الرياضيات', $annualData['title']);
        $this->assertSame('04 ساعات أسبوعياً', $annualData['weekly_hours']);

        $monthlyData = $service->getDistributionData(subjectId: $subject->id, level: 1, mode: 'monthly', month: '09_10');
        $this->assertSame('الرياضيات', $monthlyData['subject_name']);
        $this->assertNotEmpty($monthlyData['monthly_plans']);
    }

    public function test_informatics_curriculum_generation_does_not_generate_mathematics_domains(): void
    {
        $subject = Subject::firstOrCreate(['name' => 'الإعلام الآلي', 'code' => 'INFO']);
        $generator = app(CurriculumPlanGeneratorService::class);
        $level4 = $generator->findLevelByNumber(4);

        $this->assertNotNull($level4);

        // Pre-create an incorrect math domain to ensure pruning works
        CurriculumDomain::create([
            'subject_id' => $subject->id,
            'level_id' => $level4->id,
            'name' => 'أنشطة عددية',
            'description' => 'حساب الجذور والـ PGCD',
            'sort_order' => 99,
        ]);

        $res = $generator->generateForSubjectAndLevel($subject, $level4, true);

        $this->assertSame('created', $res['status']);
        $this->assertSame(4, $res['domains_count']);

        // Check that Informatics domains exist
        $this->assertDatabaseHas('curriculum_domains', [
            'subject_id' => $subject->id,
            'level_id' => $level4->id,
            'name' => 'بناء المشاريع',
        ]);
        $this->assertDatabaseHas('curriculum_domains', [
            'subject_id' => $subject->id,
            'level_id' => $level4->id,
            'name' => 'المجدول',
        ]);

        // Check that Math domain was pruned and does not exist under Informatics
        $this->assertDatabaseMissing('curriculum_domains', [
            'subject_id' => $subject->id,
            'level_id' => $level4->id,
            'name' => 'أنشطة عددية',
        ]);
    }
}
