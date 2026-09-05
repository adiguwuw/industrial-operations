<?php

namespace App\Services\Incident;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\IncidentPhoto;
use App\Models\IncidentStatusHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class IncidentPhotoService
{
    public function upload(
        Incident $incident,
        User $user,
        UploadedFile $photo,
        ?IncidentComment $comment = null,
        ?IncidentStatusHistory $statusHistory = null,
    ): IncidentPhoto {
        $path = $photo->store(
            "incidents/{$incident->id}",
            'local'
        );

        return $incident->photos()->create([
            'comment_id' => $comment?->id,
            'status_history_id' => $statusHistory?->id,
            'file_path' => $path,
            'original_name' => $photo->getClientOriginalName(),
            'mime_type' => $photo->getMimeType(),
            'file_size' => $photo->getSize(),
        ]);
    }

    public function delete(IncidentPhoto $photo): void
    {
        if ($photo->file_path) {
            Storage::disk('local')->delete($photo->file_path);
        }

        $photo->delete();
    }
}