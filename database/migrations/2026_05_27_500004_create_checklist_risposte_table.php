<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_risposte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_compilata_id')->constrained('checklist_compilate')->cascadeOnDelete();
            $table->foreignId('checklist_voce_id')->constrained('checklist_voci')->cascadeOnDelete();
            $table->boolean('valore_booleano')->nullable();        // si_no
            $table->decimal('valore_numerico', 10, 2)->nullable(); // numerico
            $table->text('valore_testo')->nullable();              // testo_libero
            $table->string('foto_path', 500)->nullable();          // foto_obbligatoria
            $table->timestamps();
            $table->unique(['checklist_compilata_id', 'checklist_voce_id'], 'uq_checklist_risposta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_risposte');
    }
};
