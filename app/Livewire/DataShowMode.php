<?php

namespace App\Livewire;

use App\Enums\LessonPhaseType;
use App\Models\BehaviorObservation;
use App\Models\DailyLog;
use App\Models\Student;
use App\Services\StudentMotivationService;
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
            ->with(['schedule.subject', 'schedule.classroom', 'schedule.group.classroom'])
            ->whereHas('schedule', fn ($q) => $q->forTeacher(auth()->id()))
            ->orderByDesc('date')
            ->limit(10)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'label' => $l->date?->format('d/m/Y').' — '.($l->schedule?->sectionName() ?? '').' — '.$l->title,
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

        $log = DailyLog::with('schedule.subject', 'schedule.classroom', 'schedule.group.classroom')->find($this->logId);

        if (! $log) {
            $this->content = [];

            return;
        }

        $slides = [];
        $lessonData = $this->lessonData($log->content_data ?? []);
        $competence = $lessonData['competence'] ?? $log->content_data['competence'] ?? null;
        $startingSituation = $lessonData['starting_situation']
            ?? $lessonData['pedagogical_situation']
            ?? $log->content_data['starting_situation']
            ?? $log->content_data['pedagogical_situation']
            ?? null;
        $startingSituation = match ($startingSituation) {
            'daily_life' => __('سياق حياتي'),
            'problem' => __('وضعية إشكالية'),
            'project' => __('مشروع'),
            'review' => __('مراجعة'),
            default => $startingSituation,
        };
        $lessonTitle = $lessonData['lesson_title'] ?? $log->title;

        // Slide 1 — Cover
        $cover = [
            'type' => 'cover',
            'title' => $log->title,
            'subtitle' => ($log->schedule?->subject?->name ?? '').' — '.($log->schedule?->sectionName() ?? '').' — '.($log->date?->format('l, d/m/Y') ?? today()->format('l, d/m/Y')),
            'competence' => $competence ?? ($log->actual_content ? $this->extractFirstHeading($log->actual_content) : null),
            'starting_situation' => $startingSituation,
            'subject_color' => $log->schedule?->subject?->color ?? 'amber',
        ];
        $cover['narration_text'] = $this->buildNarrationText([
            [__('عنوان الشريحة'), $lessonTitle],
            [__('الكفاءة المستهدفة'), $cover['competence']],
            [__('وضعية الانطلاق'), $cover['starting_situation']],
        ]);
        $slides[] = $cover;

        $phases = $lessonData['phases']
            ?? $log->content_data['phases']
            ?? $log->content_data['lesson_phases']
            ?? [];

        foreach ($phases as $i => $phase) {
            if (! is_array($phase)) {
                continue;
            }
            $phaseType = LessonPhaseType::tryFrom($phase['type'] ?? '');
            $phaseTitle = $phase['phase_name']
                ?? $phaseType?->label()
                ?? ($phase['type'] ?? __('مرحلة'));
            $phaseContent = $phase['description'] ?? $phase['content'] ?? null;
            $phaseSlide = [
                'type' => 'phase',
                'phase_number' => $i + 1,
                'title' => $phaseTitle,
                'description' => $phaseContent,
                'duration' => $phase['duration'] ?? null,
            ];
            $phaseSlide['narration_text'] = $this->buildNarrationText([
                [__('عنوان الشريحة'), $lessonTitle],
                [__('عنوان المرحلة'), $phaseTitle],
                [__('محتوى المرحلة'), $phaseContent],
            ]);
            $slides[] = $phaseSlide;
        }

        // Final slide — register summary + homework
        $summary = [
            'type' => 'summary',
            'title' => __('الملخص والواجب المنزلي'),
            'register' => $log->actual_content ?? $log->content_data['actual_content'] ?? null,
            'homework' => $log->homework ?? null,
            'observations' => $log->observations ?? null,
        ];
        $summary['narration_text'] = $this->buildNarrationText([
            [__('عنوان الشريحة'), $summary['title']],
            [__('محتوى الحصة'), $summary['register']],
            [__('الواجب المنزلي'), $summary['homework']],
            [__('ملاحظات'), $summary['observations']],
        ]);
        $slides[] = $summary;

        $this->content = $slides;
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $contentData
     * @return array<string, mixed>
     */
    private function lessonData(array $contentData): array
    {
        foreach ($contentData as $block) {
            if (
                is_array($block)
                && in_array($block['type'] ?? null, ['lesson_structure', 'middle_school_lesson'], true)
                && is_array($block['data'] ?? null)
            ) {
                return [
                    ...$block['data'],
                    'phases' => $block['data']['lesson_phases'] ?? $block['data']['phases'] ?? [],
                ];
            }
        }

        return $contentData;
    }

    /**
     * @param  list<array{0: string, 1: mixed}>  $sections
     */
    private function buildNarrationText(array $sections): string
    {
        return collect($sections)
            ->map(function (array $section): ?string {
                $text = $this->plainText($section[1]);

                return $text === null ? null : $section[0].': '.$text;
            })
            ->filter()
            ->implode(' — ');
    }

    private function plainText(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = preg_replace(
            '/<(?:br|\/p|\/div|\/h[1-6]|\/li)\b[^>]*>/i',
            ' ',
            (string) $value,
        ) ?? (string) $value;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[\s\p{Z}]+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return $text === '' ? null : $text;
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

    /**
     * Quick instant kudos awarded by a teacher during Data Show projection.
     */
    public function awardQuickKudos(int $studentId, string $type): void
    {
        $log = DailyLog::with(['schedule.user', 'schedule.classroom', 'schedule.group.classroom'])
            ->find($this->logId);

        if (! $log || ! $log->schedule) {
            return;
        }

        $teacher = auth()->user();
        if (! $teacher || $log->schedule->user_id !== $teacher->id) {
            return;
        }

        $student = Student::query()
            ->forSchedule($log->schedule)
            ->whereKey($studentId)
            ->first();

        if (! $student) {
            return;
        }

        $category = $type === 'kudos_excellence' ? 'positive' : 'positive';
        $note = $type === 'kudos_excellence'
            ? __('إجابة متميزة — تقدير سريع من المسلاط')
            : __('مشاركة شجاعة — تشجيع مباشر');

        BehaviorObservation::create([
            'student_id' => $student->id,
            'schedule_id' => $log->schedule->id,
            'category' => $category,
            'notes' => $note,
            'date' => $log->date ?? today(),
        ]);

        $this->dispatch('kudos-awarded', studentName: $student->full_name, type: $type);
    }

    /**
     * Classroom synergy percentage for the active schedule's classroom.
     */
    public function classroomSynergyPercent(): int
    {
        $log = DailyLog::with('schedule')->find($this->logId);

        if (! $log || ! $log->schedule) {
            return 0;
        }

        $classroom = $log->schedule->resolvedClassroom();

        if (! $classroom) {
            return 0;
        }

        $service = app(StudentMotivationService::class);
        $metrics = $service->classroomMetrics($classroom);

        return $metrics['synergy_percentage'] ?? 0;
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
            'speechLocale' => match (app()->getLocale()) {
                'ar' => 'ar-SA',
                'fr' => 'fr-FR',
                'en' => 'en-US',
                default => 'ar-SA',
            },
        ]);
    }
}
