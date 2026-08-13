<?php

namespace App\Services\Incident;

use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class IncidentPhotoService
{
    public function upload(
        Incident $incident,
        User $user,
        UploadedFile $photo
    ): IncidentPhoto {
        $path = $photo->store(
            "incidents/{$incident->id}",
            'public'
        );

        return $incident->photos()->create([
            'file_path' => $path,
            'original_name' => $photo->getClientOriginalName(),
            'mime_type' => $photo->getMimeType(),
            'file_size' => $photo->getSize(),
        ]);
    }

    public function delete(IncidentPhoto $photo): void
    {
        if ($photo->file_path) {
            \Storage::disk('public')->delete($photo->file_path);
        }

        $photo->delete();
    }
}