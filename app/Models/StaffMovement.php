<?php

namespace App\Models;

use App\Enums\StaffMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMovement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'decision_date' => 'date',
        'movement_type' => StaffMovementType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => __('قيد الدراسة والاعتماد'),
            'approved' => __('مُعتمد ومؤشر'),
            'in_progress' => __('ساري المفعول'),
            'completed' => __('منجز ومكتمل'),
            'rejected' => __('مرفوض'),
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }
}
