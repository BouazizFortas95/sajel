<?php

namespace App\Models;

use App\Enums\AssessmentType;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'schedule_id',
        'title',
        'type',
        'date',
        'max_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssessmentType::class,
            'date' => 'date',
            'max_score' => 'decimal:2',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Materialize an empty grade row for every student on the schedule roster.
     *
     * This is idempotent: existing scores and remarks are never overwritten,
     * while students added to the group or classroom later are inserted with
     * a null score.
     */
    public function ensureGradeRecords(): int
    {
        if (! $this->exists) {
            return 0;
        }

        $schedule = $this->schedule()->first(['id', 'classroom_id', 'group_id']);

        if (! $schedule) {
            return 0;
        }

        $studentIds = Student::query()
            ->forSchedule($schedule)
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $now = now();

        $rows = $studentIds
            ->map(fn (int|string $studentId): array => [
                'assessment_id' => $this->getKey(),
                'student_id' => (int) $studentId,
                'score' => null,
                'remarks' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        return DB::table('grades')->insertOrIgnore($rows);
    }
}
