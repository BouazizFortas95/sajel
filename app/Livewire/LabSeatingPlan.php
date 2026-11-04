<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\Lab;
use App\Models\LabSeatingPlan as LabPlan;
use App\Models\LabSeatingPlanStation;
use App\Models\Student;
use App\Services\NetStationService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LabSeatingPlan extends Component
{
    public ?int $labId = null;

    public ?int $groupId = null;

    public ?int $existingPlanId = null;

    public string $planName = '';

    public string $planNotes = '';

    public array $stations = [];

    public array $students = [];

    public ?int $selectedStationId = null;

    public bool $showSaveModal = false;

    /**
     * Mode: 'control' (NetStation live PC monitoring) or 'seating' (seating arrangement).
     */
    public string $viewMode = 'control';

    public bool $showSafetyModal = false;

    public bool $showBroadcastModal = false;

    public string $broadcastMessageText = '';

    public bool $showLockModal = false;

    public string $customLockMessage = '';

    public bool $globalInternetEnabled = true;

    public function mount(?int $lab = null, ?int $group = null): void
    {
        $this->labId = $lab;
        $this->groupId = $group;

        $this->refreshState();
    }

    public function updatedLabId(): void
    {
        $this->selectedStationId = null;
        $this->refreshState();
    }

    public function updatedGroupId(): void
    {
        $this->selectedStationId = null;
        $this->refreshState();
    }

    public function setViewMode(string $mode): void
    {
        $selectedLab = $this->labId ? Lab::find($this->labId) : null;
        if ($selectedLab && ! $selectedLab->isInformatics()) {
            $this->viewMode = 'seating';

            return;
        }

        $this->viewMode = in_array($mode, ['control', 'seating'], true) ? $mode : 'control';
    }

    public function refreshState(): void
    {
        $this->stations = [];
        $this->students = [];
        $this->selectedStationId = null;

        // 1. Load stations if lab is selected
        if ($this->labId) {
            $lab = Lab::query()->with('workingStations')->find($this->labId);
            if ($lab) {
                if (! $lab->isInformatics()) {
                    $this->viewMode = 'seating';
                }

                foreach ($lab->workingStations as $station) {
                    $this->stations[] = [
                        'id' => $station->id,
                        'station_number' => $station->station_number,
                        'device_type' => $station->device_type,
                        'ip_address' => $station->ip_address ?? '192.168.1.'.(100 + $station->station_number),
                        'is_locked' => (bool) $station->is_locked,
                        'lock_message' => $station->lock_message,
                        'active_broadcast' => $station->active_broadcast,
                        'internet_enabled' => (bool) $station->internet_enabled,
                        'is_online' => $station->isOnline(),
                        'is_computer' => $station->isComputer(),
                        'student_id' => null,
                        'student_name' => null,
                        'position_x' => 0,
                        'position_y' => 0,
                    ];
                }
            }
        }

        // 2. Load students if group is selected
        if ($this->groupId) {
            $group = Group::query()->with('students')->find($this->groupId);
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

        // 3. If both lab and group are selected, look for existing saved plan
        if ($this->labId && $this->groupId) {
            $existingPlan = LabPlan::query()
                ->with(['stations.student'])
                ->where('lab_id', $this->labId)
                ->where('group_id', $this->groupId)
                ->latest()
                ->first();

            if ($existingPlan) {
                $this->existingPlanId = $existingPlan->id;
                $this->planName = $existingPlan->name;
                $this->planNotes = $existingPlan->notes ?? '';

                $planStations = $existingPlan->stations->keyBy('lab_station_id');
                foreach ($this->stations as &$st) {
                    if ($ps = $planStations->get($st['id'])) {
                        $st['student_id'] = $ps->student_id;
                        $st['student_name'] = $ps->student?->full_name;
                        $st['position_x'] = $ps->position_x;
                        $st['position_y'] = $ps->position_y;
                    }
                }
                unset($st);
            } else {
                $this->existingPlanId = null;
                $lab = Lab::find($this->labId);
                $group = Group::find($this->groupId);
                if ($lab && $group) {
                    $this->planName = __('خطة جلوس :lab — :group', [
                        'lab' => $lab->name,
                        'group' => $group->display_name(),
                    ]);
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
                ->title(__('يرجى اختيار محطة أولاً بالنقر عليها، ثم النقر على التلميذ لتعيينه'))
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

        if ($this->selectedStationId === $stationId) {
            $this->selectedStationId = null;
        }
    }

    public function autoAssign(): void
    {
        if (empty($this->stations) || empty($this->students)) {
            return;
        }

        $assignedStudentIds = collect($this->stations)->pluck('student_id')->filter()->all();
        $unassignedStudents = collect($this->students)
            ->reject(fn ($s) => in_array($s['id'], $assignedStudentIds))
            ->values();

        $studentIdx = 0;
        foreach ($this->stations as &$station) {
            if (! $station['student_id'] && $studentIdx < $unassignedStudents->count()) {
                $student = $unassignedStudents[$studentIdx++];
                $station['student_id'] = $student['id'];
                $station['student_name'] = $student['full_name'];
            }
        }
        unset($station);

        Notification::make()
            ->title(__('تم توزيع التلاميذ على المحطات تلقائياً'))
            ->success()
            ->send();
    }

    public function clearAssignments(): void
    {
        foreach ($this->stations as &$station) {
            $station['student_id'] = null;
            $station['student_name'] = null;
        }
        unset($station);
        $this->selectedStationId = null;

        Notification::make()
            ->title(__('تم تفريغ جميع المحطات'))
            ->info()
            ->send();
    }

    public function resetStations(): void
    {
        $this->refreshState();

        Notification::make()
            ->title(__('تمت استعادة حالة الخطة'))
            ->info()
            ->send();
    }

    public function savePlan(): void
    {
        if (blank($this->planName) && $this->labId && $this->groupId) {
            $lab = Lab::find($this->labId);
            $group = Group::find($this->groupId);
            $this->planName = __('خطة جلوس :lab — :group', [
                'lab' => $lab?->name ?? '',
                'group' => $group?->display_name() ?? '',
            ]);
        }

        $this->validate([
            'labId' => 'required|exists:labs,id',
            'groupId' => 'required|exists:groups,id',
            'planName' => 'required|string|max:255',
        ]);

        if ($this->existingPlanId) {
            $plan = LabPlan::find($this->existingPlanId);
            if ($plan) {
                $plan->update([
                    'name' => $this->planName,
                    'notes' => $this->planNotes,
                ]);
                $plan->stations()->delete();
            } else {
                $plan = LabPlan::create([
                    'lab_id' => $this->labId,
                    'group_id' => $this->groupId,
                    'name' => $this->planName,
                    'notes' => $this->planNotes,
                ]);
                $this->existingPlanId = $plan->id;
            }
        } else {
            $plan = LabPlan::firstOrNew([
                'lab_id' => $this->labId,
                'name' => $this->planName,
            ]);
            $plan->group_id = $this->groupId;
            $plan->notes = $this->planNotes;
            $plan->save();

            $plan->stations()->delete();
            $this->existingPlanId = $plan->id;
        }

        foreach ($this->stations as $index => $station) {
            LabSeatingPlanStation::create([
                'seating_plan_id' => $plan->id,
                'lab_station_id' => $station['id'],
                'student_id' => $station['student_id'],
                'position_x' => $station['position_x'] ?? (int) ($index / 6),
                'position_y' => $station['position_y'] ?? (int) ($index % 6),
            ]);
        }

        $this->showSaveModal = false;

        Notification::make()
            ->title(__('تم حفظ خطة الجلوس بنجاح'))
            ->body(__('المحطات المشغولة: :count محطة', ['count' => $this->assignedCount]))
            ->success()
            ->send();
    }

    // =========================================================================
    // NetStation Leader PC Controls
    // =========================================================================

    public function lockAllStations(?string $customMsg = null): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $count = app(NetStationService::class)->lockAll($this->labId, $customMsg);

        foreach ($this->stations as &$st) {
            $st['is_locked'] = true;
            $st['lock_message'] = $customMsg ?: __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.');
        }
        unset($st);

        $this->showLockModal = false;

        Notification::make()
            ->title(__('تم قفل جميع شاشات المخبر (:count محطة)', ['count' => $count]))
            ->warning()
            ->send();
    }

    public function unlockAllStations(): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $count = app(NetStationService::class)->unlockAll($this->labId);

        foreach ($this->stations as &$st) {
            $st['is_locked'] = false;
            $st['lock_message'] = null;
        }
        unset($st);

        Notification::make()
            ->title(__('تم إلغاء قفل جميع شاشات المخبر بنجاح'))
            ->success()
            ->send();
    }

    public function toggleStationLock(int $stationId): void
    {
        $lab = $this->labId ? Lab::find($this->labId) : null;
        if (! $lab?->isInformatics()) {
            return;
        }

        $stationIndex = collect($this->stations)->search(fn ($s) => $s['id'] === $stationId);
        if ($stationIndex === false) {
            return;
        }

        $isCurrentlyLocked = (bool) $this->stations[$stationIndex]['is_locked'];

        if ($isCurrentlyLocked) {
            app(NetStationService::class)->unlockStation($stationId);
            $this->stations[$stationIndex]['is_locked'] = false;
            $this->stations[$stationIndex]['lock_message'] = null;

            Notification::make()
                ->title(__('تم إلغاء قفل المحطة :num', ['num' => $this->stations[$stationIndex]['station_number']]))
                ->success()
                ->send();
        } else {
            app(NetStationService::class)->lockStation($stationId);
            $this->stations[$stationIndex]['is_locked'] = true;
            $this->stations[$stationIndex]['lock_message'] = __('انتبه للأستاذ! الشاشة مقفلة بأمر من المحطة الرئيسية.');

            Notification::make()
                ->title(__('تم قفل شاشة المحطة :num', ['num' => $this->stations[$stationIndex]['station_number']]))
                ->warning()
                ->send();
        }
    }

    public function toggleInternetAll(): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $this->globalInternetEnabled = ! $this->globalInternetEnabled;
        app(NetStationService::class)->toggleInternet($this->labId, $this->globalInternetEnabled);

        foreach ($this->stations as &$st) {
            $st['internet_enabled'] = $this->globalInternetEnabled;
        }
        unset($st);

        Notification::make()
            ->title($this->globalInternetEnabled ? __('تم تمكين الإنترنت لجميع المحطات') : __('تم حجب الإنترنت عن جميع المحطات'))
            ->info()
            ->send();
    }

    public function sendBroadcastMessage(): void
    {
        if (! $this->labId || blank($this->broadcastMessageText)) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        $count = app(NetStationService::class)->broadcastMessage($this->labId, $this->broadcastMessageText);

        foreach ($this->stations as &$st) {
            $st['active_broadcast'] = $this->broadcastMessageText;
        }
        unset($st);

        $this->showBroadcastModal = false;
        $this->broadcastMessageText = '';

        Notification::make()
            ->title(__('تم إرسال البث الجماعي إلى :count محطة بنجاح', ['count' => $count]))
            ->success()
            ->send();
    }

    public function clearBroadcastMessage(): void
    {
        if (! $this->labId) {
            return;
        }

        $lab = Lab::find($this->labId);
        if (! $lab?->isInformatics()) {
            return;
        }

        app(NetStationService::class)->clearBroadcast($this->labId);

        foreach ($this->stations as &$st) {
            $st['active_broadcast'] = null;
        }
        unset($st);

        Notification::make()
            ->title(__('تم إيقاف ومسح البث الجماعي عن جميع الشاشات'))
            ->info()
            ->send();
    }

    public function getAssignedCountProperty(): int
    {
        return collect($this->stations)->filter(fn ($s) => ! empty($s['student_id']))->count();
    }

    public function getAvailableStationsCountProperty(): int
    {
        return count($this->stations);
    }

    public function getLockedCountProperty(): int
    {
        return collect($this->stations)->filter(fn ($s) => ! empty($s['is_locked']))->count();
    }

    public function render(): View
    {
        $labs = Lab::query()->with('workingStations')->orderBy('name')->get();
        $groups = Group::query()->with(['students', 'classroom.level'])->orderBy('name')->get();

        return view('livewire.lab-seating-plan', [
            'labs' => $labs,
            'groups' => $groups,
        ]);
    }
}
