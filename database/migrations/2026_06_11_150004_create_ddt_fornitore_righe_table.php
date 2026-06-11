<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ddt_fornitore_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ddt_fornitore_id')->constrained('ddt_fornitori')->cascadeOnDelete();
            $table->foreignId('ordine_riga_id')->nullable()->constrained('ordine_fornitore_righe')->nullOnDelete();
            $table->foreignId('articolo_id')->nullable()->constrained('articoli')->nullOnDelete();
            $table->string('descrizione');
            $table->unsignedInteger('quantita_ricevuta');
            $table->decimal('prezzo_unitario', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ddt_fornitore_righe');
    }
};
