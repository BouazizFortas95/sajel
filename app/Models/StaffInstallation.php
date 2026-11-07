<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'installation_date',
        'recruitment_order_ref',
        'role_label',
        'statutory_grade',
        'previous_institution',
        'ministry_decision_ref',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'installation_date' => 'date',
    ];

    // ───── Relationships ─────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ───── Helpers ─────

    /**
     * Returns the full staff name from the linked user (or fallback).
     */
    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? '—';
    }
}
