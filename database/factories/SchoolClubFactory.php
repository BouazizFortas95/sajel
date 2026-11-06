<?php

namespace Database\Factories;

use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Enums\DayOfWeek;
use App\Models\SchoolClub;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClubFactory extends Factory
{
    protected $model = SchoolClub::class;

    public function definition(): array
    {
        $type = fake()->randomElement(ClubType::cases());
        $names = [
            ClubType::Scientific->value => 'نادي الروبوتيك والابتكار العلمي',
            ClubType::Environmental->value => 'النادي الأخضر وحماية البيئة المدرسية',
            ClubType::Cultural->value => 'نادي الفنون والمسرح المدرسي',
            ClubType::Reading->value => 'نادي الفكر والقراءة والمطالعة',
            ClubType::Sports->value => 'الجمعية الرياضية والنشاط البدني',
            ClubType::Citizenship->value => 'نادي حقوق الطفل والمواطنة الفاعلة',
            ClubType::Other->value => 'نادي الإعلام والتوثيق المدرسي',
        ];

        return [
            'name' => $names[$type->value] ?? 'نادي الإبداع المدرسي',
            'code' => 'CLUB-'.fake()->unique()->bothify('##??'),
            'type' => $type,
            'academic_year' => '2026-2027',
            'description' => 'فضاء تربوي تفاعلي لصقل مواهب التلاميذ وتنمية مهاراتهم الإبداعية.',
            'objectives' => 'ترسيخ العمل الجماعي، تنمية التفكير النقدي، والمشاركة في الأنشطة الثقافية.',
            'lead_teacher_id' => User::factory()->teacher(),
            'assistant_teacher_id' => null,
            'meeting_day' => fake()->randomElement([DayOfWeek::Tuesday, DayOfWeek::Thursday]),
            'meeting_time' => '16:30 - 17:30',
            'meeting_location' => 'مخبر الإعلام الآلي / قاعة الأنشطة',
            'max_members' => 30,
            'status' => ClubStatus::Active,
            'color' => 'emerald',
        ];
    }

    public function environmental(): static
    {
        return $this->state(fn (): array => [
            'name' => 'النادي الأخضر والبيئة المدرسية',
            'type' => ClubType::Environmental,
            'color' => 'emerald',
        ]);
    }

    public function scientific(): static
    {
        return $this->state(fn (): array => [
            'name' => 'نادي الذكاء الاصطناعي والروبوتيك',
            'type' => ClubType::Scientific,
            'color' => 'cyan',
        ]);
    }
}
