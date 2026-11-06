<?php

namespace App\Models;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClub extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => ClubType::class,
        'status' => ClubStatus::class,
        'meeting_day' => DayOfWeek::class,
        'max_members' => 'integer',
    ];

    public function leadTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_teacher_id');
    }

    public function assistantTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_teacher_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClubMembership::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'club_memberships')
            ->withPivot(['role', 'joined_at', 'status', 'notes'])
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClubActivity::class);
    }

    /*
     * -------------------------------------------------------------
     * Scopes
     * -------------------------------------------------------------
     */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClubStatus::Active);
    }

    public function scopeByYear(Builder $query, string $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByType(Builder $query, ClubType|string $type): Builder
    {
        $val = $type instanceof ClubType ? $type->value : $type;

        return $query->where('type', $val);
    }

    /*
     * -------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------
     */

    public function activeMembersCount(): int
    {
        return $this->memberships()->where('status', ClubMemberStatus::Active)->count();
    }

    public function completedActivitiesCount(): int
    {
        return $this->activities()->where('status', ClubActivityStatus::Completed)->count();
    }

    public function isFull(): bool
    {
        return $this->activeMembersCount() >= $this->max_members;
    }

    /**
     * Return club executive bureau members (President, Vice-President, Secretary).
     */
    public function bureauMembers(): Collection
    {
        return $this->memberships()
            ->with('student.group.classroom')
            ->whereIn('role', [
                ClubMemberRole::President,
                ClubMemberRole::VicePresident,
                ClubMemberRole::Secretary,
            ])
            ->where('status', ClubMemberStatus::Active)
            ->get();
    }

    public function formattedMeetingSchedule(): string
    {
        $day = $this->meeting_day?->label();
        $time = $this->meeting_time;
        $loc = $this->meeting_location;

        $parts = array_filter([$day, $time, $loc]);

        return $parts ? implode(' — ', $parts) : __('غير محدد');
    }
}
