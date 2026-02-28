<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class CategoryProductSeeder extends Seeder
{
    public function run(): void
    {

        // Stationery products
        $stationeryProducts = [
            [
                'name' => 'Hardcover Book (A4)',
                'description' => 'Hardcover notebook, perfect for notes and journaling',
                'price' => 4500,
                'stock_quantity' => 50,
                'image_url' => "hard.jpeg"
            ],
            [
                'name' => 'Pens',
                'description' => 'Big Pens',
                'price' => 1200,
                'stock_quantity' => 100,
                'image_url' => "pen2.png"
            ],
            [
                'name' => 'Plain Paper Rim (500 sheets)',
                'description' => 'A4 plain paper, 500 sheets',
                'price' => 3500,
                'stock_quantity' => 30,
                'image_url' => "paper.png"
            ],
            [
                'name' => 'Scientific Calculator',
                'description' => 'Advanced scientific calculator for students',
                'price' => 8500,
                'stock_quantity' => 25,
                'image_url' => "C2.png"
            ],
        ];

        foreach ($stationeryProducts as $product) {
            Product::firstOrCreate([
                'name' => $product['name']
            ], array_merge($product, [
                'category_id' => 1,
                'featured' => false,
            ]));
        }

       
        // Dairy products
        $dairyProducts = [
            [
                'name' => 'Lilongwe Dairy Fresh Milk (1L)',
                'description' => 'Fresh pasteurized milk',
                'price' => 1500,
                'stock_quantity' => 60,
                'image_url' => "p2.png"
            ],
            [
                'name' => 'Lilongwe Dairy Cheese (500g)',
                'description' => 'Cheddar cheese, 500g',
                'price' => 3800,
                'stock_quantity' => 25,
                'image_url' => "cheese.png"
                
            ],
            [
                'name' => 'Lilongwe Dairy Yogurt (1L)',
                'description' => 'Plain yogurt, 1 liter',
                'price' => 2200,
                'stock_quantity' => 40,
                'image_url' => "y1.png"
            ],
            [
                'name' => 'Lilongwe Dairy Milk(500g)',
                'description' => 'Salted butter, 500g',
                'price' => 3200,
                'stock_quantity' => 30,
                'image_url' => "p2.png"
            ],
        ];

        foreach ($dairyProducts as $product) {
            Product::firstOrCreate([
                'name' => $product['name']
            ], array_merge($product, [
                'category_id' => 2,
                'featured' => false,
            ]));
        }
    }
}