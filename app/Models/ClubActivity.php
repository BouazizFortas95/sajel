<?php

namespace App\Models;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubActivityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubActivity extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'activity_type' => ClubActivityType::class,
        'status' => ClubActivityStatus::class,
        'activity_date' => 'date',
        'attendees_count' => 'integer',
        'is_public' => 'boolean',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(SchoolClub::class, 'school_club_id');
    }

    /*
     * -------------------------------------------------------------
     * Scopes
     * -------------------------------------------------------------
     */

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ClubActivityStatus::Completed);
    }

    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('status', ClubActivityStatus::Planned);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('activity_date', '>=', now()->toDateString())
            ->whereIn('status', [ClubActivityStatus::Planned, ClubActivityStatus::Postponed]);
    }

    public function scopeByType(Builder $query, ClubActivityType|string $type): Builder
    {
        $val = $type instanceof ClubActivityType ? $type->value : $type;

        return $query->where('activity_type', $val);
    }

    /*
     * -------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------
     */

    public function formattedTime(): string
    {
        if ($this->start_time && $this->end_time) {
            return "{$this->start_time} - {$this->end_time}";
        }

        return $this->start_time ?: __('غير محدد');
    }
}
