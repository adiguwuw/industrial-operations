<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentPhoto extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'incident_id',
        'comment_id',
        'status_history_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(IncidentComment::class);
    }

    public function statusHistory(): BelongsTo
    {
        return $this->belongsTo(IncidentStatusHistory::class);
    }
}