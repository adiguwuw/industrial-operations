<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RiskAssessment;
use App\Models\Capa;

class Finding extends Model
{
    use HasFactory;

    protected $fillable = [
        'safety_inspection_id',
        'safety_inspection_item_id',
        'description',
    ];

    public function safetyInspection(): BelongsTo
    {
        return $this->belongsTo(SafetyInspection::class);
    }

    public function safetyInspectionItem(): BelongsTo
    {
        return $this->belongsTo(SafetyInspectionItem::class);
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    public function capas(): HasMany
    {
        return $this->hasMany(Capa::class);
    }
}