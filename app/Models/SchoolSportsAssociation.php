<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSportsAssociation extends Model
{
    use HasFactory;

    protected $table = 'school_sports_associations';

    protected $fillable = [
        'association_name',
        'academic_year',
        'affiliation_number',
        'approval_receipt_number',
        'approval_date',
        'principal_president_name',
        'secretary_general_name',
        'treasurer_name',
        'executive_bureau_members',
        'annual_subscription_fee',
        'active_members_count',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'approval_date' => 'date',
            'executive_bureau_members' => 'array',
            'annual_subscription_fee' => 'decimal:2',
            'active_members_count' => 'integer',
        ];
    }
}

