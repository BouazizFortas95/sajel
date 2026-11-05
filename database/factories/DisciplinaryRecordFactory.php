<?php

namespace Database\Factories;

use App\Enums\DisciplinarySanction;
use App\Enums\DisciplinaryStatus;
use App\Enums\InfractionDegree;
use App\Models\DisciplinaryRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisciplinaryRecord>
 */
class DisciplinaryRecordFactory extends Factory
{
    protected $model = DisciplinaryRecord::class;

    public function definition(): array
    {
        $degree = fake()->randomElement(InfractionDegree::cases());
        $status = fake()->randomElement([DisciplinaryStatus::Scheduled, DisciplinaryStatus::Deliberated]);
        $incidents = [
            'عرقلة السير العادي للحصة الدراسية والتلفظ بعبارات غير لائقة تجاه الأستاذ.',
            'المشاركة في شجار عنيف بساحة المؤسسة والتسبب في أضرار مادية بباب القاعة.',
            'تكرار التأخرات والغيابات غير المبررة مع مغادرة المؤسسة دون ترخيص إداري.',
            'إتلاف التجهيزات المخبرية وتكسير زجاج نافذة القاعة عمداً.',
            'حيازة أشياء ممنوعة داخل المؤسسة والتشويش المستمر على الزملاء.',
        ];

        return [
            'student_id' => Student::factory(),
            'academic_year' => '2026/2027',
            'session_number' => fake()->numerify('ج.ت/##/2026'),
            'incident_date' => fake()->dateTimeBetween('2026-10-01', '2026-10-25')->format('Y-m-d'),
            'session_date' => fake()->dateTimeBetween('2026-10-26', '2026-11-15')->format('Y-m-d 10:00:00'),
            'infraction_degree' => $degree,
            'incident_description' => fake()->randomElement($incidents),
            'reported_by_id' => User::factory()->teacher(),
            'status' => $status,
            'summons_sent_at' => fake()->dateTimeBetween('2026-10-20', '2026-10-25')->format('Y-m-d'),
            'guardian_attended' => fake()->boolean(80),
            'student_statement' => 'اعترف التلميذ بالخطأ وقدّم اعتذاراً خطياً للمجلس متعهداً بعدم تكرار السلوك.',
            'decision' => $status === DisciplinaryStatus::Deliberated ? fake()->randomElement(DisciplinarySanction::cases()) : null,
            'exclusion_days' => 3,
            'exclusion_start_date' => '2026-11-01',
            'exclusion_end_date' => '2026-11-03',
            'compensation_amount' => 1500.00,
            'guardian_notified_at' => '2026-11-04',
            'attendees' => [
                'الرئيس: مدير المؤسسة',
                'المستشار الرئيسي للتربية',
                'ناظر الدروس',
                'أستاذ(ة) المادة الممثل',
                'مندوب جمعية أولياء التلاميذ',
            ],
            'notes' => 'جلسة انضباطية رسمية وفق النظام الداخلي للمؤسسة.',
            'president_id' => User::factory()->admin(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => DisciplinaryStatus::Scheduled,
            'decision' => null,
            'exclusion_days' => null,
            'exclusion_start_date' => null,
            'exclusion_end_date' => null,
            'compensation_amount' => null,
            'guardian_notified_at' => null,
        ]);
    }

    public function deliberated(DisciplinarySanction $sanction = DisciplinarySanction::TemporaryExclusion): static
    {
        return $this->state(fn (): array => [
            'status' => DisciplinaryStatus::Deliberated,
            'decision' => $sanction,
            'exclusion_days' => $sanction === DisciplinarySanction::TemporaryExclusion ? 3 : null,
            'exclusion_start_date' => $sanction === DisciplinarySanction::TemporaryExclusion ? '2026-11-01' : null,
            'exclusion_end_date' => $sanction === DisciplinarySanction::TemporaryExclusion ? '2026-11-03' : null,
            'guardian_notified_at' => now()->toDateString(),
        ]);
    }
}

