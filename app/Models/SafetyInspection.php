<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_id',
        'location',
        'inspection_date',
        'status',
    ];

    protected $casts = [
        'inspection_date' => 'date',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SafetyInspectionItem::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }
}