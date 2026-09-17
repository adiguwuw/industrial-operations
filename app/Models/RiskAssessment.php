<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    protected $fillable = [
        'finding_id',
        'hazard',
        'likelihood',
        'severity',
        'risk_level',
        'existing_controls',
        'assessment_date',
    ];

    protected $casts = [
        'likelihood' => 'integer',
        'severity' => 'integer',
        'assessment_date' => 'date',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }
}