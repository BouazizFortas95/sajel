<?php

namespace App\Models;

use App\Enums\BoardingExitPassStatus;
use App\Enums\BoardingExitPassType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardingExitPass extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'pass_type' => BoardingExitPassType::class,
        'departure_datetime' => 'datetime',
        'expected_return_datetime' => 'datetime',
        'actual_return_datetime' => 'datetime',
        'status' => BoardingExitPassStatus::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
