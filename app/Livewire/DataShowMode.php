<?php

namespace App\Livewire;

use App\Enums\LessonPhaseType;
use App\Models\DailyLog;
use Livewire\Component;

class DataShowMode extends Component
{
    public ?int $logId = null;

    public array $content = [];

    public int $slideIndex = 0;

    public function mount(?int $logId = null): void
    {
        if ($logId) {
            $this->logId = $logId;
        } else {
            $this->logId = DailyLog::query()
                ->whereHas('schedule', fn ($q) => $q->forTeacher(auth()->id()))
                ->whereDate('date', today())
                ->first()?->id;
        }
        $this->loadContent();
    }

    public function loadContent(): void
    {
        if (! $this->logId) {
            $this->content = [];

            return;
        }

        $log = DailyLog::with('schedule.subject', 'schedule.classroom', 'schedule.group')->find($this->logId);

        if (! $log) {
            $this->content = [];

            return;
        }

        $slides = [];

        $slides[] = [
            'title' => $log->title,
            'subtitle' => $log->date?->format('l, d/m/Y') ?? today()->format('l, d/m/Y'),
            'type' => 'cover',
        ];

        $phases = $log->content_data['phases'] ?? $log->content_data['lesson_phases'] ?? [];

        foreach ($phases as $phase) {
            if (! is_array($phase)) {
                continue;
            }

            $phaseType = LessonPhaseType::tryFrom($phase['type'] ?? '');
            $slides[] = [
                'title' => $phaseType?->label() ?? ($phase['type'] ?? ''),
                'description' => $phase['description'] ?? $phase['content'] ?? null,
                'duration' => $phase['duration'] ?? null,
                'type' => 'phase',
            ];
        }

        $this->content = $slides;
    }

    public function next(): void
    {
        if ($this->slideIndex < count($this->content) - 1) {
            $this->slideIndex++;
        }
    }

    public function prev(): void
    {
        if ($this->slideIndex > 0) {
            $this->slideIndex--;
        }
    }

    public function goToSlide(int $index): void
    {
        $this->slideIndex = max(0, min(count($this->content) - 1, $index));
    }

    public function render()
    {
        return view('livewire.data-show-mode', [
            'slides' => $this->content,
            'currentSlide' => $this->content[$this->slideIndex] ?? null,
        ]);
    }
}
