<?php

namespace App\Services;

use App\Models\Incident;

class IncidentNumberService
{
    public function generate(): string
    {
        $year = now()->year;

        $lastIncident = Incident::withTrashed()
            ->where('incident_number', 'like', "INC-{$year}-%")
            ->orderByDesc('incident_number')
            ->first();

        $nextNumber = $lastIncident
            ? ((int) substr($lastIncident->incident_number, -6)) + 1
            : 1;

        return sprintf(
            'INC-%d-%06d',
            $year,
            $nextNumber
        );
    }
}