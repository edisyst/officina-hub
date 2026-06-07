<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_veicolo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clienti')->cascadeOnDelete();
            $table->foreignId('veicolo_id')->constrained('veicoli')->cascadeOnDelete();
            $table->boolean('proprietario_attuale')->default(true);
            $table->date('data_inizio')->nullable();
            $table->date('data_fine')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'veicolo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_veicolo');
    }
};
