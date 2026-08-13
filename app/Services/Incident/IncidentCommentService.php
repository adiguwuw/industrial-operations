<?php

namespace App\Services\Incident;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;

class IncidentCommentService
{
    /**
     * Create a new class instance.
     */
    public function create(
        Incident $incident,
        User $user,
        string $comment
    ): IncidentComment {
        return $incident->comments()->create([
            'user_id' => $user->id,
            'comment' => $comment,
        ])->load('user');
    }
}
