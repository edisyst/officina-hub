<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMySQL = in_array(config('database.default'), ['mysql', 'mariadb']);

        if (!Schema::hasIndex('clienti', 'clienti_telefono_index')) {
            Schema::table('clienti', fn (Blueprint $t) => $t->index('telefono', 'clienti_telefono_index'));
        }
        if (!Schema::hasIndex('veicoli', 'veicoli_targa_index')) {
            Schema::table('veicoli', fn (Blueprint $t) => $t->index('targa', 'veicoli_targa_index'));
        }
        if (!Schema::hasIndex('veicoli', 'veicoli_vin_index')) {
            Schema::table('veicoli', fn (Blueprint $t) => $t->index('vin', 'veicoli_vin_index'));
        }
        if (!Schema::hasIndex('articoli', 'articoli_codice_index')) {
            Schema::table('articoli', fn (Blueprint $t) => $t->index('codice', 'articoli_codice_index'));
        }

        if ($isMySQL) {
            if (!$this->hasFulltext('clienti', 'clienti_search_fulltext')) {
                DB::statement('ALTER TABLE clienti ADD FULLTEXT INDEX clienti_search_fulltext (nome, cognome, ragione_sociale, email)');
            }
            if (!$this->hasFulltext('veicoli', 'veicoli_search_fulltext')) {
                DB::statement('ALTER TABLE veicoli ADD FULLTEXT INDEX veicoli_search_fulltext (marca, modello)');
            }
            if (!$this->hasFulltext('articoli', 'articoli_search_fulltext')) {
                DB::statement('ALTER TABLE articoli ADD FULLTEXT INDEX articoli_search_fulltext (descrizione)');
            }
            if (!$this->hasFulltext('commesse', 'commesse_search_fulltext')) {
                DB::statement('ALTER TABLE commesse ADD FULLTEXT INDEX commesse_search_fulltext (descrizione_cliente, diagnosi_tecnica, note_interne)');
            }
        }
    }

    public function down(): void
    {
        $isMySQL = in_array(config('database.default'), ['mysql', 'mariadb']);

        if (Schema::hasIndex('clienti', 'clienti_telefono_index')) {
            Schema::table('clienti', fn (Blueprint $t) => $t->dropIndex('clienti_telefono_index'));
        }
        if (Schema::hasIndex('veicoli', 'veicoli_targa_index')) {
            Schema::table('veicoli', fn (Blueprint $t) => $t->dropIndex('veicoli_targa_index'));
        }
        if (Schema::hasIndex('veicoli', 'veicoli_vin_index')) {
            Schema::table('veicoli', fn (Blueprint $t) => $t->dropIndex('veicoli_vin_index'));
        }
        if (Schema::hasIndex('articoli', 'articoli_codice_index')) {
            Schema::table('articoli', fn (Blueprint $t) => $t->dropIndex('articoli_codice_index'));
        }

        if ($isMySQL) {
            if ($this->hasFulltext('clienti', 'clienti_search_fulltext')) {
                DB::statement('ALTER TABLE clienti DROP INDEX clienti_search_fulltext');
            }
            if ($this->hasFulltext('veicoli', 'veicoli_search_fulltext')) {
                DB::statement('ALTER TABLE veicoli DROP INDEX veicoli_search_fulltext');
            }
            if ($this->hasFulltext('articoli', 'articoli_search_fulltext')) {
                DB::statement('ALTER TABLE articoli DROP INDEX articoli_search_fulltext');
            }
            if ($this->hasFulltext('commesse', 'commesse_search_fulltext')) {
                DB::statement('ALTER TABLE commesse DROP INDEX commesse_search_fulltext');
            }
        }
    }

    private function hasFulltext(string $table, string $indexName): bool
    {
        $db = config('database.connections.' . config('database.default') . '.database');
        $count = DB::selectOne(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE table_schema = ? AND table_name = ? AND index_name = ? AND index_type = 'FULLTEXT'",
            [$db, $table, $indexName]
        );
        return $count && $count->cnt > 0;
    }
};
