<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{

    public function run(): void
    {
        $categories = [
            ['name' => 'Stationery', 'slug' => 'stationery'],
            ['name' => 'Dairy', 'slug' => 'dairy'],
            ['name' => 'Clothes', 'slug' => 'clothes']
        ];

        foreach ($categories as $category) {
            \App\Models\Category::firstOrCreate($category);
        }
    }
}
