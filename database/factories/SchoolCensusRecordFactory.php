<?php

namespace Database\Factories;

use App\Models\SchoolCensusRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolCensusRecord>
 */
class SchoolCensusRecordFactory extends Factory
{
    protected $model = SchoolCensusRecord::class;

    public function definition(): array
    {
        return [
            'academic_year' => '2026/2027',
            'census_date' => '2026-10-15',
            'reference_number' => 'إحصاء/2026/01',
            'status' => 'draft',
            'certified_at' => null,
            'certified_by_id' => null,
            'ordinary_classrooms_count' => 12,
            'specialized_rooms_count' => 2,
            'science_labs_count' => 2,
            'computer_labs_count' => 1,
            'library_capacity' => 40,
            'canteen_capacity' => 250,
            'administrative_staff_count' => 4,
            'education_supervisors_count' => 6,
            'service_workers_count' => 8,
            'snapshot_data' => null,
            'observations' => 'تم إعداد الكشف الإحصائي السنوي طبقاً للتنظيم المعمول به إلى غاية 15 أكتوبر.',
        ];
    }

    public function certified(): static
    {
        return $this->state(fn (): array => [
            'status' => 'certified',
            'certified_at' => now(),
            'certified_by_id' => User::factory()->admin(),
            'snapshot_data' => [
                'certified_at' => now()->toIso8601String(),
                'certified_by' => 'مدير المؤسسة',
                'demographics' => [
                    'levels' => [],
                    'total_students' => 100,
                    'total_divisions' => 4,
                    'total_boys' => 50,
                    'total_girls' => 50,
                    'total_externes' => 40,
                    'total_demi_internes' => 60,
                    'total_internes' => 0,
                    'average_per_division' => 25.0,
                    'boys_percentage' => 50.0,
                    'girls_percentage' => 50.0,
                ],
                'age_pyramid' => ['levels' => [], 'rows' => []],
                'staffing' => ['subjects' => [], 'total_teachers' => 10, 'total_weekly_hours' => 180, 'overall_ratio' => 10.0],
            ],
        ]);
    }
}
