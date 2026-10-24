<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\LessonPhaseType;
use Carbon\CarbonInterface;
use Database\Factories\DailyLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LogicException;

class DailyLog extends Model
{
    /** @use HasFactory<DailyLogFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'substitute_teacher_id',
        'schedule_id',
        'curriculum_resource_id',
        'date',
        'title',
        'content_data',
        'actual_content',
        'homework',
        'observations',
        'status',
        'reviewed_by_id',
        'reviewed_at',
        'admin_notes',
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
            'reviewed_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function curriculumResource(): BelongsTo
    {
        return $this->belongsTo(CurriculumResource::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Materialize the roster as present attendance rows.
     *
     * This is idempotent: existing rows keep their status and remarks, while
     * students added to the group later are inserted as present.
     */
    public function ensureAttendanceRecords(): int
    {
        if (! $this->exists) {
            return 0;
        }

        $schedule = $this->schedule()->first(['id', 'classroom_id', 'group_id']);

        if (! $schedule) {
            return 0;
        }

        $studentIds = Student::query()
            ->forSchedule($schedule)
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $now = now();

        $rows = $studentIds
            ->map(fn (int|string $studentId): array => [
                'daily_log_id' => $this->getKey(),
                'student_id' => (int) $studentId,
                'status' => AttendanceStatus::Present->value,
                'remarks' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        return DB::table('attendances')->insertOrIgnore($rows);
    }

    public function scopeForDate(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForTeacher(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where(
            fn (Builder $q) => $q
                ->whereHas('schedule', fn (Builder $query) => $query->forTeacher($userId))
                ->orWhere('substitute_teacher_id', $userId)
        );
    }

    public function isPlanned(): bool
    {
        return $this->status === DailyLogStatus::Planned;
    }

    public function isCompleted(): bool
    {
        return $this->status === DailyLogStatus::Completed;
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    public function markAsReviewed(User $reviewer, ?string $notes = null): void
    {
        if (! $this->isCompleted()) {
            throw new LogicException('Only completed daily logs can be administratively reviewed.');
        }

        $this->reviewed_by_id = $reviewer->getKey();
        $this->reviewed_at = now();
        $this->admin_notes = $notes;
        $this->save();
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

        if ($this->curriculumResource) {
            $lines[] = 'المورد المعرفي: '.$this->curriculumResource->label();
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
