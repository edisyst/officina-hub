<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RuoliSeeder extends Seeder
{
    public function run(): void
    {
        // Resetta la cache dei permessi
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ruoli = ['admin', 'accettatore', 'meccanico', 'cassa'];

        foreach ($ruoli as $ruolo) {
            Role::firstOrCreate(['name' => $ruolo, 'guard_name' => 'web']);
        }
    }
}
