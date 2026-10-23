<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\PedagogicalMemo;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PedagogicalMemo>
 */
class PedagogicalMemoFactory extends Factory
{
    protected $model = PedagogicalMemo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_id' => Subject::factory(),
            'level_id' => Level::factory(),
            'title' => fake()->randomElement([
                'مذكرة الأعداد الطبيعية',
                'مذكرة الكسور',
                'مذكرة القراءة التحليلية',
                'مذكرة التفاعلات الكيميائية',
            ]),
            'phase' => fake()->randomElement([
                'المقطع الأول',
                'المقطع الثاني',
                'المقطع الثالث',
            ]),
            'competence' => 'يحلّ التلميذ وضعيات مشكلة باستعمال الموارد المعرفية المكتسبة.',
            'starting_situation' => 'وضعية انطلاقية مستمدة من الحياة اليومية تسمح بتوظيف المكتسبات السابقة.',
            'activities' => [
                [
                    'name' => 'وضعية الانطلاق',
                    'duration' => 5,
                    'strategy' => 'حوار موجه',
                    'content' => 'طرح الوضعية الانطلاقية وتحديد المشكلة التي سيتم حلها.',
                ],
                [
                    'name' => 'بناء التعلمات',
                    'duration' => 20,
                    'strategy' => 'عمل في أفواج',
                    'content' => 'استكشاف الأمثلة، صياغة القاعدة، ثم مقارنة النتائج.',
                ],
                [
                    'name' => 'التقويم',
                    'duration' => 10,
                    'strategy' => 'عمل فردي',
                    'content' => 'حل تمرين تطبيقي قصير والتحقق من صحة النتائج.',
                ],
            ],
            'resources' => 'الكتاب المدرسي، السبورة، بطاقات الأنشطة، وثائق داعمة.',
            'evaluation' => 'تمرين تطبيقي فردي مع تصحيح جماعي.',
            'notes' => 'مراعاة الفروق الفردية وتقديم دعم للتلاميذ المتعثرين.',
            'is_template' => false,
            'is_archived' => false,
        ];
    }

    public function template(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_template' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_archived' => true,
        ]);
    }
}
