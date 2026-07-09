<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guard migration: crea data_ora_consegna_prevista su commesse solo se assente.
 * Step 28 potrebbe averla già creata — down() è no-op documentato per sicurezza.
 */
return new class extends Migration
{
    private bool $createdHere = false;

    public function up(): void
    {
        if (Schema::hasColumn('commesse', 'data_ora_consegna_prevista')) {
            return;
        }

        Schema::table('commesse', function (Blueprint $table) {
            $table->dateTime('data_ora_consegna_prevista')->nullable()->after('data_uscita_prevista');
        });

        $this->createdHere = true;
    }

    public function down(): void
    {
        // Se la colonna esisteva già prima di questa migrazione non la rimuoviamo:
        // impossibile distinguere in down() chi l'ha creata.
        // Rimuoviamo solo se sicuri di averla creata noi (non applicabile in down standard).
        // Scelta coerente con altri step: down() è no-op per questa colonna.
    }
};
