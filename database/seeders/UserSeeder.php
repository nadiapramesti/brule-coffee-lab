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
            ['email' => 'admin@brule.test'],
            [
                'name'     => 'Admin BRÛLÉ',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'phone'    => '081200000001',
            ]
        );

        User::updateOrCreate(
            ['email' => 'roaster@brule.test'],
            [
                'name'     => 'Head Roaster',
                'password' => Hash::make('password'),
                'role'     => 'roaster',
                'phone'    => '081200000002',
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@brule.test'],
            [
                'name'     => 'Pelanggan Demo',
                'password' => Hash::make('password'),
                'role'     => 'customer',
                'phone'    => '081200000003',
            ]
        );

        foreach ([
            ['raka@brule.test',  'Raka Pratama', '081200000004'],
            ['sinta@brule.test', 'Sinta Dewi',   '081200000005'],
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

        // Alamat contoh supaya alur checkout di demo tinggal pilih.
        $nadia = User::where('email', 'customer@brule.test')->first();
        \App\Models\Address::updateOrCreate(
            ['user_id' => $nadia->id, 'label' => 'Rumah'],
            ['recipient' => 'Pelanggan Demo', 'phone' => '081200000003', 'address' => 'Jl. Dago No. 25, Coblong, Kota Bandung 40135', 'is_default' => true]
        );
    }
}
