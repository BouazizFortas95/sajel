<?php

namespace App\Models;

use Database\Factories\ActiveLearningStrategyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveLearningStrategy extends Model
{
    /** @use HasFactory<ActiveLearningStrategyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'tags',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
