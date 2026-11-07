<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolCooperative extends Model
{
    use HasFactory;

    protected $table = 'school_cooperatives';

    protected $fillable = [
        'academic_year',
        'cooperative_name',
        'approval_decree_number',
        'foundation_date',
        'motto_or_slogan',
        'coordinator_teacher_id',
        'student_president_name',
        'student_vice_president_name',
        'student_secretary_name',
        'student_treasurer_name',
        'total_members_count',
        'treasury_balance',
        'annual_action_plan',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'foundation_date' => 'date',
            'total_members_count' => 'integer',
            'treasury_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function coordinatorTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_teacher_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(CooperativeProjectsInitiative::class, 'school_cooperative_id');
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(CooperativeFinancialLedger::class, 'school_cooperative_id');
    }
}
