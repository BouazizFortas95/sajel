<?php

namespace App\Models;

use App\Enums\DisciplinarySanction;
use App\Enums\DisciplinaryStatus;
use App\Enums\InfractionDegree;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryRecord extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'incident_date' => 'date',
        'session_date' => 'datetime',
        'summons_sent_at' => 'date',
        'exclusion_start_date' => 'date',
        'exclusion_end_date' => 'date',
        'deliberated_at' => 'date',
        'guardian_notified_at' => 'date',
        'guardian_attended' => 'boolean',
        'compensation_amount' => 'decimal:2',
        'attendees' => 'array',
        'infraction_degree' => InfractionDegree::class,
        'decision' => DisciplinarySanction::class,
        'status' => DisciplinaryStatus::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function president(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_id');
    }

    /*
     * -------------------------------------------------------------
     * Aliases & Accessors / Mutators for Flexible Cross-Domain Use
     * -------------------------------------------------------------
     */

    public function getDegreeAttribute(): ?InfractionDegree
    {
        return $this->infraction_degree;
    }

    public function setDegreeAttribute($value): void
    {
        $this->attributes['infraction_degree'] = $value instanceof InfractionDegree ? $value->value : $value;
    }

    public function getHearingDateAttribute()
    {
        return $this->session_date;
    }

    public function setHearingDateAttribute($value): void
    {
        $this->attributes['session_date'] = $value;
    }

    public function getSanctionAttribute(): ?DisciplinarySanction
    {
        return $this->decision;
    }

    public function setSanctionAttribute($value): void
    {
        $this->attributes['decision'] = $value instanceof DisciplinarySanction ? $value->value : $value;
    }

    public function getRestitutionAmountAttribute()
    {
        return $this->compensation_amount;
    }

    public function setRestitutionAmountAttribute($value): void
    {
        $this->attributes['compensation_amount'] = $value;
    }

    public function getDefenseSummaryAttribute()
    {
        return $this->student_statement;
    }

    public function setDefenseSummaryAttribute($value): void
    {
        $this->attributes['student_statement'] = $value;
    }

    public function getDecisionNotesAttribute()
    {
        return $this->notes;
    }

    public function setDecisionNotesAttribute($value): void
    {
        $this->attributes['notes'] = $value;
    }

    public function getGuardianSummonedAttribute(): bool
    {
        return ! is_null($this->summons_sent_at);
    }

    public function setGuardianSummonedAttribute($value): void
    {
        $this->attributes['summons_sent_at'] = $value ? ($this->summons_sent_at ?? now()) : null;
    }

    public function getGuardianSummonedAtAttribute()
    {
        return $this->summons_sent_at;
    }

    public function setGuardianSummonedAtAttribute($value): void
    {
        $this->attributes['summons_sent_at'] = $value;
    }

    public function getGuardianNotifiedAttribute(): bool
    {
        return ! is_null($this->guardian_notified_at);
    }

    public function setGuardianNotifiedAttribute($value): void
    {
        $this->attributes['guardian_notified_at'] = $value ? ($this->guardian_notified_at ?? now()) : null;
    }

    public function isScheduled(): bool
    {
        return $this->status === DisciplinaryStatus::Scheduled;
    }

    public function isDeliberated(): bool
    {
        return $this->status === DisciplinaryStatus::Deliberated || $this->status === DisciplinaryStatus::Executed;
    }

    public function isExclusion(): bool
    {
        return in_array($this->decision, [
            DisciplinarySanction::TemporaryExclusion,
            DisciplinarySanction::DefinitiveExclusion,
        ], true);
    }

    public function hasFinancialCompensation(): bool
    {
        return $this->decision === DisciplinarySanction::FinancialCompensation
            || ($this->compensation_amount !== null && (float) $this->compensation_amount > 0);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', DisciplinaryStatus::Scheduled);
    }

    public function scopeDeliberated(Builder $query): Builder
    {
        return $query->whereIn('status', [DisciplinaryStatus::Deliberated, DisciplinaryStatus::Executed]);
    }

    public function scopeForAcademicYear(Builder $query, ?string $year = null): Builder
    {
        $targetYear = $year ?? SchoolSetting::current()?->academic_year ?? '2026/2027';
        $altYear = str_contains($targetYear, '/') ? str_replace('/', '-', $targetYear) : str_replace('-', '/', $targetYear);

        return $query->where(function (Builder $q) use ($targetYear, $altYear): void {
            $q->where('academic_year', $targetYear)
                ->orWhere('academic_year', $altYear);
        });
    }
}
