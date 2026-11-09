<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
        'valid_from',
        'valid_until',
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
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->user();
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

    public function scopeActiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        $d = $date instanceof CarbonInterface ? $date->toDateString() : (string) $date;

        return $query
            ->where(function (Builder $q) use ($d): void {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $d);
            })
            ->where(function (Builder $q) use ($d): void {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $d);
            });
    }

    public function scopeActiveBetween(Builder $query, CarbonInterface|string $start, CarbonInterface|string $end): Builder
    {
        $s = $start instanceof CarbonInterface ? $start->toDateString() : (string) $start;
        $e = $end instanceof CarbonInterface ? $end->toDateString() : (string) $end;

        return $query
            ->where(function (Builder $q) use ($e): void {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $e);
            })
            ->where(function (Builder $q) use ($s): void {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $s);
            });
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $this->scopeActiveOn($query, today());
    }

    public function scopeExpired(Builder $query, ?CarbonInterface $reference = null): Builder
    {
        $ref = ($reference ?? today())->toDateString();

        return $query->whereNotNull('valid_until')->where('valid_until', '<', $ref);
    }

    public function scopeUpcoming(Builder $query, ?CarbonInterface $reference = null): Builder
    {
        $ref = ($reference ?? today())->toDateString();

        return $query->whereNotNull('valid_from')->where('valid_from', '>', $ref);
    }

    public function isActiveOn(CarbonInterface|string $date): bool
    {
        $d = $date instanceof CarbonInterface ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        if ($this->valid_from && $this->valid_from->startOfDay()->isAfter($d)) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->startOfDay()->isBefore($d)) {
            return false;
        }

        return true;
    }

    public function isCurrentlyActive(): bool
    {
        return $this->isActiveOn(today());
    }

    public function isExpired(?CarbonInterface $reference = null): bool
    {
        if ($this->valid_until === null) {
            return false;
        }

        $ref = ($reference ?? today())->startOfDay();

        return $this->valid_until->startOfDay()->isBefore($ref);
    }

    public function isUpcoming(?CarbonInterface $reference = null): bool
    {
        if ($this->valid_from === null) {
            return false;
        }

        $ref = ($reference ?? today())->startOfDay();

        return $this->valid_from->startOfDay()->isAfter($ref);
    }

    public function overlapsDateRange(?string $from, ?string $until): bool
    {
        $existingFrom = $this->valid_from?->toDateString();
        $existingUntil = $this->valid_until?->toDateString();

        $fromValid = ($existingUntil === null || $from === null || $from <= $existingUntil);
        $untilValid = ($until === null || $existingFrom === null || $existingFrom <= $until);

        return $fromValid && $untilValid;
    }

    public function validityStatusLabel(): string
    {
        if ($this->isExpired()) {
            return __('منتهي الصلاحية / مقصى');
        }

        if ($this->isUpcoming()) {
            return __('مستقبلي (يبدأ لاحقاً)');
        }

        return __('ساري المفعول');
    }

    public function validityPeriodLabel(): string
    {
        if ($this->valid_from && $this->valid_until) {
            return __('من :from إلى :until', [
                'from' => $this->valid_from->format('Y-m-d'),
                'until' => $this->valid_until->format('Y-m-d'),
            ]);
        }

        if ($this->valid_from) {
            return __('ابتداءً من :from', ['from' => $this->valid_from->format('Y-m-d')]);
        }

        if ($this->valid_until) {
            return __('حتى :until', ['until' => $this->valid_until->format('Y-m-d')]);
        }

        return __('دائم وساري المفعول');
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
