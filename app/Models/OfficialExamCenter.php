<?php

namespace App\Models;

use App\Enums\OfficialExamCenterStatus;
use App\Enums\OfficialExamType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialExamCenter extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'exam_type' => OfficialExamType::class,
        'session_year' => 'integer',
        'candidate_count' => 'integer',
        'room_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => OfficialExamCenterStatus::class,
    ];

    public function headUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(OfficialExamRoom::class, 'center_id')->orderBy('room_number');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(OfficialExamSupervisor::class, 'center_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(OfficialExamIncident::class, 'center_id')->latest('incident_date');
    }
}
