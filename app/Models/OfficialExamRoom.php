<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialExamRoom extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'room_number' => 'integer',
        'candidate_count' => 'integer',
        'supervisors_required' => 'integer',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(OfficialExamCenter::class, 'center_id');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(OfficialExamSupervisor::class, 'room_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(OfficialExamIncident::class, 'room_id');
    }
}

