<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'classroom_id',
        'group_id',
        'subject_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function scopeForTeacher(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeOnDay(Builder $query, DayOfWeek $day): Builder
    {
        return $query->where('day_of_week', $day->value);
    }

    /**
     * Resolve the classroom that owns this session.
     *
     * A schedule can target a whole classroom (`classroom_id`) or a single
     * group inside it (`group_id`). Both shapes must resolve to the same
     * classroom so labels, registers, and reports never depend on the shape
     * chosen when the schedule was created.
     */
    public function resolvedClassroom(): ?Classroom
    {
        return $this->classroom ?? $this->group?->classroom;
    }

    /**
     * Localized section name. The group name is only a last-resort fallback
     * for legacy schedules that have neither a classroom nor a classified group.
     */
    public function sectionName(): ?string
    {
        return $this->resolvedClassroom()?->name ?? $this->group?->name;
    }

    /**
     * Section label with the group kept as a secondary qualifier.
     * Example: "1 م 1 — المجموعة أ"
     */
    public function placeLabel(): ?string
    {
        $section = $this->sectionName();

        if (blank($section)) {
            return null;
        }

        $group = $this->group?->name;

        return filled($group) && $group !== $section
            ? $section.' — '.$group
            : $section;
    }

    /**
     * Human-readable label used across Filament selects and tables.
     * Example: "الاثنين 08:00-09:00 | الرياضيات | 1 م 1 — المجموعة أ"
     */
    public function label(): string
    {
        return implode(' | ', array_filter([
            sprintf(
                '%s (%s - %s)',
                $this->day_of_week?->label(),
                $this->start_time?->format('H:i'),
                $this->end_time?->format('H:i'),
            ),
            $this->subject?->name,
            $this->placeLabel(),
        ]));
    }
}
