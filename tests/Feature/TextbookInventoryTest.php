<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\TextbookDistributionType;
use App\Enums\TextbookReturnCondition;
use App\Filament\Pages\TextbookInventoryHub;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentTextbookLoan;
use App\Models\Subject;
use App\Models\Textbook;
use App\Models\User;
use App\Services\TextbookInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextbookInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_standard_algerian_catalog_creates_and_updates_national_textbooks(): void
    {
        $phase = EducationPhase::factory()->create(['name' => 'التعليم المتوسط']);
        $level1 = Level::factory()->for($phase)->create(['name' => 'السنة الأولى متوسط', 'grade_number' => 1]);
        $subject = Subject::factory()->create(['name' => 'اللغة العربية']);

        $service = app(TextbookInventoryService::class);
        $count = $service->seedStandardAlgerianCatalog();

        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseHas('textbooks', [
            'level_id' => $level1->id,
            'subject_id' => $subject->id,
            'title' => 'كتاب اللغة العربية وآدابها — السنة الأولى متوسط',
            'unit_price' => 280.00,
        ]);
    }

    public function test_distribute_classroom_pack_allocates_textbooks_to_students(): void
    {
        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '1 م 1']);
        $group = Group::factory()->for($classroom)->create();
        $subject = Subject::factory()->create();

        $students = Student::factory()->count(3)->create(['group_id' => $group->id]);

        $textbook = Textbook::factory()->create([
            'level_id' => $level->id,
            'subject_id' => $subject->id,
            'total_received' => 50,
        ]);

        $service = app(TextbookInventoryService::class);
        $distributedCount = $service->batchDistributeToClassroom($classroom, null, TextbookDistributionType::Solidarity);

        $this->assertSame(3, $distributedCount);
        $this->assertCount(3, StudentTextbookLoan::where('textbook_id', $textbook->id)->get());

        // Stock decreased by 3
        $this->assertSame(47, $textbook->fresh()->availableStock());
    }

    public function test_record_return_processes_good_and_damaged_conditions(): void
    {
        $service = app(TextbookInventoryService::class);

        $textbook = Textbook::factory()->create([
            'total_received' => 10,
        ]);

        $loan1 = StudentTextbookLoan::factory()->create([
            'textbook_id' => $textbook->id,
            'returned_at' => null,
            'clearance_granted' => false,
        ]);

        $loan2 = StudentTextbookLoan::factory()->create([
            'textbook_id' => $textbook->id,
            'returned_at' => null,
            'clearance_granted' => false,
        ]);

        // Process return loan1 with Good condition
        $service->processReturn($loan1, TextbookReturnCondition::Good, 'حالة ممتازة');
        $loan1->refresh();
        $this->assertTrue($loan1->isReturned());
        $this->assertSame(TextbookReturnCondition::Good, $loan1->return_condition);
        $this->assertTrue($loan1->clearance_granted);

        // Process return loan2 with Damaged condition
        $service->processReturn($loan2, TextbookReturnCondition::Damaged, 'غلاف ممزق');
        $loan2->refresh();
        $this->assertTrue($loan2->isReturned());
        $this->assertSame(TextbookReturnCondition::Damaged, $loan2->return_condition);
        $this->assertFalse($loan2->clearance_granted);
        $this->assertSame('غلاف ممزق', $loan2->notes);
    }

    public function test_clearance_computation_and_manual_granting(): void
    {
        $service = app(TextbookInventoryService::class);

        $group = Group::factory()->create();
        $student = Student::factory()->create(['group_id' => $group->id]);

        $loan = StudentTextbookLoan::factory()->create([
            'student_id' => $student->id,
            'returned_at' => null,
            'clearance_granted' => false,
        ]);

        // Unreturned loan -> clearance false
        $status = $service->checkStudentClearance($student);
        $this->assertFalse($status['is_cleared']);

        // Mark returned as good
        $service->processReturn($loan, TextbookReturnCondition::Good);

        $status = $service->checkStudentClearance($student);
        $this->assertTrue($status['is_cleared']);

        // Grant clearance
        $service->grantStudentClearance($student);
        $this->assertTrue($loan->fresh()->clearance_granted);
    }

    public function test_textbook_inventory_hub_accessible_to_admin_and_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(TextbookInventoryHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('إدارة الكتاب المدرسي وحصص التضامن وبراءة الذمة');

        $this->actingAs($teacher)
            ->get(TextbookInventoryHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('إدارة الكتاب المدرسي وحصص التضامن وبراءة الذمة');

        auth()->logout();
        $this->get(TextbookInventoryHub::getUrl(['locale' => 'ar']))
            ->assertRedirect();
    }

    public function test_print_routes_render_clearance_classroom_sheet_and_annual_inventory(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '2 م 1']);
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->create(['group_id' => $group->id, 'first_name' => 'أمين', 'last_name' => 'بلمهدي']);

        // 1. Student clearance route
        $this->get(route('print.textbook.clearance', [
            'locale' => 'ar',
            'student' => $student,
        ]))
            ->assertOk()
            ->assertSee('شهادة براءة الذمة الرسمية (Quitus)')
            ->assertSee('أمين')
            ->assertSee('بلمهدي');

        // 2. Classroom distribution sheet route
        $this->get(route('print.textbook.classroom', [
            'locale' => 'ar',
            'classroom' => $classroom,
        ]))
            ->assertOk()
            ->assertSee('استمارة تسليم واسترجاع الكتب المدرسية للقسم')
            ->assertSee('2 م 1');

        // 3. Annual ONPS inventory route
        $this->get(route('print.textbook.annual-inventory', [
            'locale' => 'ar',
        ]))
            ->assertOk()
            ->assertSee('الديوان الوطني للمطبوعات المدرسية (ONPS)')
            ->assertSee('الحصيلة السنوية الشاملة وجرد حركة الكتاب المدرسي');
    }

    public function test_teacher_can_access_clearance_and_classroom_sheet_for_assigned_classroom(): void
    {
        $teacher = User::factory()->teacher()->create();
        $unassignedTeacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create();
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->create(['group_id' => $group->id]);
        $subject = Subject::factory()->create();

        Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);

        // Assigned teacher gets 200
        $this->actingAs($teacher)
            ->get(route('print.textbook.classroom', ['locale' => 'ar', 'classroom' => $classroom]))
            ->assertOk();

        $this->actingAs($teacher)
            ->get(route('print.textbook.clearance', ['locale' => 'ar', 'student' => $student]))
            ->assertOk();

        // Unassigned teacher gets 403
        $this->actingAs($unassignedTeacher)
            ->get(route('print.textbook.classroom', ['locale' => 'ar', 'classroom' => $classroom]))
            ->assertForbidden();

        // Teacher cannot access annual ONPS inventory (admin only)
        $this->actingAs($teacher)
            ->get(route('print.textbook.annual-inventory', ['locale' => 'ar']))
            ->assertForbidden();
    }

    public function test_pdf_download_routes_stream_when_mocked(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $phase = EducationPhase::factory()->create();
        $level = Level::factory()->for($phase)->create();
        $classroom = Classroom::factory()->for($level)->create(['name' => '3 م 2']);
        $group = Group::factory()->for($classroom)->create();
        $student = Student::factory()->create(['group_id' => $group->id]);

        $this->mock(TextbookInventoryService::class, function ($mock): void {
            $mock->shouldReceive('generateClearancePdf')
                ->once()
                ->andReturn('%PDF-1.4 mock clearance');

            $mock->shouldReceive('generateClassroomDistributionPdf')
                ->once()
                ->andReturn('%PDF-1.4 mock classroom');

            $mock->shouldReceive('generateAnnualOnpsInventoryPdf')
                ->once()
                ->andReturn('%PDF-1.4 mock annual inventory');
        });

        $this->get(route('print.textbook.clearance.pdf', ['locale' => 'ar', 'student' => $student]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('print.textbook.classroom.pdf', ['locale' => 'ar', 'classroom' => $classroom]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('print.textbook.annual-inventory.pdf', ['locale' => 'ar']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
