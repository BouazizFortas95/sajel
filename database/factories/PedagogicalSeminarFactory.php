<?php

namespace Database\Factories;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use App\Models\PedagogicalSeminar;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PedagogicalSeminar>
 */
class PedagogicalSeminarFactory extends Factory
{
    protected $model = PedagogicalSeminar::class;

    public function definition(): array
    {
        return [
            'academic_year' => '2026/2027',
            'subject_id' => Subject::factory(),
            'title' => 'المقاربة بالكفاءات وبناء وضعيات الإدماج',
            'inspector_name' => 'أ. عبد القادر بن سالم',
            'host_school' => 'متوسطة الشهيد العربي بن مهيدي',
            'scheduled_at' => now()->addDays(5),
            'seminar_type' => SeminarType::InspectoralSeminar,
            'target_teachers_description' => 'كافة أساتذة المادة بالمقاطعة',
            'animator_teacher_id' => User::factory(),
            'objectives' => 'تمكين الأساتذة من بناء وضعيات تقويمية مدمجة وإعداد شبكات الملاحظة والتقييم.',
            'recommendations_summary' => 'التركيز على المعايير الدنيا ومؤشرات الكفاءة.',
            'official_call_number' => '2026/م.ت/'.fake()->unique()->numerify('###'),
            'status' => SeminarStatus::Planned,
        ];
    }
}

