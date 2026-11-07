<?php

namespace App\Models;

use App\Enums\SchoolBoardMemberCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBoardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'member_category',
        'institution_role',
        'user_id',
        'member_name',
        'mandate_start_date',
        'mandate_end_date',
        'is_active',
        'phone_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'member_category' => SchoolBoardMemberCategory::class,
            'mandate_start_date' => 'date',
            'mandate_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
