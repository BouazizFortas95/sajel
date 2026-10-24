<?php

namespace Database\Seeders;

use App\Models\SchoolHoliday;
use Illuminate\Database\Seeder;

class SchoolHolidaySeeder extends Seeder
{
    public function run(): void
    {
        SchoolHoliday::insert([
            ['title' => 'عطلة الخريف', 'start_date' => '2025-10-30', 'end_date' => '2025-11-02', 'type' => 'seasonal', 'notes' => 'عطلة فصلية رسمية', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'عيد اندلاع الثورة التحريرية', 'start_date' => '2025-11-01', 'end_date' => '2025-11-01', 'type' => 'national', 'notes' => 'عطلة وطنية رسمية', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'عطلة الشتاء', 'start_date' => '2025-12-25', 'end_date' => '2026-01-05', 'type' => 'seasonal', 'notes' => 'عطلة فصلية', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'عيد الفطر', 'start_date' => '2026-04-01', 'end_date' => '2026-04-03', 'type' => 'religious', 'notes' => 'عطلة دينية', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'عطلة ربيعية', 'start_date' => '2026-05-05', 'end_date' => '2026-05-09', 'type' => 'seasonal', 'notes' => 'عطلة فصلية رسمية', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
