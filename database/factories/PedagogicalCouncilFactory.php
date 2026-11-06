<?php

namespace Database\Factories;

use App\Enums\PedagogicalCouncilStatus;
use App\Enums\PedagogicalCouncilType;
use App\Models\PedagogicalCouncil;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PedagogicalCouncil>
 */
class PedagogicalCouncilFactory extends Factory
{
    protected $model = PedagogicalCouncil::class;

    public function definition(): array
    {
        return [
            'academic_year' => '2026/2027',
            'subject_id' => Subject::factory(),
            'council_type' => PedagogicalCouncilType::SubjectCouncil,
            'session_term' => 1,
            'title' => 'مجلس التعليم لمادة اللغة العربية',
            'held_at' => now(),
            'location' => 'قاعة الأساتذة',
            'coordinator_id' => User::factory(),
            'agenda' => 'دراسة التوجيهات وضبط التدرج البيداغوجي وتوحيد الفروض.',
            'curriculum_pacing_notes' => 'سير الدروس متوافق مع المخطط الوزاري.',
            'unified_assessments_agreement' => 'فروض موحدة للفصل الأول في الأسبوع السادس.',
            'recommendations' => 'تكثيف حصص المعالجة البيداغوجية واستغلال المخابر.',
            'pv_reference' => 'PV-CE-ARA-2026-'.fake()->unique()->numerify('###'),
            'status' => PedagogicalCouncilStatus::Completed,
        ];
    }
}

