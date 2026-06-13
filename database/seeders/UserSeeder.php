<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Demo User',
            'email' => 'demo@taskflow.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Jane Member',
            'email' => 'jane@taskflow.com',
            'password' => Hash::make('password'),
        ]);
    }
}
