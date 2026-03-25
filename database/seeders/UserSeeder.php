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
        $data = [
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('test123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Paseador',
                'email' => 'paseador@gmail.com',
                'password' => Hash::make('test123'),
                'role' => 'paseador',
            ],
            [
                'name' => 'Paseador2',
                'email' => 'paseador2@gmail.com',
                'password' => Hash::make('test123'),
                'role' => 'paseador',
            ],
        ];

        foreach ($data as $user) {
            User::create($user);
        }
    }
}
