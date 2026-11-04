<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\InspectionReport;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InspectionReportFactory extends Factory
{
    protected $model = InspectionReport::class;

    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'inspector_name' => 'أ. عبد الرحمن بلقاسم',
            'inspector_role' => 'inspector',
            'subject_id' => Subject::factory(),
            'classroom_id' => Classroom::factory(),
            'schedule_id' => null,
            'daily_log_id' => null,
            'curriculum_resource_id' => null,
            'visit_date' => now()->subDays(rand(1, 30))->toDateString(),
            'lesson_topic' => 'بناء التعلمات والمفاهيم الرياضية الأساسية',
            'evaluation_criteria' => [
                'planning' => ['score' => 4.5, 'max' => 5.0, 'notes' => 'تحضير جيد ومذكرة متناسقة مع المنهاج'],
                'management' => ['score' => 4.0, 'max' => 5.0, 'notes' => 'انضباط ممتاز وإشراك فعال للتلاميذ'],
                'resources' => ['score' => 4.0, 'max' => 5.0, 'notes' => 'استثمار سليم للسبورة والوسائل البيداغوجية'],
                'assessment' => ['score' => 3.5, 'max' => 5.0, 'notes' => 'تقويم تشخيصي وتكويني مواكب للدرس'],
            ],
            'strengths' => 'التمكن المعرفي الجيد، وضوح الإلقاء، إشراك مختلف فئات التلاميذ، احترام مراحل بناء الدرس.',
            'areas_for_improvement' => 'تنويع وضعيات الانطلاق، تكثيف العمل الفوجي، وإتاحة وقت أطول للتقويم الختامي.',
            'pedagogical_score' => 16.0,
            'pedagogical_appreciation' => 'very_good',
            'inspector_signature_date' => now()->toDateString(),
            'teacher_acknowledged_at' => now(),
        ];
    }
}
