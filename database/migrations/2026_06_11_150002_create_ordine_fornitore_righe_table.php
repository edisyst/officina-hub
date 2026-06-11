<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordine_fornitore_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordine_fornitore_id')->constrained('ordini_fornitori')->cascadeOnDelete();
            $table->foreignId('articolo_id')->nullable()->constrained('articoli')->nullOnDelete();
            $table->string('descrizione');
            $table->string('codice_fornitore')->nullable();
            $table->unsignedInteger('quantita_ordinata');
            $table->unsignedInteger('quantita_ricevuta')->default(0);
            $table->decimal('prezzo_unitario_atteso', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordine_fornitore_righe');
    }
};
