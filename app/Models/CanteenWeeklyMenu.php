<?php

namespace App\Models;

use Database\Factories\CanteenWeeklyMenuFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanteenWeeklyMenu extends Model
{
    /** @use HasFactory<CanteenWeeklyMenuFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'week_number',
        'week_start_date',
        'sunday_menu',
        'monday_menu',
        'tuesday_menu',
        'wednesday_menu',
        'thursday_menu',
        'nutrition_calories_approx',
        'is_approved_by_doctor',
        'approved_by_principal',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'nutrition_calories_approx' => 'integer',
        'is_approved_by_doctor' => 'boolean',
        'approved_by_principal' => 'boolean',
    ];

    public function scopeForYear(Builder $query, string $year): Builder
    {
        return $query->where('academic_year', $year);
    }
}

