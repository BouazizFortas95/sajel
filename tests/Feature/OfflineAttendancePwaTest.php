<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Livewire\QuickAttendance;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfflineAttendancePwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_service_worker_and_panel_manifest_link_are_available(): void
    {
        $manifestResponse = $this->get('/manifest.webmanifest')->assertOk();
        $manifest = $manifestResponse->json();

        $this->assertSame('Sajel — سجِّل', $manifest['name']);
        $this->assertSame('rtl', $manifest['dir']);
        $this->assertSame('ar', $manifest['lang']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/ar/auth', $manifest['start_url']);
        $this->assertSame('#f59e0b', $manifest['theme_color']);
        $this->assertSame('#0f172a', $manifest['background_color']);

        foreach ($manifest['icons'] as $icon) {
            $this->get($icon['src'])->assertOk();
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
            $this->assertGreaterThan(0, filesize(public_path(ltrim($icon['src'], '/'))));
            $this->assertContains($icon['purpose'], ['any', 'maskable']);
        }

        $this->get('/sw.js')
            ->assertOk()
            ->assertSee('networkFirst', false)
            ->assertSee('/build/', false)
            ->assertSee('buildAssetUrls', false)
            ->assertSee('CACHE_QUICK_ATTENDANCE', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
            ->assertSee('name="theme-color" content="#f59e0b"', false)
            ->assertSee('navigator.serviceWorker.register', false);

        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($teacher)
            ->get('/ar/auth/quick-attendance')
            ->assertOk()
            ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
            ->assertSee('name="theme-color" content="#f59e0b"', false)
            ->assertSee('navigator.serviceWorker.register', false)
            ->assertSee('CACHE_QUICK_ATTENDANCE', false);
    }

    public function test_it_syncs_and_updates_multiple_attendance_rows_idempotently(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        [$schedule, $students] = $this->makeScheduleRoster($teacher);
        $date = '2026-10-24';
        $entries = [
            $this->entry($schedule, $students[0], $date, AttendanceStatus::Absent->value, 'مرض'),
            $this->entry($schedule, $students[1], $date, AttendanceStatus::Late->value, 'تأخر'),
        ];

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->call('syncOfflineAttendance', $entries)
            ->assertReturned(['synced' => 2])
            ->assertHasNoErrors();

        $this->assertDatabaseCount('daily_logs', 1);
        $this->assertDatabaseHas('daily_logs', [
            'schedule_id' => $schedule->id,
            'date' => $date.' 00:00:00',
            'status' => 'planned',
        ]);
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $students[0]->id,
            'status' => AttendanceStatus::Absent->value,
            'remarks' => 'مرض',
        ]);

        $entries[0]['status'] = AttendanceStatus::Excused->value;
        $entries[0]['remarks'] = 'رخصة';

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->call('syncOfflineAttendance', $entries)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('daily_logs', 1);
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $students[0]->id,
            'status' => AttendanceStatus::Excused->value,
            'remarks' => 'رخصة',
        ]);

        Notification::assertNotified(__('اكتملت المزامنة'));
    }

    public function test_it_rejects_a_teacher_syncing_another_teachers_schedule(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $otherTeacher = User::factory()->create(['role' => Role::Teacher->value]);
        [$schedule, $students] = $this->makeScheduleRoster($otherTeacher);

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->call('syncOfflineAttendance', [
                $this->entry($schedule, $students[0], '2026-10-24', AttendanceStatus::Absent->value),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_it_rejects_students_outside_the_schedule_roster(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        [$schedule] = $this->makeScheduleRoster($teacher);
        $outsideStudent = Student::factory()->create();

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->call('syncOfflineAttendance', [
                $this->entry($schedule, $outsideStudent, '2026-10-24', AttendanceStatus::Absent->value),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_admin_can_sync_any_teachers_attendance(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        [$schedule, $students] = $this->makeScheduleRoster($teacher);

        Livewire::actingAs($admin)
            ->test(QuickAttendance::class)
            ->call('syncOfflineAttendance', [
                $this->entry($schedule, $students[0], '2026-10-24', AttendanceStatus::Absent->value),
            ])
            ->assertReturned(['synced' => 1])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendances', [
            'student_id' => $students[0]->id,
            'status' => AttendanceStatus::Absent->value,
        ]);
    }

    public function test_it_validates_dates_and_attendance_statuses_in_sync_batches(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        [$schedule, $students] = $this->makeScheduleRoster($teacher);
        $entry = $this->entry($schedule, $students[0], '24-10-2026', 'unknown');

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->call('syncOfflineAttendance', [$entry])
            ->assertHasErrors(['entries.0.date', 'entries.0.status']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_quick_attendance_renders_offline_controls_and_translated_status_labels(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        Livewire::actingAs($teacher)
            ->test(QuickAttendance::class)
            ->assertSee(__('متصل — الحفظ فوري'))
            ->assertSee(__('وضع عدم الاتصال — يتم حفظ الغيابات محلياً'))
            ->assertSee(__('تعديلات بانتظار المزامنة: :count'))
            ->assertSee(__('مزامنة الآن'))
            ->assertSee(__('حاضر'))
            ->assertSee(__('غائب'))
            ->assertSee(__('متأخر'))
            ->assertSee(__('معفى'));
    }

    /**
     * @return array{0: Schedule, 1: array{0: Student, 1: Student}}
     */
    private function makeScheduleRoster(User $teacher): array
    {
        $classroom = Classroom::factory()->create();
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::cases()[today()->dayOfWeek],
        ]);

        $students = [
            Student::factory()->create(['group_id' => $group->id]),
            Student::factory()->create(['group_id' => $group->id]),
        ];

        return [$schedule, $students];
    }

    /**
     * @return array{schedule_id: int, date: string, student_id: int, status: string, remarks: ?string}
     */
    private function entry(
        Schedule $schedule,
        Student $student,
        string $date,
        string $status,
        ?string $remarks = null,
    ): array {
        return [
            'schedule_id' => $schedule->id,
            'date' => $date,
            'student_id' => $student->id,
            'status' => $status,
            'remarks' => $remarks,
        ];
    }
}
