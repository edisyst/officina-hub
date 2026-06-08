<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // Aggiunge le impostazioni lookup targa tramite SettingsSeeder al prossimo migrate:fresh.
    // In produzione eseguire il seeder manualmente dopo la migrazione.
    public function up(): void {}

    public function down(): void {}
};
