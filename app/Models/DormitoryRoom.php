<?php

namespace App\Models;

use App\Enums\DormitoryWing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DormitoryRoom extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'wing' => DormitoryWing::class,
        'floor_level' => 'integer',
        'bed_capacity' => 'integer',
        'active_occupants_count' => 'integer',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(StudentDormitoryAllocation::class, 'dormitory_room_id');
    }

    public function activeAllocations(): HasMany
    {
        return $this->hasMany(StudentDormitoryAllocation::class, 'dormitory_room_id')
            ->where('status', 'active');
    }
}
