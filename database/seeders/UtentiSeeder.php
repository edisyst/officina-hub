<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UtentiSeeder extends Seeder
{
    public function run(): void
    {
        $utenti = [
            ['name' => 'Marco Bianchi',    'email' => 'accettatore@officinahub.local', 'role' => 'accettatore'],
            ['name' => 'Luigi Ferretti',   'email' => 'meccanico@officinahub.local',   'role' => 'meccanico'],
            ['name' => 'Sara Conti',       'email' => 'cassa@officinahub.local',        'role' => 'cassa'],
        ];

        foreach ($utenti as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'               => $data['name'],
                    'password'           => Hash::make('password'),
                    'email_verified_at'  => now(),
                ]
            );
            $user->assignRole($data['role']);
        }
    }
}
