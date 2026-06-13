<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@admin.admin',                    'name' => 'Amministratore',  'role' => 'admin'],
            ['email' => 'accettatore@accettatore.accettatore',  'name' => 'Accettatore',     'role' => 'accettatore'],
            ['email' => 'meccanico@meccanico.meccanico',        'name' => 'Meccanico',       'role' => 'meccanico'],
            ['email' => 'cassa@cassa.cassa',                    'name' => 'Cassa',           'role' => 'cassa'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('admin'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole($data['role']);
        }
    }
}
