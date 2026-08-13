<?php

namespace App\Services\Incident;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncidentWorkflowService
{
    public function investigate(
        Incident $incident,
        User $user,
        ?string $notes = null
    ): Incident {
        return DB::transaction(function () use (
            $incident,
            $user,
            $notes
        ) {
            if ($incident->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only pending incidents can be investigated.',
                    ],
                ]);
            }

            $fromStatus = $incident->status;

            $incident->update([
                'status' => 'investigating',
            ]);

            $incident->statusHistories()->create([
                'user_id' => $user->id,
                'from_status' => $fromStatus,
                'to_status' => 'investigating',
                'notes' => $notes,
            ]);

            return $incident->fresh([
                'category',
                'reporter',
                'statusHistories',
            ]);
        });
    }

    public function resolve(
        Incident $incident,
        User $user,
        string $notes
    ): Incident {
        return DB::transaction(function () use (
            $incident,
            $user,
            $notes
        ) {
            if ($incident->status !== 'investigating') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only investigating incidents can be resolved.',
                    ],
                ]);
            }

            $fromStatus = $incident->status;

            $incident->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            $incident->statusHistories()->create([
                'user_id' => $user->id,
                'from_status' => $fromStatus,
                'to_status' => 'resolved',
                'notes' => $notes,
            ]);

            return $incident->fresh([
                'category',
                'reporter',
                'statusHistories',
            ]);
        });
    }
}