<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Court;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Lapangan paddle dengan fasilitas standar dan area bermain yang nyaman.',
            'image' => null,
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        Court::create([
            'name' => 'Paddle Court 2',
            'description' => 'Lapangan paddle dengan area bermain luas dan pencahayaan yang baik.',
            'image' => null,
            'price_per_hour' => 175000,
            'is_active' => true,
        ]);

        Court::create([
            'name' => 'Paddle Court 3',
            'description' => 'Lapangan paddle premium dengan fasilitas dan kenyamanan tambahan.',
            'image' => null,
            'price_per_hour' => 200000,
            'is_active' => true,
        ]);
    }
}
