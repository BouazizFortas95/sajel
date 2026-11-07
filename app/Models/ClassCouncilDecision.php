<?php

namespace App\Models;

use App\Enums\CouncilHonors;
use App\Enums\CouncilSanctions;
use App\Enums\EndOfYearDecision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassCouncilDecision extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'trimester_average' => 'float',
        'annual_average' => 'float',
        'rank' => 'integer',
        'honors' => CouncilHonors::class,
        'sanctions' => CouncilSanctions::class,
        'end_of_year_decision' => EndOfYearDecision::class,
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassCouncilSession::class, 'class_council_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
