<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ems.com'], // Ensures the user is only created once
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin'), // Change this to a secure password
            ]
        );
    }
}
