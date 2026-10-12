<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
   protected $fillable = [
        'capa_id',
        'source_type',
        'source_id',
        'title',
        'description',
        'assigned_to',
        'assigned_by',
        'due_date',
        'status',
        'task_photo_path',
        'evidence_photo_path',
        'before_photo_path',
        'after_photo_path',
        'completion_notes',
        'started_at',
        'submitted_at',
        'validated_by',
        'validated_at',
        'validation_notes',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function capa(): BelongsTo
    {
        return $this->belongsTo(Capa::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}