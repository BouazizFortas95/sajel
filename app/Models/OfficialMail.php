<?php

namespace App\Models;

use App\Enums\MailConfidentiality;
use App\Enums\MailStatus;
use App\Enums\OfficialMailType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialMail extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_number',
        'mail_type',
        'mail_date',
        'sender_name',
        'recipient_name',
        'subject',
        'reference_number',
        'confidentiality',
        'attachment_path',
        'response_deadline',
        'status',
        'notes',
        'assigned_to',
        'recorded_by',
    ];

    protected $casts = [
        'mail_date'         => 'date',
        'response_deadline' => 'date',
        'mail_type'         => OfficialMailType::class,
        'confidentiality'   => MailConfidentiality::class,
        'status'            => MailStatus::class,
    ];

    // ───── Relationships ─────

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ───── Helpers ─────

    public function isOverdue(): bool
    {
        return $this->response_deadline !== null
            && $this->response_deadline->isPast()
            && $this->status !== MailStatus::Processed
            && $this->status !== MailStatus::Archived;
    }

    public function isIncoming(): bool
    {
        return $this->mail_type === OfficialMailType::Incoming;
    }

    public function isOutgoing(): bool
    {
        return $this->mail_type === OfficialMailType::Outgoing;
    }
}

