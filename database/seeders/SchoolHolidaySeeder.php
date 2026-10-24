<?php

namespace Database\Seeders;

use App\Models\SchoolHoliday;
use Illuminate\Database\Seeder;

class SchoolHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['title' => 'عطلة الخريف', 'start' => '2025-10-30', 'end' => '2025-11-02', 'type' => 'seasonal', 'notes' => 'عطلة فصلية رسمية'],
            ['title' => 'عيد اندلاع الثورة التحريرية', 'start' => '2025-11-01', 'end' => '2025-11-01', 'type' => 'national', 'notes' => 'عطلة وطنية رسمية'],
            ['title' => 'عطلة الشتاء', 'start' => '2025-12-25', 'end' => '2026-01-05', 'type' => 'seasonal', 'notes' => 'عطلة فصلية'],
            ['title' => 'عيد الفطر', 'start' => '2026-04-01', 'end' => '2026-04-03', 'type' => 'religious', 'notes' => 'عطلة دينية'],
            ['title' => 'عطلة ربيعية', 'start' => '2026-05-05', 'end' => '2026-05-09', 'type' => 'seasonal', 'notes' => 'عطلة فصلية رسمية'],
        ];
        foreach ($holidays as $h) {
            SchoolHoliday::updateOrCreate(
                ['title' => $h['title'], 'start_date' => $h['start']],
                ['end_date' => $h['end'], 'type' => $h['type'], 'notes' => $h['notes']],
            );
        }
    }
}