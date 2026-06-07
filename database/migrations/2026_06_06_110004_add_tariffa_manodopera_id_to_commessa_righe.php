<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            $table->foreignId('tariffa_manodopera_id')
                ->nullable()
                ->after('articolo_id')
                ->constrained('tariffe_manodopera')
                ->nullOnDelete();

            $table->foreignId('pacchetto_servizio_id')
                ->nullable()
                ->after('tariffa_manodopera_id')
                ->constrained('pacchetti_servizio')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            $table->dropForeign(['tariffa_manodopera_id']);
            $table->dropForeign(['pacchetto_servizio_id']);
            $table->dropColumn(['tariffa_manodopera_id', 'pacchetto_servizio_id']);
        });
    }
};
