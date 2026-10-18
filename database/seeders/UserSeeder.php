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
                'name'     => 'Admin BRÛLÉ',
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

        foreach ([
            ['raka@kromatik.test',  'Raka Pratama', '081200000004'],
            ['sinta@kromatik.test', 'Sinta Dewi',   '081200000005'],
        ] as [$email, $name, $phone]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $name,
                    'password' => Hash::make('password'),
                    'role'     => 'customer',
                    'phone'    => $phone,
                ]
            );
        }
    }
}
