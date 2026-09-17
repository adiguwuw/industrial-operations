<?php

namespace App\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Capa extends Model
{
    protected $fillable = [
        'finding_id',
        'type',
        'action',
        'responsible_user_id',
        'due_date',
        'status',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}