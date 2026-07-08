<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrici_prezzo', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_attiva')->default(true);
            $table->timestamps();
        });

        Schema::create('matrici_prezzo_scaglioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrice_prezzo_id')->constrained('matrici_prezzo')->cascadeOnDelete();
            $table->decimal('costo_da', 10, 2);
            $table->decimal('costo_a', 10, 2)->nullable();
            $table->decimal('markup_percent', 6, 2);
            $table->enum('arrotondamento', ['none', '0.10', '0.50', '1.00'])->default('none');
            $table->timestamps();

            $table->index(['matrice_prezzo_id', 'costo_da']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrici_prezzo_scaglioni');
        Schema::dropIfExists('matrici_prezzo');
    }
};
