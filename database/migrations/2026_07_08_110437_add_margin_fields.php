<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            if (! Schema::hasColumn('commessa_righe', 'ore_preventivate')) {
                $table->decimal('ore_preventivate', 6, 2)->nullable()->after('quantita');
            }
        });

        Schema::table('commesse', function (Blueprint $table) {
            if (! Schema::hasColumn('commesse', 'data_ora_consegna_prevista')) {
                $table->dateTime('data_ora_consegna_prevista')->nullable()->after('data_uscita_prevista');
            }
        });

        // Backfill prezzo_acquisto da articolo dove è 0 e c'è un articolo collegato.
        // Approssimazione: usa il prezzo di acquisto attuale dell'articolo.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('
                UPDATE commessa_righe cr
                JOIN articoli a ON a.id = cr.articolo_id
                SET cr.prezzo_acquisto = a.prezzo_acquisto
                WHERE cr.tipo = \'articolo\'
                  AND cr.articolo_id IS NOT NULL
                  AND (cr.prezzo_acquisto IS NULL OR cr.prezzo_acquisto = 0)
                  AND a.prezzo_acquisto > 0
            ');
        }
    }

    public function down(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            if (Schema::hasColumn('commessa_righe', 'ore_preventivate')) {
                $table->dropColumn('ore_preventivate');
            }
        });

        Schema::table('commesse', function (Blueprint $table) {
            if (Schema::hasColumn('commesse', 'data_ora_consegna_prevista')) {
                $table->dropColumn('data_ora_consegna_prevista');
            }
        });
    }
};
