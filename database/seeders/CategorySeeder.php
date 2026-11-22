<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Equipment' => 'Machines, free weights and training gear.',
            'Supplements' => 'Protein, creatine and workout formulas.',
            'Apparel' => 'Shirts, leggings and gloves.',
            'Accessories' => 'Shakers, ropes and towels.',
            'Nutrition' => 'Energy bars and hydration products.',
            'Recovery' => 'Foam rollers and massage tools.',
        ];

        foreach ($categories as $name => $description) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $description, 'is_active' => true]
            );
        }
    }
}
