<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Models\IncidentComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Incident $incident,
        public IncidentComment $comment,
    ) {
    }

    /**
     * Get the notification delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_comment',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_number,
            'incident_title' => $this->incident->title,
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->comment->user_id,
            'commenter_name' => $this->comment->user?->name ?? 'Someone',
            'comment' => $this->comment->comment,
        ];
    }
}