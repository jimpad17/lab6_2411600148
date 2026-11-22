<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Ironbulk' => 'Strength training equipment and free weights.',
            'NutriCore' => 'Supplements and nutrition products.',
            'ActiveWear' => 'Gym apparel and training clothing.',
            'HydroGear' => 'Bottles, towels and training accessories.',
            'FlexFit' => 'Resistance, mobility and recovery gear.',
            'Stride Mechanics' => 'Cardio machines and treadmills.',
            'ProGrip' => 'Lifting support and grip accessories.',
        ];

        foreach ($brands as $name => $description) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $description, 'is_active' => true]
            );
        }
    }
}
