<?php

namespace Database\Seeders;

use App\Models\ActiveLearningStrategy;
use Illuminate\Database\Seeder;

class ActiveLearningStrategySeeder extends Seeder
{
    public function run(): void
    {
        $strategies = [
            [
                'name' => 'التفكير — المشاركة — العرض',
                'slug' => 'think-pair-share',
                'description' => 'يُطلب من التلاميذ التفكير فردياً، ثم المناقشة مع زميل، ثم عرض الأفكار على المجموعة.',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'color' => 'primary',
                'tags' => ['collaborative', 'discussion'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'بطاقة الخروج',
                'slug' => 'exit-ticket',
                'description' => 'في نهاية الحصة، يُجيب كل تلميذ على سؤال سريع لتقييم الفهم.',
                'icon' => 'heroicon-o-ticket',
                'color' => 'warning',
                'tags' => ['assessment', 'individual'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'الجِيجسو',
                'slug' => 'jigsaw',
                'description' => 'تقسيم المحتوى إلى أقسام، ويصبح كل تلميذ خبيراً في قسم، ثم يُعلّم زملاءه.',
                'icon' => 'heroicon-o-puzzle-piece',
                'color' => 'success',
                'tags' => ['collaborative', 'peer-teaching'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'المشي في المعرض',
                'slug' => 'gallery-walk',
                'description' => 'يُعرض عمل المجموعات على الجدران، ويزور التلاميذ كل عرض ويُعلّقون.',
                'icon' => 'heroicon-o-photo',
                'color' => 'info',
                'tags' => ['presentation', 'collaborative'],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'القرعة العشوائية',
                'slug' => 'random-picker',
                'description' => 'اختيار عشوائي لتلاميذ للمشاركة أو الإجابة دون تكرار في الجولة.',
                'icon' => 'heroicon-o-arrows-pointing-out',
                'color' => 'warning',
                'tags' => ['engagement', 'fairness'],
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'التعليم بالأقران',
                'slug' => 'peer-instruction',
                'description' => 'تلاميذ يشرحون لبعضهم البعض بعد التفكير الفردي الأول.',
                'icon' => 'heroicon-o-user-group',
                'color' => 'primary',
                'tags' => ['collaborative', 'peer-teaching'],
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'الخريطة المفاهيمية',
                'slug' => 'concept-map',
                'description' => 'رسم بصري للعلاقات بين المفاهيم لتنظيم الفهم.',
                'icon' => 'heroicon-o-map',
                'color' => 'info',
                'tags' => ['visual', 'individual'],
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'لعب الأدوار',
                'slug' => 'role-play',
                'description' => 'يتخذ التلاميذ أدواراً مختلفة لتطبيق المفاهيم في سياق واقعي.',
                'icon' => 'heroicon-o-user-circle',
                'color' => 'success',
                'tags' => ['simulation', 'collaborative'],
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        $timestamp = now();
        $strategies = array_map(
            static fn (array $strategy): array => [
                ...$strategy,
                'tags' => json_encode($strategy['tags'], JSON_THROW_ON_ERROR),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            $strategies,
        );

        ActiveLearningStrategy::query()->upsert(
            $strategies,
            ['slug'],
            ['name', 'description', 'icon', 'color', 'tags', 'is_active', 'sort_order', 'updated_at'],
        );
    }
}
