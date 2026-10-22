<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Faker locale used for given names. Faker ships Arabic name providers
     * for `ar_SA`, `ar_EG` and `ar_JO` (there is no `ar_DZ` provider).
     */
    private const NAME_LOCALE = 'ar_SA';

    /**
     * Family names common across Algeria, kept curated because Faker's
     * Arabic `lastName` list is Gulf-specific (العتيبي، الشهري، القحطاني…)
     * and would look out of place on an Algerian school register.
     *
     * @var list<string>
     */
    public const FAMILY_NAMES = [
        'بن علي', 'مرزوقي', 'بوعلام', 'حمداني', 'زروقي', 'شريف',
        'بلقاسم', 'عمراني', 'بن ناصر', 'بوزيدي', 'حمداوي', 'سعداوي',
        'بوزيد', 'مسعودي', 'بوقرة', 'خلفاوي', 'قاسمي', 'لعموري',
        'بوعزة', 'زيتوني', 'عباسي', 'مزيان', 'سليماني', 'بن عمر',
        'تواتي', 'حجاج', 'رحماني', 'شعبان', 'بلحاج', 'بومدين',
        'بن يحيى', 'صحراوي', 'طيبي', 'غربي', 'فريدي', 'دحماني',
        'سعيدي', 'بن قاسم', 'بوجمعة', 'دريدي', 'لعمارة', 'بن دحمان',
        'آيت علي', 'آيت أحمد', 'أويحيى', 'عمراوي', 'حميتي', 'بوعناني',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake(self::NAME_LOCALE);
        $isFemale = $faker->boolean();

        return [
            'first_name' => trim($isFemale ? $faker->firstNameFemale() : $faker->firstNameMale()),
            'last_name' => $faker->randomElement(self::FAMILY_NAMES),
            'registration_number' => $faker->unique()->numerify('2026-####'),
            'date_of_birth' => $faker->dateTimeBetween('-15 years', '-11 years')->format('Y-m-d'),
            'group_id' => Group::factory(),
        ];
    }
}
