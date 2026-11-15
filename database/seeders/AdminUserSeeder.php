<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        //Akun admin test
        User::updateOrCreate(
            [
                'email' => 'admin@paddlecourt.com',
            ],
            [
                'name'=> 'Admin Paddle Court',
                'password' => 'AdminPaddle2026!',
                'role' => 'admin',
                'email_verified_at' => now()
            ]
        );
    }
}
