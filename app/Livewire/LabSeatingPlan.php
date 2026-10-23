<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\Lab;
use App\Models\LabSeatingPlan;
use App\Models\LabSeatingPlanStation;
use App\Models\Student;
use Filament\Notifications\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class LabSeatingPlan extends Component
{
    use WithPagination;

    public ?int $labId = null;

    public ?int $groupId = null;

    public string $planName = '';

    public string $planNotes = '';

    public array $stations = [];

    public array $students = [];

    public array $assignments = [];

    public ?int $selectedStationId = null;

    public bool $showSaveModal = false;

    public function mount(): void
    {
        $this->resetStations();
    }

    public function updatedLabId(): void
    {
        $this->groupId = null;
        $this->planName = '';
        $this->planNotes = '';
        $this->resetStations();
    }

    public function updatedGroupId(): void
    {
        $this->loadStudents();
    }

    public function resetStations(): void
    {
        $this->stations = [];
        $this->students = [];
        $this->assignments = [];
        $this->selectedStationId = null;

        if ($this->labId) {
            $lab = Lab::query()->find($this->labId);
            if ($lab) {
                foreach ($lab->workingStations as $station) {
                    $this->stations[] = [
                        'id' => $station->id,
                        'station_number' => $station->station_number,
                        'device_type' => $station->device_type,
                        'student_id' => null,
                        'student_name' => null,
                    ];
                }
            }
        }
    }

    public function loadStudents(): void
    {
        $this->students = [];

        if ($this->groupId) {
            $group = Group::query()->find($this->groupId);
            if ($group) {
                foreach ($group->students as $student) {
                    $this->students[] = [
                        'id' => $student->id,
                        'full_name' => $student->full_name,
                        'registration_number' => $student->registration_number ?? '—',
                    ];
                }
            }
        }
    }

    public function selectStation(int $stationId): void
    {
        $this->selectedStationId = $this->selectedStationId === $stationId ? null : $stationId;
    }

    public function assignStudent(int $studentId): void
    {
        if (! $this->selectedStationId) {
            Notification::make()
                ->title(__('يرجى اختيار محطة أولاً'))
                ->warning()
                ->send();

            return;
        }

        // Remove student from any other station
        foreach ($this->stations as &$station) {
            if ($station['student_id'] === $studentId) {
                $station['student_id'] = null;
                $station['student_name'] = null;
            }
        }
        unset($station);

        // Assign to selected station
        foreach ($this->stations as &$station) {
            if ($station['id'] === $this->selectedStationId) {
                $student = collect($this->students)->firstWhere('id', $studentId);
                $station['student_id'] = $studentId;
                $station['student_name'] = $student['full_name'] ?? null;
                break;
            }
        }
        unset($station);

        $this->selectedStationId = null;
    }

    public function unassignStudent(int $stationId): void
    {
        foreach ($this->stations as &$station) {
            if ($station['id'] === $stationId) {
                $station['student_id'] = null;
                $station['student_name'] = null;
                break;
            }
        }
        unset($station);
    }

    public function savePlan(): void
    {
        $this->validate([
            'labId' => 'required|exists:labs,id',
            'groupId' => 'required|exists:groups,id',
            'planName' => 'required|string|max:255',
        ]);

        $plan = LabSeatingPlan::create([
            'lab_id' => $this->labId,
            'group_id' => $this->groupId,
            'name' => $this->planName,
            'notes' => $this->planNotes,
        ]);

        foreach ($this->stations as $station) {
            LabSeatingPlanStation::create([
                'seating_plan_id' => $plan->id,
                'lab_station_id' => $station['id'],
                'student_id' => $station['student_id'],
                'position_x' => $station['position_x'] ?? 0,
                'position_y' => $station['position_y'] ?? 0,
            ]);
        }

        $this->showSaveModal = false;

        Notification::make()
            ->title(__('تم حفظ خطة الجلوس بنجاح'))
            ->success()
            ->send();

        $this->redirect(
            route('filament.admin.lab-seating-plans.edit', [
                'locale' => app()->getLocale(),
                'record' => $plan->id,
            ])
        );
    }

    public function cancelSave(): void
    {
        $this->showSaveModal = false;
    }

    public function getAssignedCountProperty(): int
    {
        return collect($this->stations)->filter(fn ($s) => $s['student_id'])->count();
    }

    public function getAvailableStationsCountProperty(): int
    {
        return count($this->stations);
    }

    public function render()
    {
        $labs = Lab::query()->with('workingStations')->orderBy('name')->get();
        $groups = Group::query()->with('students')->orderBy('name')->get();

        return view('livewire.lab-seating-plan', [
            'labs' => $labs,
            'groups' => $groups,
        ]);
    }
}
