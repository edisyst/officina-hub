<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commessa_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->enum('tipo', ['manodopera', 'articolo', 'nota'])->default('articolo');
            $table->string('descrizione');
            $table->decimal('quantita', 8, 2)->default(1);
            $table->decimal('prezzo_unitario', 10, 2)->default(0);
            $table->decimal('sconto_percentuale', 5, 2)->default(0);
            $table->decimal('iva_percentuale', 5, 2)->default(22);
            $table->integer('ordinamento')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commessa_righe');
    }
};
