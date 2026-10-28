<?php

namespace Database\Seeders;

use App\Models\SchoolHoliday;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Official 2026–2027 Algerian middle-school holiday calendar.
 *
 * The demo dataset materialises daily logs for the previous Sunday → current
 * Thursday, so any holiday that would fall inside that teaching window is
 * skipped dynamically. This keeps the seeded timetable meaningful whatever
 * day the database is refreshed, while still shipping a full annual calendar.
 */
class SchoolHolidaySeeder extends Seeder
{
    /**
     * @var list<array{title: string, start_date: string, end_date: string, type: string, notes: string}>
     */
    private const HOLIDAYS = [
        [
            'title' => 'عطلة الخريف',
            'start_date' => '2026-10-29',
            'end_date' => '2026-11-02',
            'type' => 'seasonal',
            'notes' => 'عطلة فصلية رسمية — السنة الدراسية 2026-2027.',
        ],
        [
            'title' => 'عيد اندلاع الثورة التحريرية',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'type' => 'national',
            'notes' => 'عطلة وطنية رسمية.',
        ],
        [
            'title' => 'عطلة الشتاء',
            'start_date' => '2026-12-24',
            'end_date' => '2027-01-04',
            'type' => 'seasonal',
            'notes' => 'عطلة فصلية رسمية بين الفصل الأول والفصل الثاني.',
        ],
        [
            'title' => 'رأس السنة الأمازيغية',
            'start_date' => '2027-01-12',
            'end_date' => '2027-01-12',
            'type' => 'national',
            'notes' => 'عطلة وطنية رسمية.',
        ],
        [
            'title' => 'عيد الفطر',
            'start_date' => '2027-03-20',
            'end_date' => '2027-03-23',
            'type' => 'religious',
            'notes' => 'عطلة دينية رسمية.',
        ],
        [
            'title' => 'عطلة الربيع',
            'start_date' => '2027-03-25',
            'end_date' => '2027-04-04',
            'type' => 'seasonal',
            'notes' => 'عطلة فصلية رسمية بين الفصل الثاني والفصل الثالث.',
        ],
        [
            'title' => 'يوم العلم',
            'start_date' => '2027-04-16',
            'end_date' => '2027-04-16',
            'type' => 'national',
            'notes' => 'يوم العلم — نشاط تربوي وطني.',
        ],
        [
            'title' => 'عيد العمال',
            'start_date' => '2027-05-01',
            'end_date' => '2027-05-01',
            'type' => 'national',
            'notes' => 'عطلة وطنية رسمية.',
        ],
        [
            'title' => 'عيد الأضحى',
            'start_date' => '2027-05-16',
            'end_date' => '2027-05-18',
            'type' => 'religious',
            'notes' => 'عطلة دينية رسمية.',
        ],
        [
            'title' => 'رأس السنة الهجرية',
            'start_date' => '2027-06-06',
            'end_date' => '2027-06-06',
            'type' => 'religious',
            'notes' => 'عطلة دينية رسمية.',
        ],
        [
            'title' => 'عطلة نهاية السنة الدراسية',
            'start_date' => '2027-06-30',
            'end_date' => '2027-09-15',
            'type' => 'seasonal',
            'notes' => 'العطلة الصيفية الرسمية.',
        ],
    ];

    public function run(): void
    {
        $weekStart = now()->startOfWeek(Carbon::SUNDAY);
        $windowStart = $weekStart->copy()->subWeek()->startOfDay();
        $windowEnd = $weekStart->copy()->addDays(4)->endOfDay();
        $timestamp = now();
        $rows = [];

        foreach (self::HOLIDAYS as $holiday) {
            $start = Carbon::parse($holiday['start_date'])->startOfDay();
            $end = Carbon::parse($holiday['end_date'])->endOfDay();

            // Never publish a holiday inside the two teaching weeks materialised
            // by the demo seeder; the timetable and daily logs must stay usable.
            if ($start->lte($windowEnd) && $end->gte($windowStart)) {
                continue;
            }

            $rows[] = $holiday + [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        SchoolHoliday::query()->insert($rows);
    }
}
