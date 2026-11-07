<?php

namespace App\Models;

use App\Enums\HandoverRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'handover_date',
        'outgoing_user_id',
        'incoming_user_id',
        'outgoing_name',
        'incoming_name',
        'inventory_summary',
        'inventory_ref',
        'pending_dossiers',
        'remarks',
        'outgoing_signed',
        'incoming_signed',
        'director_signed',
        'recorded_by',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'outgoing_signed' => 'boolean',
        'incoming_signed' => 'boolean',
        'director_signed' => 'boolean',
        'role' => HandoverRole::class,
    ];

    // ───── Relationships ─────

    public function outgoingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'outgoing_user_id');
    }

    public function incomingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incoming_user_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ───── Helpers ─────

    public function getOutgoingLabelAttribute(): string
    {
        return $this->outgoing_name ?? $this->outgoingUser?->name ?? '—';
    }

    public function getIncomingLabelAttribute(): string
    {
        return $this->incoming_name ?? $this->incomingUser?->name ?? '—';
    }

    public function isFullySigned(): bool
    {
        return $this->outgoing_signed && $this->incoming_signed && $this->director_signed;
    }
}
