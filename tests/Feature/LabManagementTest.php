<?php

namespace Tests\Feature;

use App\Enums\LabStatus;
use App\Enums\Role;
use App\Filament\Resources\LabResource;
use App\Filament\Resources\LabResource\Pages\EditLab;
use App\Filament\Resources\LabResource\RelationManagers\LabStationsRelationManager;
use App\Models\EducationPhase;
use App\Models\Lab;
use App\Models\LabStation;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LabManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_status_enum_labels_and_colors(): void
    {
        $this->assertSame('Working', LabStatus::Working->label());
        $this->assertSame('success', LabStatus::Working->color());

        $this->assertSame('Broken', LabStatus::Broken->label());
        $this->assertSame('danger', LabStatus::Broken->color());

        $this->assertSame('Under Maintenance', LabStatus::UnderMaintenance->label());
        $this->assertSame('warning', LabStatus::UnderMaintenance->color());

        $this->assertSame('working', LabStatus::Working->value);
        $this->assertSame('broken', LabStatus::Broken->value);
        $this->assertSame('under_maintenance', LabStatus::UnderMaintenance->value);
    }

    public function test_lab_belongs_to_level_and_has_stations(): void
    {
        $phase = EducationPhase::factory()->create(['phase_abbreviation' => 'م']);
        $level = Level::factory()->create([
            'education_phase_id' => $phase->id,
            'grade_number' => 1,
            'name' => 'السنة الأولى',
        ]);

        $lab = Lab::factory()->create([
            'level_id' => $level->id,
            'name' => 'مختبر الحاسوب 1',
            'capacity' => 20,
        ]);

        $station1 = LabStation::factory()->create([
            'lab_id' => $lab->id,
            'station_number' => 1,
            'device_type' => 'computer',
            'status' => LabStatus::Working,
        ]);
        $station2 = LabStation::factory()->create([
            'lab_id' => $lab->id,
            'station_number' => 2,
            'device_type' => 'computer',
            'status' => LabStatus::Broken,
        ]);

        $this->assertTrue($lab->level->is($level));
        $this->assertSame(2, $lab->stations->count());
        $this->assertSame(1, $lab->workingStations->count());
        $this->assertSame(1, $lab->brokenStations->count());
        $this->assertSame(0, $lab->underMaintenanceStations->count());
    }

    public function test_lab_display_name_includes_level_and_academic_year(): void
    {
        $phase = EducationPhase::factory()->create(['phase_abbreviation' => 'م']);
        $level = Level::factory()->create([
            'education_phase_id' => $phase->id,
            'grade_number' => 1,
            'name' => 'السنة الأولى',
        ]);

        $lab = Lab::factory()->create([
            'level_id' => $level->id,
            'name' => 'مختبر العلوم',
            'academic_year' => '2025-2026',
        ]);

        $this->assertSame('مختبر العلوم — السنة الأولى (2025-2026)', $lab->display_name());
    }

    public function test_teachers_cannot_open_the_labs_resource(): void
    {
        $teacher = User::factory()->create();

        $this->assertFalse(LabResource::shouldRegisterNavigation());

        $this->actingAs($teacher);
        $this->get('/en/auth/labs')->assertForbidden();
    }

    public function test_admins_can_open_the_labs_resource(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        $this->actingAs($admin);

        $this->assertTrue(LabResource::shouldRegisterNavigation());

        $this->get('/en/auth/labs')
            ->assertOk()
            ->assertSee('Labs');
    }

    public function test_a_lab_can_be_created_from_the_filament_form(): void
    {
        $level = Level::factory()->create();

        $this->actingAs(User::factory()->create(['role' => Role::Admin->value]));

        $lab = Lab::factory()->create([
            'level_id' => $level->id,
            'name' => 'مختبر الحاسوب 1',
            'capacity' => 20,
            'academic_year' => '2025-2026',
        ]);

        $this->assertDatabaseHas('labs', [
            'id' => $lab->id,
            'name' => 'مختبر الحاسوب 1',
            'capacity' => 20,
            'academic_year' => '2025-2026',
        ]);
    }

    public function test_lab_names_are_unique_per_academic_year(): void
    {
        $level = Level::factory()->create();

        Lab::factory()->create([
            'level_id' => $level->id,
            'name' => 'مختبر الحاسوب 1',
            'academic_year' => '2025-2026',
        ]);

        $this->expectException(QueryException::class);

        Lab::factory()->create([
            'level_id' => $level->id,
            'name' => 'مختبر الحاسوب 1',
            'academic_year' => '2025-2026',
        ]);
    }

    public function test_lab_station_status_must_be_valid_enum_value(): void
    {
        $lab = Lab::factory()->create();

        $station = LabStation::factory()->create([
            'lab_id' => $lab->id,
            'station_number' => 1,
            'device_type' => 'computer',
            'status' => LabStatus::Working,
        ]);

        $this->assertSame(LabStatus::Working, $station->status);
        $this->assertSame('working', $station->status->value);
        $this->assertTrue($lab->stations->contains($station));
    }

    public function test_lab_station_numbers_are_unique_per_lab(): void
    {
        $lab = Lab::factory()->create();

        LabStation::factory()->create([
            'lab_id' => $lab->id,
            'station_number' => 5,
            'device_type' => 'computer',
        ]);

        $this->expectException(QueryException::class);

        LabStation::factory()->create([
            'lab_id' => $lab->id,
            'station_number' => 5,
            'device_type' => 'tablet',
        ]);
    }

    public function test_lab_counts_working_broken_and_maintenance_stations(): void
    {
        $lab = Lab::factory()->create(['capacity' => 10]);

        LabStation::factory()->count(3)->create([
            'lab_id' => $lab->id,
            'device_type' => 'computer',
            'status' => LabStatus::Working,
        ]);
        LabStation::factory()->count(2)->create([
            'lab_id' => $lab->id,
            'device_type' => 'computer',
            'status' => LabStatus::Broken,
        ]);
        LabStation::factory()->count(1)->create([
            'lab_id' => $lab->id,
            'device_type' => 'tablet',
            'status' => LabStatus::UnderMaintenance,
        ]);

        $this->assertSame(3, $lab->workingStations->count());
        $this->assertSame(2, $lab->brokenStations->count());
        $this->assertSame(1, $lab->underMaintenanceStations->count());
        $this->assertSame(6, $lab->stations->count());
    }

    public function test_deleting_a_lab_deletes_its_stations(): void
    {
        $lab = Lab::factory()->create();
        $station = LabStation::factory()->create([
            'lab_id' => $lab->id,
            'station_number' => 1,
        ]);

        $lab->delete();

        $this->assertDatabaseMissing('labs', ['id' => $lab->id]);
        $this->assertDatabaseMissing('lab_stations', ['id' => $station->id]);
    }

    public function test_admins_can_create_stations_from_the_lab_relation_manager(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $lab = Lab::factory()->create();

        $this->actingAs($admin);

        Livewire::test(LabStationsRelationManager::class, [
            'ownerRecord' => $lab,
            'pageClass' => EditLab::class,
        ])
            ->assertSuccessful()
            ->assertTableHeaderActionsExistInOrder(['create'])
            ->assertTableEmptyStateActionsExistInOrder(['create'])
            ->callTableAction('create', data: [
                'station_number' => 7,
                'device_type' => 'computer',
                'status' => LabStatus::Working->value,
                'notes' => 'محطة جديدة',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('lab_stations', [
            'lab_id' => $lab->id,
            'station_number' => 7,
            'device_type' => 'computer',
            'status' => LabStatus::Working->value,
        ]);
    }
}
