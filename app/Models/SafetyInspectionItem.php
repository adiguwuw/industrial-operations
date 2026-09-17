<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class SafetyInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'safety_inspection_id',
        'checklist_item_id',
        'question',
        'sort_order',
        'is_required',
        'result',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function safetyInspection(): BelongsTo
    {
        return $this->belongsTo(SafetyInspection::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }
}