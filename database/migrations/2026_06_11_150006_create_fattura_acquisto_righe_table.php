<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fattura_acquisto_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fattura_acquisto_id')->constrained('fatture_acquisto')->cascadeOnDelete();
            $table->foreignId('articolo_id')->nullable()->constrained('articoli')->nullOnDelete();
            $table->string('descrizione');
            $table->decimal('quantita', 8, 2)->default(1);
            $table->decimal('prezzo_unitario', 10, 2)->default(0);
            $table->decimal('iva_percentuale', 5, 2)->default(22);
            $table->decimal('imponibile_riga', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fattura_acquisto_righe');
    }
};
