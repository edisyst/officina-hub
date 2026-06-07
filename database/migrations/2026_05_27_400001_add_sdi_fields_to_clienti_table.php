<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->string('codice_destinatario_sdi', 7)->nullable()->after('partita_iva');
            $table->string('pec_sdi')->nullable()->after('codice_destinatario_sdi');
        });
    }

    public function down(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->dropColumn(['codice_destinatario_sdi', 'pec_sdi']);
        });
    }
};
