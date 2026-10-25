<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\SchoolHoliday;
use App\Models\User;
use App\Services\SchoolSupervisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SchoolHolidayComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_day_holiday_is_detected(): void
    {
        SchoolHoliday::create([
            'title' => 'عيد اندلاع الثورة التحريرية',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-01',
            'type' => 'national',
        ]);

        $this->assertTrue(SchoolHoliday::isHoliday('2025-11-01'));
        $this->assertFalse(SchoolHoliday::isHoliday('2025-11-02'));
    }

    public function test_multi_day_holiday_range_and_date_enumeration(): void
    {
        SchoolHoliday::create([
            'title' => 'عطلة الشتاء',
            'start_date' => '2025-12-25',
            'end_date' => '2025-12-28',
            'type' => 'seasonal',
        ]);

        $this->assertTrue(SchoolHoliday::isHoliday('2025-12-25'));
        $this->assertTrue(SchoolHoliday::isHoliday('2025-12-27'));
        $this->assertTrue(SchoolHoliday::isHoliday('2025-12-28'));
        $this->assertFalse(SchoolHoliday::isHoliday('2025-12-29'));

        $this->assertSame(
            ['2025-12-25', '2025-12-26', '2025-12-27', '2025-12-28'],
            SchoolHoliday::holidayDatesBetween('2025-12-24', '2025-12-30'),
        );

        $this->assertSame(
            ['2025-12-26', '2025-12-27'],
            SchoolHoliday::holidayDatesBetween('2025-12-26', '2025-12-27'),
        );
    }

    public function test_admins_manage_holidays_while_teachers_are_read_only(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $holiday = SchoolHoliday::create([
            'title' => 'عطلة الخريف',
            'start_date' => '2025-10-30',
            'end_date' => '2025-11-02',
            'type' => 'seasonal',
        ]);

        $this->assertTrue(Gate::forUser($teacher)->allows('viewAny', SchoolHoliday::class));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $holiday));
        $this->assertFalse(Gate::forUser($teacher)->allows('create', SchoolHoliday::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $holiday));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $holiday));

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', SchoolHoliday::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', SchoolHoliday::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $holiday));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $holiday));
    }

    public function test_supervision_service_skips_holiday(): void
    {
        SchoolHoliday::create([
            'title' => 'عطلة رسمية',
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
            'type' => 'national',
        ]);

        $data = (new SchoolSupervisionService)->overview(today()->toDateString());

        $this->assertTrue($data['holiday'] ?? false, 'holiday flag set');
        $this->assertSame('عطلة رسمية', $data['holidayName'] ?? null);
        $this->assertSame(0, $data['complianceSummary']['overdue_unlogged_sessions']);
        $this->assertSame([], $data['overdueSessions']);
    }

    public function test_unlogged_widget_ignores_holiday(): void
    {
        SchoolHoliday::create([
            'title' => 'عطلة رسمية',
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
            'type' => 'national',
        ]);

        $this->assertTrue(SchoolHoliday::isHoliday(today()));
    }
}
