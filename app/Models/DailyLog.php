<?php

namespace App\Models;

use App\Enums\DailyLogStatus;
use App\Enums\LessonPhaseType;
use Carbon\CarbonInterface;
use Database\Factories\DailyLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    /** @use HasFactory<DailyLogFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'schedule_id',
        'date',
        'title',
        'content_data',
        'actual_content',
        'homework',
        'observations',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'content_data' => 'array',
            'status' => DailyLogStatus::class,
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function scopeForDate(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForTeacher(Builder $query, User|int $user): Builder
    {
        return $query->whereHas('schedule', fn (Builder $query) => $query->forTeacher($user));
    }

    public function isPlanned(): bool
    {
        return $this->status === DailyLogStatus::Planned;
    }

    public function isCompleted(): bool
    {
        return $this->status === DailyLogStatus::Completed;
    }

    /**
     * Mark the log as completed and promote the prepared content
     * (or the given text) to the official class register entry.
     */
    public function markAsCompleted(?string $actualContent = null): self
    {
        $this->status = DailyLogStatus::Completed;
        $this->actual_content = filled($actualContent)
            ? $actualContent
            : $this->generateRegisterSummary();

        $this->save();

        return $this;
    }

    /**
     * Build a readable, summarized version of the dynamic `content_data`
     * blocks so it can act as the official entry in the class register book.
     */
    public function generateRegisterSummary(): string
    {
        $lines = ['العنوان: '.$this->title];

        if ($this->schedule) {
            $lines[] = 'الحصة: '.$this->schedule->label();
        }

        foreach ($this->content_data ?? [] as $block) {
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? [];

            match ($type) {
                'middle_school_lesson' => $this->appendMiddleSchoolLesson($lines, $data),
                'general_lesson' => $this->appendGeneralLesson($lines, $data),
                'warmup' => $this->appendWarmup($lines, $data),
                default => null,
            };
        }

        if (filled($this->homework)) {
            $lines[] = 'الواجب المنزلي: '.$this->homework;
        }

        if (filled($this->observations)) {
            $lines[] = 'ملاحظات: '.$this->observations;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $lines
     * @param  array<string, mixed>  $data
     */
    protected function appendMiddleSchoolLesson(array &$lines, array $data): void
    {
        if (filled($data['lesson_title'] ?? null)) {
            $lines[] = 'عنوان الدرس: '.$data['lesson_title'];
        }

        if (filled($data['competence'] ?? null)) {
            $lines[] = 'الكفاءة المستهدفة: '.$data['competence'];
        }

        if (filled($data['pedagogical_situation'] ?? null)) {
            $lines[] = 'الموقف البيداغوجي: '.$data['pedagogical_situation'];
        }

        foreach ($data['phases'] ?? [] as $index => $phase) {
            $label = LessonPhaseType::tryFrom($phase['type'] ?? '')?->label() ?? 'مرحلة';
            $duration = filled($phase['duration'] ?? null) ? " ({$phase['duration']} د)" : '';

            $lines[] = sprintf(
                'المرحلة %d — %s%s: %s',
                $index + 1,
                $label,
                $duration,
                trim(($phase['description'] ?? '').' '.($phase['strategy'] !== '' && filled($phase['strategy'] ?? null) ? "[{$phase['strategy']}]" : '')),
            );
        }
    }

    /**
     * @param  list<string>  $lines
     * @param  array<string, mixed>  $data
     */
    protected function appendGeneralLesson(array &$lines, array $data): void
    {
        if (filled($data['title'] ?? null)) {
            $lines[] = 'المحتوى: '.$data['title'];
        }

        if (filled($data['objectives'] ?? null)) {
            $lines[] = 'الأهداف: '.$data['objectives'];
        }

        if (filled($data['content'] ?? null)) {
            $lines[] = $data['content'];
        }
    }

    /**
     * @param  list<string>  $lines
     * @param  array<string, mixed>  $data
     */
    protected function appendWarmup(array &$lines, array $data): void
    {
        $duration = filled($data['duration'] ?? null) ? " ({$data['duration']} د)" : '';
        $lines[] = 'تمهيد'.$duration.': '.($data['description'] ?? '');
    }
}
