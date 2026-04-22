<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('123456'),
                'language' => 'en',
                'currency' => 'CAD',
                'is_active' => true,
                'plan' => null,
                'total_income' => 0,
                'total_expense' => 0,
                'total_subscription' => 0,
            ]
        );
    }
}
