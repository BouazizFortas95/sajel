<?php

namespace App\Models;

use App\Enums\SafetyEquipmentStatus;
use App\Enums\SafetyEquipmentType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyFireEquipment extends Model
{
    use HasFactory;

    protected $table = 'safety_fire_equipment';

    protected $fillable = [
        'equipment_code',
        'equipment_type',
        'exact_location',
        'installation_date',
        'last_inspection_date',
        'next_inspection_due_date',
        'inspection_status',
        'inspector_entity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'last_inspection_date' => 'date',
            'next_inspection_due_date' => 'date',
            'equipment_type' => SafetyEquipmentType::class,
            'inspection_status' => SafetyEquipmentStatus::class,
        ];
    }

    public function isExpiredOrDue(): bool
    {
        return $this->next_inspection_due_date && $this->next_inspection_due_date->isPast();
    }

    public function isDueSoon(int $days = 30): bool
    {
        if (! $this->next_inspection_due_date) {
            return false;
        }

        return $this->next_inspection_due_date->isFuture() && $this->next_inspection_due_date->diffInDays(Carbon::today()) <= $days;
    }

    public function isOperational(): bool
    {
        return $this->inspection_status === SafetyEquipmentStatus::Operational && ! $this->isExpiredOrDue();
    }
}
