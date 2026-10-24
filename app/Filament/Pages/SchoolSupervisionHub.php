<?php

namespace App\Filament\Pages;

use App\Models\BehaviorObservation;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\User;
use App\Services\SchoolSupervisionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

class SchoolSupervisionHub extends Page
{
    protected static ?string $slug = 'school-supervision';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.pages.school-supervision-hub';

    #[Url]
    public string $selectedDate = '';

    #[Url(as: 'classroom')]
    public ?int $selectedClassroomId = null;

    #[Url(as: 'level')]
    public ?int $levelId = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('المراقبة العامة والتفتيش');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التشغيل اليومي');
    }

    public function getTitle(): string
    {
        return __('المراقبة العامة والتفتيش');
    }

    public function mount(): void
    {
        if (blank($this->selectedDate)) {
            $this->selectedDate = now(config('app.timezone', 'Africa/Algiers'))->toDateString();
        }

        $this->validate([
            'selectedDate' => ['required', 'date_format:Y-m-d'],
            'selectedClassroomId' => ['nullable', 'integer', 'exists:classrooms,id'],
            'levelId' => ['nullable', 'integer', 'exists:levels,id'],
        ]);
    }

    public function updatedSelectedDate(): void
    {
        $this->validate([
            'selectedDate' => ['required', 'date_format:Y-m-d'],
        ]);
    }

    public function updatedSelectedClassroomId(): void
    {
        $this->validate([
            'selectedClassroomId' => ['nullable', 'integer', 'exists:classrooms,id'],
        ]);
    }

    public function updatedLevelId(): void
    {
        $this->validate([
            'levelId' => ['nullable', 'integer', 'exists:levels,id'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function getClassroomOptionsProperty(): array
    {
        return Classroom::query()
            ->with('level.educationPhase')
            ->when($this->levelId, fn ($query) => $query->where('level_id', $this->levelId))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->id => $classroom->display_name(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getLevelOptionsProperty(): array
    {
        return Level::query()
            ->with('educationPhase')
            ->orderBy('grade_number')
            ->get()
            ->mapWithKeys(fn (Level $level): array => [$level->id => $level->display_name()])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardDataProperty(): array
    {
        return app(SchoolSupervisionService::class)->overview(
            $this->selectedDate,
            $this->selectedClassroomId,
            $this->levelId,
        );
    }

    public function resetFiltersToToday(): void
    {
        $this->selectedDate = now(config('app.timezone', 'Africa/Algiers'))->toDateString();
    }

    public function resolveBehaviorObservation(int $observationId, ?string $followUpNotes = null): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $observation = BehaviorObservation::query()->findOrFail($observationId);
        $followUpNotes = filled($followUpNotes) ? trim($followUpNotes) : null;

        validator(
            ['followUpNotes' => $followUpNotes],
            ['followUpNotes' => ['nullable', 'string', 'max:2000']],
        )->validate();

        if (
            $observation->is_followed_up
            || ! in_array($observation->category, ['negative', 'disciplinary', 'observation'], true)
        ) {
            throw ValidationException::withMessages([
                'observation' => __('هذه الحالة ليست ضمن الحالات المفتوحة للمتابعة.'),
            ]);
        }

        $observation->is_followed_up = true;
        $observation->follow_up_notes = $followUpNotes;
        $observation->save();

        Notification::make()
            ->success()
            ->title(__('تم تسجيل متابعة الحالة'))
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }
}
