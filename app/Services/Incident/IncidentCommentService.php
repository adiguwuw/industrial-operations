<?php

namespace App\Services\Incident;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use App\Notifications\IncidentCommentNotification;

class IncidentCommentService
{
    /**
     * Create a new comment and notify the incident owner.
     */
    public function create(
        Incident $incident,
        User $user,
        string $comment
    ): IncidentComment {
        $incidentComment = $incident->comments()->create([
            'user_id' => $user->id,
            'comment' => $comment,
        ])->load('user');

        // Do not notify the user about their own comment.
        if ($incident->user_id !== $user->id) {
            $incident->loadMissing('reporter');

            if ($incident->reporter) {
                $incident->reporter->notify(
                    new IncidentCommentNotification(
                        $incident,
                        $incidentComment
                    )
                );
            }
        }

        return $incidentComment;
    }
}