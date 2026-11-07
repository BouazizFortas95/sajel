<?php

namespace App\Models;

use App\Enums\OfficialExamIncidentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialExamIncident extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'incident_type' => OfficialExamIncidentType::class,
        'incident_date' => 'date',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(OfficialExamCenter::class, 'center_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(OfficialExamRoom::class, 'room_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
