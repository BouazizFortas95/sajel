<?php

namespace App\Livewire;

use App\Enums\LessonPhaseType;
use App\Models\DailyLog;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class DataShowMode extends Component
{
    public ?int $logId = null;

    public array $content = [];

    public int $slideIndex = 0;

    public string $theme = 'cinema'; // cinema | whiteboard | chalkboard

    public float $fontScale = 1.0;

    public bool $timerRunning = false;

    public int $timerSeconds = 0;

    public array $pickedStudents = [];

    public bool $spotlight = false;

    public string $searchQuery = '';

    public array $logs = [];

    public function mount(?int $logId = null): void
    {
        $this->loadLogs();
        $this->selectLog($logId ?? null);
    }

    public function loadLogs(): void
    {
        $this->logs = DailyLog::query()
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group'])
            ->whereHas('schedule', fn ($q) => $q->forTeacher(auth()->id()))
            ->orderByDesc('date')
            ->limit(10)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'label' => $l->date?->format('d/m/Y') . ' — ' . ($l->schedule?->classroom?->name ?? '') . ' — ' . $l->title,
            ])
            ->toArray();
    }

    public function selectLog(?int $id = null): void
    {
        if ($id) {
            $this->logId = $id;
        } else {
            $today = DailyLog::query()
                ->whereHas('schedule', fn ($q) => $q->forTeacher(auth()->id()))
                ->whereDate('date', today())
                ->first();
            $this->logId = $today?->id ?? (DailyLog::query()
                ->whereHas('schedule', fn ($q) => $q->forTeacher(auth()->id()))
                ->orderByDesc('date')
                ->first()?->id);
        }
        $this->slideIndex = 0;
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

        // Slide 1 — Cover
        $slides[] = [
            'type' => 'cover',
            'title' => $log->title,
            'subtitle' => ($log->schedule?->subject?->name ?? '') . ' — ' . ($log->schedule?->classroom?->name ?? '') . ' — ' . ($log->date?->format('l, d/m/Y') ?? today()->format('l, d/m/Y')),
            'competence' => $log->content_data['competence'] ?? $log->actual_content ? $this->extractFirstHeading($log->actual_content) : null,
            'subject_color' => $log->schedule?->subject?->color ?? 'amber',
        ];

        $phases = $log->content_data['phases'] ?? $log->content_data['lesson_phases'] ?? [];

        foreach ($phases as $i => $phase) {
            if (! is_array($phase)) {
                continue;
            }
            $phaseType = LessonPhaseType::tryFrom($phase['type'] ?? '');
            $slides[] = [
                'type' => 'phase',
                'phase_number' => $i + 1,
                'title' => $phaseType?->label() ?? ($phase['type'] ?? __('مرحلة')),
                'description' => $phase['description'] ?? $phase['content'] ?? null,
                'duration' => $phase['duration'] ?? null,
            ];
        }

        // Final slide — register summary + homework
        $slides[] = [
            'type' => 'summary',
            'title' => __('الملخص والواجب المنزلي'),
            'register' => $log->actual_content ?? $log->content_data['actual_content'] ?? null,
            'homework' => $log->homework ?? null,
            'observations' => $log->observations ?? null,
        ];

        $this->content = $slides;
    }

    private function extractFirstHeading(string $html): ?string
    {
        if (preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $html, $m)) {
            return strip_tags($m[1]);
        }

        return null;
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

    public function switchTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function zoomIn(): void
    {
        $this->fontScale = min(4.0, $this->fontScale + 0.25);
    }

    public function zoomOut(): void
    {
        $this->fontScale = max(0.75, $this->fontScale - 0.25);
    }

    public function toggleTimer($minutes): void
    {
        $this->timerSeconds = $minutes * 60;
        $this->timerRunning = true;
    }

    public function stopTimer(): void
    {
        $this->timerRunning = false;
    }

    public function tickTimer(): void
    {
        if ($this->timerRunning && $this->timerSeconds > 0) {
            $this->timerSeconds--;
            if ($this->timerSeconds <= 0) {
                $this->timerRunning = false;
            }
        }
    }

    public function pickRandomStudent(): void
    {
        $log = DailyLog::with('schedule')->find($this->logId);
        if (! $log || ! $log->schedule) {
            return;
        }
        $students = Student::query()
            ->forSchedule($log->schedule)
            ->get();
        if ($students->isEmpty()) {
            return;
        }
        $picked = $students->random();
        $this->pickedStudents = [$picked->id];
    }

    public function toggleSpotlight(): void
    {
        $this->spotlight = ! $this->spotlight;
    }

    public function requestFullscreen(): void
    {
        $this->dispatch('request-fullscreen');
    }

    public function render()
    {
        $slides = $this->content;
        $current = $slides[$this->slideIndex] ?? null;
        $timerFormatted = sprintf('%02d:%02d', (int) floor($this->timerSeconds / 60), $this->timerSeconds % 60);

        return view('livewire.data-show-mode', [
            'slides' => $slides,
            'currentSlide' => $current,
            'slideIndex' => $this->slideIndex,
            'timerFormatted' => $timerFormatted,
            'timerRunning' => $this->timerRunning,
            'timerSeconds' => $this->timerSeconds,
            'pickedStudents' => $this->pickedStudents,
            'theme' => $this->theme,
            'fontScale' => $this->fontScale,
            'spotlight' => $this->spotlight,
            'logs' => $this->logs,
            'searchQuery' => $this->searchQuery,
        ]);
    }
}
