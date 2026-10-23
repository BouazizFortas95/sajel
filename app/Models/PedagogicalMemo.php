<?php

namespace App\Models;

use Database\Factories\PedagogicalMemoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedagogicalMemo extends Model
{
    /** @use HasFactory<PedagogicalMemoFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject_id',
        'level_id',
        'title',
        'phase',
        'competence',
        'starting_situation',
        'activities',
        'resources',
        'evaluation',
        'notes',
        'is_template',
        'is_archived',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activities' => 'array',
            'is_template' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function scopeForTeacher(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeTemplates(Builder $query): Builder
    {
        return $query->where('is_template', true);
    }

    /**
     * Number of lesson activities stored in the memo.
     */
    public function activityCount(): int
    {
        return is_array($this->activities) ? count($this->activities) : 0;
    }

    /**
     * Create a fresh, reusable copy owned by the given teacher.
     *
     * The copy keeps the pedagogical content but never inherits the archived
     * state, and is not automatically marked as a template: duplicating a
     * model memo is normally the first step towards adapting it for a class.
     */
    public function duplicateFor(User $user): self
    {
        $copy = $this->replicate(['is_archived']);
        $copy->user_id = $user->id;
        $copy->title = $this->title.' — '.__('نسخة');
        $copy->is_template = false;
        $copy->is_archived = false;
        $copy->save();

        return $copy;
    }

    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    public function restore(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * A readable label for select options and print headers.
     */
    public function display_name(): string
    {
        return implode(' — ', array_filter([
            $this->title,
            $this->subject?->name,
            $this->level?->name,
        ]));
    }
}
