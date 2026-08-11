<?php

namespace Database\Factories;

use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Incident>
 */
class IncidentFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement([
            'pending',
            'investigating',
            'resolved',
        ]);

        return [
            'incident_number' => fake()->unique()->numerify(
                'INC-' . now()->year . '-######'
            ),

            'user_id' => User::role('employee')
                ->inRandomOrder()
                ->value('id'),

            'category_id' => IncidentCategory::query()
                ->inRandomOrder()
                ->value('id'),

            'title' => fake()->sentence(6),

            'description' => fake()->paragraph(3),

            'location_address' => fake()->randomElement([
                'Area Produksi A',
                'Area Produksi B',
                'Warehouse Utama',
                'Maintenance Area',
                'Workshop',
                'Gudang Bahan Baku',
                'Loading Dock',
            ]),

            'latitude' => fake()->latitude(-8.2, -7.8),
            'longitude' => fake()->longitude(112.5, 113.0),

            'severity' => fake()->randomElement([
                'low',
                'medium',
                'high',
                'critical',
            ]),

            'status' => $status,

            'reported_at' => fake()->dateTimeBetween(
                '-30 days',
                'now'
            ),

            'resolved_at' => $status === 'resolved'
                ? fake()->dateTimeBetween('-20 days', 'now')
                : null,
        ];
    }
}