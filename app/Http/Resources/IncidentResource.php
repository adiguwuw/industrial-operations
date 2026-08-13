<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'incident_number' => $this->incident_number,

            'title' => $this->title,
            'description' => $this->description,

            'severity' => $this->severity,
            'status' => $this->status,

            'location' => [
                'address' => $this->location_address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            'reported_at' => $this->reported_at,
            'resolved_at' => $this->resolved_at,

            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),

            'reporter' => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}