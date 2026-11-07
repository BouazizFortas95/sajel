<?php

namespace App\Models;

use App\Enums\AscsDisciplineCategory;
use App\Enums\SportsGenderTarget;
use App\Enums\StudentAgeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportsCulturalDiscipline extends Model
{
    use HasFactory;

    protected $table = 'sports_cultural_disciplines';

    protected $fillable = [
        'discipline_code',
        'name_ar',
        'name_fr',
        'category',
        'supervising_teacher_name',
        'supervising_user_id',
        'training_schedule',
        'target_genders',
        'age_category',
        'max_participants',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => AscsDisciplineCategory::class,
            'target_genders' => SportsGenderTarget::class,
            'age_category' => StudentAgeCategory::class,
            'max_participants' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function supervisingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervising_user_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(StudentSportsLicense::class, 'discipline_id');
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(SportsCompetitionEvent::class, 'discipline_id');
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(SportsEquipmentInventory::class, 'discipline_id');
    }

    public function activeLicensesCount(string $academicYear): int
    {
        return $this->licenses()
            ->where('academic_year', $academicYear)
            ->where('status', 'Active')
            ->count();
    }
}

