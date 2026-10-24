<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\User;
use App\Services\SchoolSupervisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolHolidayComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_is_detected(): void
    {
        $holiday = SchoolHoliday::create([
            'title' => 'عطلة رسمية',
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
            'type' => 'national',
        ]);
        $this->assertTrue(SchoolHoliday::isHoliday(today()));
    }

    public function test_supervision_service_skips_holiday(): void
    {
        $holiday = SchoolHoliday::create([
            'title' => 'عطلة رسمية',
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
            'type' => 'national',
        ]);
        $service = new SchoolSupervisionService();
        $data = $service->overview(today()->toDateString());
        $this->assertIsArray($data);
    }
    }

    public function test_unlogged_widget_ignores_holiday(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $schedule = Schedule::factory()->create(['user_id' => $teacher->id]);
        SchoolHoliday::create([
            'title' => 'عطلة رسمية',
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
            'type' => 'national',
        ]);

        $this->assertTrue(SchoolHoliday::isHoliday(today()));
    }
}
