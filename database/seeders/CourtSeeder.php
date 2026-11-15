<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            [
                'name' => 'Paddle Court 1',
                'description' => 'Lapangan paddle dengan fasilitas standar dan area bermain yang nyaman.',
                'image' => null,
                'price_per_hour' => 150000,
                'is_active' => true,
            ],
            [
                'name' => 'Paddle Court 2',
                'description' => 'Lapangan paddle dengan area bermain luas dan pencahayaan yang baik.',
                'image' => null,
                'price_per_hour' => 175000,
                'is_active' => true,
            ],
            [
                'name' => 'Paddle Court 3',
                'description' => 'Lapangan paddle premium dengan fasilitas dan kenyamanan tambahan.',
                'image' => null,
                'price_per_hour' => 200000,
                'is_active' => true,
            ],
            [
                'name' => 'Paddle Court 4',
                'description' => 'Lapangan paddle dengan fasilitas premium dan area bermain yang nyaman.',
                'image' => null,
                'price_per_hour' => 250000,
                'is_active' => true,
            ],
        ];

        foreach ($courts as $court) {
            Court::updateOrCreate(
                ['name' => $court['name']],
                $court
            );
        }
    }
}
