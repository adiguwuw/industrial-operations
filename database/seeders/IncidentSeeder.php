<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\IncidentCategory;
use Illuminate\Database\Seeder;
use App\Services\IncidentNumberService;

class IncidentSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Incident Categories
        |--------------------------------------------------------------------------
        */

        $categories = [
            [
                'name' => 'Unsafe Action',
                'description' => 'Tindakan tidak aman yang berpotensi menyebabkan insiden.',
            ],
            [
                'name' => 'Unsafe Condition',
                'description' => 'Kondisi lingkungan kerja yang berpotensi menyebabkan insiden.',
            ],
            [
                'name' => 'Near Miss',
                'description' => 'Kejadian hampir celaka yang tidak menimbulkan cedera.',
            ],
            [
                'name' => 'Work Accident',
                'description' => 'Kecelakaan yang terjadi dalam aktivitas kerja.',
            ],
            [
                'name' => 'Fire',
                'description' => 'Insiden yang berkaitan dengan kebakaran atau potensi kebakaran.',
            ],
            [
                'name' => 'Environmental Incident',
                'description' => 'Insiden yang berdampak terhadap lingkungan.',
            ],
            [
                'name' => 'Equipment Damage',
                'description' => 'Kerusakan peralatan akibat kejadian operasional.',
            ],
            [
                'name' => 'Other',
                'description' => 'Insiden lain yang tidak termasuk kategori sebelumnya.',
            ],
        ];

        foreach ($categories as $category) {
            IncidentCategory::firstOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Incidents
        |--------------------------------------------------------------------------
        */

        $numberService = app(IncidentNumberService::class);

        for ($i = 0; $i < 20; $i++) {
            Incident::factory()->create([
                'incident_number' => $numberService->generate(),
            ]);
        }
    }
}