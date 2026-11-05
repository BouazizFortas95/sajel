<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'registration_number',
        'matricule_number',
        'national_id_number',
        'date_of_birth',
        'place_of_birth',
        'group_id',
        'enrollment_date',
        'departure_date',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
            'departure_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Students that belong to the roster targeted by a schedule.
     *
     * A schedule with a specific group targets only that group. Otherwise, it
     * targets every group in the classroom. A schedule with neither targets
     * nobody rather than accidentally returning the whole school.
     */
    public function scopeForSchedule(Builder $query, Schedule $schedule): Builder
    {
        if (filled($schedule->group_id)) {
            return $query->where('group_id', $schedule->group_id);
        }

        if (filled($schedule->classroom_id)) {
            return $query->whereHas(
                'group',
                fn (Builder $query): Builder => $query->where('classroom_id', $schedule->classroom_id),
            );
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * The student's full name, used by Filament tables and reports.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    public function loans(): HasMany
    {
        return $this->hasMany(StudentTextbookLoan::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StudentTransfer::class)->orderByDesc('transfer_date');
    }

    public function latestTransfer(): HasOne
    {
        return $this->hasOne(StudentTransfer::class)->latestOfMany('transfer_date');
    }

    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class)->orderByDesc('session_date');
    }
}
