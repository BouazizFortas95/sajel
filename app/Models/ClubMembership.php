<?php

namespace App\Models;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMembership extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'role' => ClubMemberRole::class,
        'status' => ClubMemberStatus::class,
        'joined_at' => 'date',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(SchoolClub::class, 'school_club_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /*
     * -------------------------------------------------------------
     * Scopes
     * -------------------------------------------------------------
     */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClubMemberStatus::Active);
    }

    public function scopeBureau(Builder $query): Builder
    {
        return $query->whereIn('role', [
            ClubMemberRole::President,
            ClubMemberRole::VicePresident,
            ClubMemberRole::Secretary,
        ]);
    }

    public function scopeByRole(Builder $query, ClubMemberRole|string $role): Builder
    {
        $val = $role instanceof ClubMemberRole ? $role->value : $role;

        return $query->where('role', $val);
    }

    /*
     * -------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------
     */

    public function isBureau(): bool
    {
        return $this->role?->isBureau() ?? false;
    }

    public function certificateReferenceNumber(): string
    {
        return sprintf(
            'CERT-CLUB-%s-%03d-%04d',
            str_replace('-', '', $this->club?->academic_year ?? '20262027'),
            $this->school_club_id,
            $this->id
        );
    }
}
