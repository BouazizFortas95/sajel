<?php

namespace Database\Factories;

use App\Enums\InstitutionalReportStatus;
use App\Models\QuarterlyInstitutionalReport;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuarterlyInstitutionalReport>
 */
class QuarterlyInstitutionalReportFactory extends Factory
{
    protected $model = QuarterlyInstitutionalReport::class;

    public function definition(): array
    {
        $evaluated = fake()->numberBetween(200, 500);
        $passed = (int) round($evaluated * fake()->randomFloat(2, 0.65, 0.90));
        $failing = $evaluated - $passed;

        $excellence = (int) round($passed * 0.15);
        $encouragement = (int) round($passed * 0.25);
        $honor = (int) round($passed * 0.35);
        $passing = $passed - ($excellence + $encouragement + $honor);

        $successRate = round(($passed / $evaluated) * 100, 2);

        return [
            'academic_year' => SchoolSetting::currentAcademicYear(),
            'trimester' => 1,
            'status' => InstitutionalReportStatus::Draft,
            'general_success_rate' => $successRate,
            'evaluated_students_count' => $evaluated,
            'passed_students_count' => $passed,
            'excellence_count' => $excellence,
            'encouragement_count' => $encouragement,
            'honor_count' => $honor,
            'passing_count' => $passing,
            'failing_count' => $failing,
            'total_due_sessions' => fake()->numberBetween(1000, 2500),
            'completed_sessions' => fake()->numberBetween(950, 2400),
            'teacher_absence_hours' => fake()->numberBetween(10, 80),
            'teacher_substitution_hours' => fake()->numberBetween(8, 75),
            'student_absence_records' => fake()->numberBetween(50, 300),
            'disciplinary_councils_count' => fake()->numberBetween(1, 5),
            'counseling_sessions_count' => fake()->numberBetween(10, 45),
            'level_breakdown' => [
                '1AM' => ['level_name' => '1 متوسط', 'evaluated' => 80, 'passed' => 65, 'success_rate' => 81.25, 'average' => 12.4],
                '2AM' => ['level_name' => '2 متوسط', 'evaluated' => 75, 'passed' => 58, 'success_rate' => 77.33, 'average' => 11.9],
                '3AM' => ['level_name' => '3 متوسط', 'evaluated' => 70, 'passed' => 52, 'success_rate' => 74.28, 'average' => 11.5],
                '4AM' => ['level_name' => '4 متوسط', 'evaluated' => 65, 'passed' => 55, 'success_rate' => 84.61, 'average' => 12.8],
            ],
            'subject_breakdown' => [
                ['subject' => 'اللغة العربية', 'average' => 13.5, 'passing_rate' => 88.0],
                ['subject' => 'الرياضيات', 'average' => 11.2, 'passing_rate' => 68.0],
                ['subject' => 'العلوم الفيزيائية والتكنولوجيا', 'average' => 12.1, 'passing_rate' => 74.0],
            ],
            'executive_summary' => 'أظهرت نتائج الثلاثي استقراراً بيداغوجياً إيجابياً مع ضرورة تكثيف حصص المعالجة في مادتي الرياضيات واللغة الفرنسية.',
            'pedagogical_recommendations' => 'تفعيل الزيارات التبادلية بين الأساتذة والتركيز على استغلال مخابر العلوم والإعلام الآلي.',
            'validated_by' => null,
            'validated_at' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InstitutionalReportStatus::Validated,
            'validated_by' => User::factory(),
            'validated_at' => now(),
        ]);
    }
}
