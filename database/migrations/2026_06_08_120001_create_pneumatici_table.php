<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pneumatici', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clienti')->cascadeOnDelete();
            $table->foreignId('veicolo_id')->constrained('veicoli')->cascadeOnDelete();
            $table->enum('stagione', ['estivo', 'invernale', 'quattro_stagioni']);
            $table->string('marca');
            $table->string('modello')->nullable();
            $table->string('misura');
            $table->unsignedSmallInteger('larghezza')->nullable();
            $table->unsignedSmallInteger('rapporto')->nullable();
            $table->unsignedSmallInteger('diametro')->nullable();
            $table->string('indice_carico', 10)->nullable();
            $table->string('indice_velocita', 5)->nullable();
            $table->unsignedTinyInteger('numero_pezzi')->default(4);
            $table->boolean('dotati_di_cerchi')->default(false);
            $table->string('tipo_cerchi', 30)->nullable();
            $table->unsignedSmallInteger('anno_produzione')->nullable();
            $table->enum('stato', ['montato', 'in_deposito', 'smaltito', 'ritirato_cliente'])->default('in_deposito');
            $table->text('note')->nullable();
            $table->timestamp('notifica_inviata_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pneumatici');
    }
};
