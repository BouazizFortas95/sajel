<?php

namespace App\Models;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceWorkOrder extends Model
{
    use HasFactory;

    protected $table = 'maintenance_work_orders';

    protected $fillable = [
        'work_order_number',
        'academic_year',
        'facility_id',
        'exact_location',
        'category',
        'priority_level',
        'issue_description',
        'reported_by_user_id',
        'reported_by_name',
        'report_date',
        'scheduled_date',
        'completion_date',
        'intervention_type',
        'contractor_name',
        'estimated_cost',
        'actual_cost',
        'status',
        'technician_notes',
        'approved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'scheduled_date' => 'date',
            'completion_date' => 'date',
            'category' => MaintenanceCategory::class,
            'priority_level' => MaintenancePriority::class,
            'status' => MaintenanceStatus::class,
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(SchoolBuildingFacility::class, 'facility_id');
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function isPendingOrActive(): bool
    {
        return in_array($this->status, [
            MaintenanceStatus::Reported,
            MaintenanceStatus::Scheduled,
            MaintenanceStatus::InProgress,
        ], true);
    }

    public function isEmergency(): bool
    {
        return $this->priority_level === MaintenancePriority::EmergencySafety
            || $this->priority_level === MaintenancePriority::Urgent;
    }
}

