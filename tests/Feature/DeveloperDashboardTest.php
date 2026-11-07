<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\DashboardStats;
use App\Livewire\DeveloperDashboardStats;
use App\Models\EducationalInstitution;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeveloperDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_sees_multi_institution_aggregator_dashboard(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $school = EducationalInstitution::factory()->create([
            'name' => 'متوسطة الشهيد العربي بن مهيدي',
            'code' => '30-1102-0007',
            'stage' => 'middle',
            'is_current' => true,
        ]);

        $this->actingAs($dev);

        $this->get('/en/auth')
            ->assertOk()
            ->assertSeeLivewire(DeveloperDashboardStats::class)
            ->assertDontSeeLivewire(DashboardStats::class);

        Livewire::test(DeveloperDashboardStats::class)
            ->assertSee('متوسطة الشهيد العربي بن مهيدي')
            ->assertSee('30-1102-0007')
            ->assertSee('إجمالي المؤسسات المسجلة')
            ->assertSee('طاقة الاستيعاب التجميعية');
    }

    public function test_principal_sees_school_operational_dashboard(): void
    {
        $principal = User::factory()->create(['role' => Role::Principal]);

        $this->actingAs($principal);

        $this->get('/en/auth')
            ->assertOk()
            ->assertSeeLivewire(DashboardStats::class)
            ->assertDontSeeLivewire(DeveloperDashboardStats::class);
    }

    public function test_teacher_sees_school_operational_dashboard(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);

        $this->actingAs($teacher);

        $this->get('/en/auth')
            ->assertOk()
            ->assertSeeLivewire(DashboardStats::class)
            ->assertDontSeeLivewire(DeveloperDashboardStats::class);
    }

    public function test_developer_dashboard_can_switch_tabs(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        EducationalInstitution::factory()->create([
            'name' => 'ثانوية مفدي زكريا',
            'wilaya' => 'غرداية',
            'stage' => 'secondary',
        ]);

        $this->actingAs($dev);

        Livewire::test(DeveloperDashboardStats::class)
            ->assertSet('activeTab', 'aggregator')
            ->call('setTab', 'wilayas')
            ->assertSet('activeTab', 'wilayas')
            ->assertSee('غرداية')
            ->call('setTab', 'system_ops')
            ->assertSet('activeTab', 'system_ops')
            ->assertSee('إجراءات الصيانة السريعة للخادم');
    }

    public function test_developer_dashboard_can_switch_active_institution(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $school1 = EducationalInstitution::factory()->create([
            'name' => 'المدرسة القديمة',
            'is_current' => true,
        ]);
        $school2 = EducationalInstitution::factory()->create([
            'name' => 'ثانوية الشهيد ديدوش مراد',
            'directorate_name' => 'مديرية التربية لولاية الجزائر وسط',
            'code' => '16-0005',
            'is_current' => false,
        ]);

        $this->actingAs($dev);

        Livewire::test(DeveloperDashboardStats::class)
            ->call('makeInstitutionCurrent', $school2->id);

        $this->assertFalse($school1->fresh()->is_current);
        $this->assertTrue($school2->fresh()->is_current);

        // SchoolSetting is updated
        $this->assertEquals('ثانوية الشهيد ديدوش مراد', SchoolSetting::current()->school_name);
        $this->assertEquals('16-0005', SchoolSetting::current()->school_code);
    }

    public function test_developer_dashboard_can_filter_institutions(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $active = EducationalInstitution::factory()->create([
            'name' => 'متوسطة النموذجية المركزية',
            'is_current' => true,
        ]);
        $primary = EducationalInstitution::factory()->create([
            'name' => 'ابتدائية ابن خلدون',
            'stage' => 'primary',
            'is_current' => false,
        ]);
        $secondary = EducationalInstitution::factory()->create([
            'name' => 'ثانوية هواري بومدين',
            'stage' => 'secondary',
            'is_current' => false,
        ]);

        $this->actingAs($dev);

        Livewire::test(DeveloperDashboardStats::class)
            ->set('stageFilter', 'secondary')
            ->assertSee('ثانوية هواري بومدين')
            ->assertDontSee('ابتدائية ابن خلدون');
    }

    public function test_developer_can_select_and_inspect_any_institution_dashboard(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $school1 = EducationalInstitution::factory()->create([
            'name' => 'متوسطة الشهيد العربي بن مهيدي',
            'code' => '30-1102-0007',
            'is_current' => true,
        ]);
        $school2 = EducationalInstitution::factory()->create([
            'name' => 'ثانوية مفدي زكريا التكنولوجية',
            'code' => '47-0201-0015',
            'directorate_name' => 'مديرية التربية لولاية غرداية',
            'wilaya' => 'غرداية',
            'stage' => 'secondary',
            'is_current' => false,
        ]);

        $this->actingAs($dev);

        Livewire::test(DeveloperDashboardStats::class)
            ->call('selectInstitution', $school2->id)
            ->assertSet('selectedInstitutionId', $school2->id)
            ->assertSet('activeTab', 'active_school_preview')
            ->assertSee('ثانوية مفدي زكريا التكنولوجية')
            ->assertSee('47-0201-0015')
            ->assertSee('مديرية التربية لولاية غرداية');
    }

    public function test_developer_can_filter_wilayas_and_preview(): void
    {
        $dev = User::factory()->create(['role' => Role::Developer]);
        $schoolConstantine = EducationalInstitution::factory()->create([
            'name' => 'متوسطة الأمير عبد القادر',
            'wilaya' => 'قسنطينة',
            'stage' => 'middle',
        ]);

        $this->actingAs($dev);

        Livewire::test(DeveloperDashboardStats::class)
            ->call('filterByWilayaAndPreview', 'قسنطينة')
            ->assertSet('previewWilayaFilter', 'قسنطينة')
            ->assertSet('selectedInstitutionId', $schoolConstantine->id)
            ->assertSet('activeTab', 'active_school_preview')
            ->assertSee('متوسطة الأمير عبد القادر');
    }

    public function test_non_developer_and_non_admin_cannot_mount_developer_dashboard(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->actingAs($teacher);

        Livewire::test(DeveloperDashboardStats::class)
            ->assertForbidden();
    }
}
