<?php

namespace Database\Factories;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubActivityType;
use App\Models\ClubActivity;
use App\Models\SchoolClub;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClubActivityFactory extends Factory
{
    protected $model = ClubActivity::class;

    public function definition(): array
    {
        return [
            'school_club_id' => SchoolClub::factory(),
            'title' => fake()->randomElement([
                'حملة تشجير وغرس الشتلات بفناء المؤسسة',
                'معرض المشاريع والتجارب العلمية المبسطة',
                'المسابقة الفكرية والثقافية بين المستويات',
                'زيارة ميدانية استكشافية إلى دار البيئة والعلوم',
                'الاحتفال باليوم الوطني للعلم (16 أفريل)',
            ]),
            'activity_type' => fake()->randomElement(ClubActivityType::cases()),
            'activity_date' => now()->subDays(fake()->numberBetween(1, 45))->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'فناء المؤسسة / قاعة المحاضرات',
            'objectives' => 'تعزيز الوعي البيئي والمسؤولية المجتمعية لدى التلاميذ.',
            'summary' => 'تم إنجاز النشاط بنجاح بمشاركة واسعة وتفاعل إيجابي من التلاميذ الحاضرين.',
            'attendees_count' => fake()->numberBetween(15, 60),
            'is_public' => true,
            'status' => ClubActivityStatus::Completed,
            'supervisor_notes' => 'تنظيم محكم ومستوى متميز من التفاعل والالتزام.',
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (): array => [
            'activity_date' => now()->addDays(fake()->numberBetween(5, 20))->toDateString(),
            'status' => ClubActivityStatus::Planned,
            'summary' => null,
            'attendees_count' => 0,
        ]);
    }
}

