<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kromatik.test'],
            [
                'name'     => 'Admin Kromatik',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'phone'    => '081200000001',
            ]
        );

        User::updateOrCreate(
            ['email' => 'roaster@kromatik.test'],
            [
                'name'     => 'Head Roaster',
                'password' => Hash::make('password'),
                'role'     => 'roaster',
                'phone'    => '081200000002',
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@kromatik.test'],
            [
                'name'     => 'Nadia Pramesti',
                'password' => Hash::make('password'),
                'role'     => 'customer',
                'phone'    => '081200000003',
            ]
        );
    }
}
