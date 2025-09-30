<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::firstOrCreate([
            'name' => 'Admin User',
            'email' => 'admin@ecommerce.com',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate([
            'name' => 'Test User',
            'email' => 'test@ecommerce.com',
            'password' => Hash::make('password123'),
        ]);

        $products = [
            [
                'name' => 'Laptop ASUS ROG Strix G15',
                'description' => 'Gaming laptop dengan processor Intel i7-11800H dan GPU RTX 3050',
                'price' => 15000000,
                'stock' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Smartphone Samsung Galaxy S23',
                'description' => 'Flagship smartphone dengan camera 108MP dan processor Snapdragon 8 Gen 2',
                'price' => 12000000,
                'stock' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Headphone Sony WH-1000XM4',
                'description' => 'Wireless noise cancelling headphone dengan battery life 30 jam',
                'price' => 3500000,
                'stock' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Apple Watch Series 8',
                'description' => 'Smartwatch dengan ECG dan blood oxygen monitoring',
                'price' => 6500000,
                'stock' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'iPad Air 5th Generation',
                'description' => 'Tablet dengan chip M1 dan layar Liquid Retina 10.9 inch',
                'price' => 8500000,
                'stock' => 12,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate($product);
        }

        $this->command->info('Sample data created successfully!');
    }
}
