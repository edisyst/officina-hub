<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veicoli_cortesia', function (Blueprint $table) {
            $table->id();
            $table->string('targa');
            $table->string('marca');
            $table->string('modello');
            $table->integer('anno')->nullable();
            $table->string('colore')->nullable();
            $table->enum('tipo', ['auto', 'moto', 'furgone'])->default('auto');
            $table->integer('km_attuali')->default(0);
            $table->enum('carburante_tipo', ['benzina', 'diesel', 'ibrido', 'elettrico', 'gpl', 'metano']);
            $table->unsignedTinyInteger('livello_carburante_inizio')->default(100);
            $table->text('note')->nullable();
            $table->boolean('attivo')->default(true);
            $table->string('immagine_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veicoli_cortesia');
    }
};
