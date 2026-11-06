<?php

namespace Tests\Feature;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubActivityType;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Enums\DayOfWeek;
use App\Filament\Pages\SchoolLifeHub;
use App\Models\Classroom;
use App\Models\ClubActivity;
use App\Models\ClubMembership;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\SchoolClub;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolLifeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolLifeTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_computes_clubs_overview_and_level_distribution(): void
    {
        $service = app(SchoolLifeService::class);
        $teacher = User::factory()->teacher()->create();

        $phase = EducationPhase::factory()->create();
        $level1 = Level::factory()->for($phase)->create(['name' => 'السنة الأولى متوسط']);
        $level2 = Level::factory()->for($phase)->create(['name' => 'السنة الثانية متوسط']);

        $class1 = Classroom::factory()->for($level1)->create(['name' => '1 م 1']);
        $class2 = Classroom::factory()->for($level2)->create(['name' => '2 م 1']);

        $group1 = Group::factory()->for($class1)->create();
        $group2 = Group::factory()->for($class2)->create();

        $student1 = Student::factory()->for($group1)->create(['first_name' => 'أحمد', 'last_name' => 'بن علي']);
        $student2 = Student::factory()->for($group2)->create(['first_name' => 'سارة', 'last_name' => 'قادري']);

        $greenClub = SchoolClub::factory()->create([
            'name' => 'النادي الأخضر والبيئة المدرسية',
            'type' => ClubType::Environmental,
            'academic_year' => '2026-2027',
            'lead_teacher_id' => $teacher->id,
            'status' => ClubStatus::Active,
        ]);

        $robotClub = SchoolClub::factory()->create([
            'name' => 'نادي الروبوتيك',
            'type' => ClubType::Scientific,
            'academic_year' => '2026-2027',
            'lead_teacher_id' => $teacher->id,
            'status' => ClubStatus::Active,
        ]);

        ClubMembership::factory()->create([
            'school_club_id' => $greenClub->id,
            'student_id' => $student1->id,
            'role' => ClubMemberRole::President,
            'status' => ClubMemberStatus::Active,
        ]);

        ClubMembership::factory()->create([
            'school_club_id' => $greenClub->id,
            'student_id' => $student2->id,
            'role' => ClubMemberRole::ActiveMember,
            'status' => ClubMemberStatus::Active,
        ]);

        ClubActivity::factory()->create([
            'school_club_id' => $greenClub->id,
            'title' => 'حملة تشجير',
            'status' => ClubActivityStatus::Completed,
            'attendees_count' => 35,
        ]);

        ClubActivity::factory()->create([
            'school_club_id' => $robotClub->id,
            'title' => 'ورشة برمجة',
            'status' => ClubActivityStatus::Planned,
        ]);

        $overview = $service->getClubsOverview('2026-2027');

        $this->assertEquals(2, $overview['total_clubs']);
        $this->assertEquals(2, $overview['active_clubs']);
        $this->assertEquals(2, $overview['total_enrolled_students']);
        $this->assertEquals(2, $overview['total_memberships']);
        $this->assertEquals(1, $overview['bureau_members_count']);
        $this->assertEquals(2, $overview['total_activities']);
        $this->assertEquals(1, $overview['completed_activities']);
        $this->assertEquals(1, $overview['planned_activities']);
        $this->assertEquals(35, $overview['total_attendees']);
        $this->assertArrayHasKey('السنة الأولى متوسط', $overview['level_distribution']);
        $this->assertArrayHasKey('السنة الثانية متوسط', $overview['level_distribution']);
    }

    public function test_service_creates_club_and_enrolls_members_and_activities(): void
    {
        $service = app(SchoolLifeService::class);
        $teacher = User::factory()->teacher()->create();
        $student = Student::factory()->create();

        $club = $service->createClub([
            'name' => 'نادي القراءة والإعلام المدرسي',
            'type' => ClubType::Reading->value,
            'academic_year' => '2026-2027',
            'lead_teacher_id' => $teacher->id,
            'meeting_day' => DayOfWeek::Thursday->value,
            'meeting_time' => '16:30 - 17:30',
            'meeting_location' => 'المكتبة المدرسية',
            'max_members' => 25,
            'status' => ClubStatus::Active->value,
        ]);

        $this->assertNotNull($club->id);
        $this->assertEquals('نادي القراءة والإعلام المدرسي', $club->name);
        $this->assertStringStartsWith('CLUB-', $club->code);

        $membership = $service->enrollStudent($club, $student->id, ClubMemberRole::Secretary, 'تلميذ متميز في الكتابة.');
        $this->assertNotNull($membership->id);
        $this->assertEquals(ClubMemberRole::Secretary, $membership->role);
        $this->assertTrue($membership->isBureau());
        $this->assertStringStartsWith('CERT-CLUB-', $membership->certificateReferenceNumber());

        $activity = $service->recordActivity($club, [
            'title' => 'مسابقة القارئ الصغير',
            'activity_type' => ClubActivityType::Competition->value,
            'activity_date' => now()->toDateString(),
            'start_time' => '15:00',
            'end_time' => '17:00',
            'location' => 'قاعة المحاضرات',
            'summary' => 'تم تكريم الفائزين بجوائز تحفيزية.',
            'attendees_count' => 40,
            'is_public' => true,
            'status' => ClubActivityStatus::Completed->value,
        ]);

        $this->assertNotNull($activity->id);
        $this->assertEquals(1, $club->completedActivitiesCount());
        $this->assertEquals(1, $club->activeMembersCount());
    }

    public function test_school_life_hub_page_authorization_and_tabs(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(SchoolLifeHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('فضاء الحياة المدرسية والنوادي التربوية');

        $this->actingAs($teacher)
            ->get(SchoolLifeHub::getUrl(['locale' => 'ar']))
            ->assertOk()
            ->assertSee('فضاء الحياة المدرسية والنوادي التربوية');

        auth()->logout();
        $this->get(SchoolLifeHub::getUrl(['locale' => 'ar']))
            ->assertRedirect();
    }

    public function test_school_life_hub_livewire_modal_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = Student::factory()->create();

        Livewire::actingAs($admin)
            ->test(SchoolLifeHub::class)
            // 1. Create Club
            ->call('openNewClubModal')
            ->assertSet('isClubModalOpen', true)
            ->set('clubName', 'نادي الفلك واستكشاف الفضاء')
            ->set('clubType', ClubType::Scientific->value)
            ->set('clubLeadTeacherId', $teacher->id)
            ->set('clubMeetingLocation', 'مخبر العلوم')
            ->call('saveClub')
            ->assertSet('isClubModalOpen', false);

        $club = SchoolClub::where('name', 'نادي الفلك واستكشاف الفضاء')->first();
        $this->assertNotNull($club);

        Livewire::actingAs($admin)
            ->test(SchoolLifeHub::class)
            // 2. Enroll Student
            ->call('openEnrollModal', $club->id)
            ->assertSet('isEnrollModalOpen', true)
            ->set('enrollingStudentId', $student->id)
            ->set('enrollingRole', ClubMemberRole::President->value)
            ->set('enrollingNotes', 'قائد ومبادر')
            ->call('confirmEnrollStudent')
            ->assertSet('isEnrollModalOpen', false);

        $membership = ClubMembership::where('school_club_id', $club->id)->where('student_id', $student->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals(ClubMemberRole::President, $membership->role);

        Livewire::actingAs($admin)
            ->test(SchoolLifeHub::class)
            // 3. Record Activity
            ->call('openActivityModal', $club->id)
            ->assertSet('isActivityModalOpen', true)
            ->set('activityTitle', 'رصد ظاهرة الخسوف القمري')
            ->set('activityType', ClubActivityType::Workshop->value)
            ->set('activityAttendeesCount', 28)
            ->call('confirmRecordActivity')
            ->assertSet('isActivityModalOpen', false);

        $activity = ClubActivity::where('school_club_id', $club->id)->first();
        $this->assertNotNull($activity);
        $this->assertEquals('رصد ظاهرة الخسوف القمري', $activity->title);
    }

    public function test_print_club_technical_sheet_view_and_pdf_download(): void
    {
        $admin = User::factory()->admin()->create();
        $leadTeacher = User::factory()->teacher()->create();
        $unrelatedTeacher = User::factory()->teacher()->create();

        $club = SchoolClub::factory()->create([
            'name' => 'نادي البيئة الأخضر',
            'lead_teacher_id' => $leadTeacher->id,
        ]);

        // 1. Admin can access
        $this->actingAs($admin)
            ->get(route('print.school-life.club-sheet', [
                'locale' => 'ar',
                'club' => $club,
            ]))
            ->assertOk()
            ->assertSee('البطاقة الفنية والتنظيمية للنادي المدرسي')
            ->assertSee($club->name)
            ->assertSee($leadTeacher->name);

        // 2. Lead teacher can access
        $this->actingAs($leadTeacher)
            ->get(route('print.school-life.club-sheet', [
                'locale' => 'ar',
                'club' => $club,
            ]))
            ->assertOk();

        // 3. Unrelated teacher is forbidden
        $this->actingAs($unrelatedTeacher)
            ->get(route('print.school-life.club-sheet', [
                'locale' => 'ar',
                'club' => $club,
            ]))
            ->assertForbidden();

        // 4. Mocked PDF download
        $this->mock(SchoolLifeService::class, function ($mock) use ($club): void {
            $mock->shouldReceive('generateClubTechnicalSheetPdf')
                ->once()
                ->with(\Mockery::on(fn ($arg) => $arg->id === $club->id))
                ->andReturn('%PDF-1.4 Mocked Club Sheet');
        });

        $this->actingAs($admin)
            ->get(route('print.school-life.club-sheet.pdf', [
                'locale' => 'ar',
                'club' => $club,
            ]))
            ->assertDownload('fiche-technique-club-'.$club->id.'.pdf');
    }

    public function test_print_member_certificate_view_and_pdf_download(): void
    {
        $admin = User::factory()->admin()->create();
        $leadTeacher = User::factory()->teacher()->create();
        $unrelatedTeacher = User::factory()->teacher()->create();

        $club = SchoolClub::factory()->create([
            'name' => 'نادي الفنون الجميلة',
            'lead_teacher_id' => $leadTeacher->id,
        ]);

        $student = Student::factory()->create(['first_name' => 'مريم', 'last_name' => 'عيسى']);

        $membership = ClubMembership::factory()->create([
            'school_club_id' => $club->id,
            'student_id' => $student->id,
            'role' => ClubMemberRole::VicePresident,
        ]);

        // 1. Admin can view certificate
        $this->actingAs($admin)
            ->get(route('print.school-life.member-certificate', [
                'locale' => 'ar',
                'membership' => $membership,
            ]))
            ->assertOk()
            ->assertSee('شهادة تقـدير ومـشاركـة')
            ->assertSee($student->name)
            ->assertSee($club->name);

        // 2. Lead teacher can view certificate
        $this->actingAs($leadTeacher)
            ->get(route('print.school-life.member-certificate', [
                'locale' => 'ar',
                'membership' => $membership,
            ]))
            ->assertOk();

        // 3. Unrelated teacher is forbidden
        $this->actingAs($unrelatedTeacher)
            ->get(route('print.school-life.member-certificate', [
                'locale' => 'ar',
                'membership' => $membership,
            ]))
            ->assertForbidden();

        // 4. Mocked PDF download
        $this->mock(SchoolLifeService::class, function ($mock) use ($membership): void {
            $mock->shouldReceive('generateMembershipCertificatePdf')
                ->once()
                ->with(\Mockery::on(fn ($arg) => $arg->id === $membership->id))
                ->andReturn('%PDF-1.4 Mocked Member Certificate');
        });

        $this->actingAs($admin)
            ->get(route('print.school-life.member-certificate.pdf', [
                'locale' => 'ar',
                'membership' => $membership,
            ]))
            ->assertDownload('certificat-club-'.$membership->id.'.pdf');
    }

    public function test_print_annual_school_life_bilan_view_and_pdf_download(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        SchoolClub::factory()->create([
            'name' => 'نادي الروبوتيك المدرسي',
            'academic_year' => '2026-2027',
        ]);

        // 1. Admin can access
        $this->actingAs($admin)
            ->get(route('print.school-life.annual-bilan', [
                'locale' => 'ar',
                'year' => '2026-2027',
            ]))
            ->assertOk()
            ->assertSee('الحصيلة السنوية الرسمية للحياة المدرسية والنوادي التربوية اللاصفية')
            ->assertSee('نادي الروبوتيك المدرسي');

        // 2. Regular teacher is forbidden from accessing the institutional annual bilan
        $this->actingAs($teacher)
            ->get(route('print.school-life.annual-bilan', [
                'locale' => 'ar',
                'year' => '2026-2027',
            ]))
            ->assertForbidden();

        // 3. Mocked PDF download
        $this->mock(SchoolLifeService::class, function ($mock): void {
            $mock->shouldReceive('generateAnnualLifeBilanPdf')
                ->once()
                ->with('2026-2027')
                ->andReturn('%PDF-1.4 Mocked Annual Life Bilan');
        });

        $this->actingAs($admin)
            ->get(route('print.school-life.annual-bilan.pdf', [
                'locale' => 'ar',
                'year' => '2026-2027',
            ]))
            ->assertDownload('bilan-annuel-vie-scolaire-20262027.pdf');
    }
}

