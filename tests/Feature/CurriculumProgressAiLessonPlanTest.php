<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\CurriculumProgress;
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
}
