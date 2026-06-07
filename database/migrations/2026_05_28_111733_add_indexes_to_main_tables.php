<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indici ad alta priorità — query eseguite ad ogni caricamento pagina
        Schema::table('commesse', function (Blueprint $table) {
            $table->index(['stato', 'created_at'], 'idx_commesse_stato_data');
        });

        Schema::table('appuntamenti', function (Blueprint $table) {
            $table->index(['data_ora_inizio', 'data_ora_fine'], 'idx_appuntamenti_range');
        });

        Schema::table('movimenti_magazzino', function (Blueprint $table) {
            $table->index(['articolo_id', 'tipo', 'created_at'], 'idx_movimenti_articolo');
        });

        Schema::table('scadenze', function (Blueprint $table) {
            $table->index(['cliente_id', 'data_scadenza'], 'idx_scadenze_cliente');
        });

        Schema::table('documenti', function (Blueprint $table) {
            $table->index(['tipo', 'anno', 'progressivo'], 'idx_documenti_numerazione');
        });
    }

    public function down(): void
    {
        Schema::table('commesse', function (Blueprint $table) {
            $table->dropIndex('idx_commesse_stato_data');
        });

        Schema::table('appuntamenti', function (Blueprint $table) {
            $table->dropIndex('idx_appuntamenti_range');
        });

        Schema::table('movimenti_magazzino', function (Blueprint $table) {
            $table->dropIndex('idx_movimenti_articolo');
        });

        Schema::table('scadenze', function (Blueprint $table) {
            $table->dropIndex('idx_scadenze_cliente');
        });

        Schema::table('documenti', function (Blueprint $table) {
            $table->dropIndex('idx_documenti_numerazione');
        });
    }
};
