<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@cloudimart.com'],
            [
                'fullName' => 'System Administrator',
                'email' => 'admin@cloudimart.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Create delivery personnel user
        User::updateOrCreate(
            ['email' => 'delivery@cloudimart.com'],
            [
                'fullName' => 'Delivery Staff',
                'email' => 'delivery@cloudimart.com',
                'password' => Hash::make('delivery123'),
                'role' => 'delivery_personnel',
            ]
        );

        $this->command->info('Admin and delivery personnel accounts created successfully!');
        $this->command->info('Admin: admin@cloudimart.com / admin123');
        $this->command->info('Delivery: delivery@cloudimart.com / delivery123');
    }
}
