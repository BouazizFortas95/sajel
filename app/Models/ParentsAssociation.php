<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentsAssociation extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'association_name',
        'registration_number',
        'accreditation_date',
        'general_assembly_date',
        'president_name',
        'president_phone',
        'president_email',
        'vice_president_name',
        'secretary_general_name',
        'treasurer_name',
        'registered_parents_count',
        'active_membership_cards_count',
        'annual_subscription_fee',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'accreditation_date' => 'date',
            'general_assembly_date' => 'date',
            'registered_parents_count' => 'integer',
            'active_membership_cards_count' => 'integer',
            'annual_subscription_fee' => 'decimal:2',
        ];
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(ParentsAssociationMeeting::class, 'parents_association_id');
    }
}
