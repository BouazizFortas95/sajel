<?php

namespace Database\Factories;

use App\Enums\OrientationAppealStatus;
use App\Enums\OrientationStream;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentOrientation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentOrientation>
 */
class StudentOrientationFactory extends Factory
{
    protected $model = StudentOrientation::class;

    public function definition(): array
    {
        $sci = fake()->randomFloat(2, 8, 18);
        $lit = fake()->randomFloat(2, 8, 18);
        $avg = round(($sci + $lit) / 2, 2);

        return [
            'student_id' => Student::factory(),
            'academic_year' => SchoolSetting::currentAcademicYear(),
            'first_choice' => OrientationStream::ScienceTech,
            'second_choice' => OrientationStream::ArtsHumanities,
            'guardian_approved' => true,
            'guardian_notes' => null,
            'pre_orientation_choice' => OrientationStream::ScienceTech,
            'pre_orientation_notes' => 'الملمح العلمي مناسب وملاحظات إيجابية من الأساتذة',
            'scientific_profile_score' => $sci,
            'literary_profile_score' => $lit,
            'annual_general_average' => $avg,
            'bem_score' => $avg + fake()->randomFloat(2, -1, 1),
            'admission_average' => $avg,
            'is_admitted' => $avg >= 10.0,
            'final_decision' => OrientationStream::ScienceTech,
            'deliberated_at' => now(),
            'counselor_notes' => 'توجيه مطابق لرغبة التلميذ ولملمحه البيداغوجي',
            'appeal_status' => OrientationAppealStatus::None,
            'appeal_decision' => null,
            'appeal_notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'final_decision' => null,
            'deliberated_at' => null,
            'is_admitted' => false,
            'counselor_notes' => null,
        ]);
    }

    public function appealed(): static
    {
        return $this->state(fn () => [
            'appeal_status' => OrientationAppealStatus::Submitted,
            'appeal_notes' => 'الولي يطالب بإعادة التوجيه نحو الجذع المشترك آداب',
        ]);
    }

    public function appealAccepted(): static
    {
        return $this->state(fn () => [
            'appeal_status' => OrientationAppealStatus::Accepted,
            'appeal_decision' => OrientationStream::ArtsHumanities,
            'appeal_notes' => 'وافقت اللجنة الولائية للطعن على إعادة التوجيه بناء على نتائج الفصل الثالث',
        ]);
    }
}
