<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacchetti_servizio', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->enum('tipo_commessa', ['meccanica', 'carrozzeria', 'tagliando', 'entrambi'])->default('entrambi');
            $table->enum('tipo_veicolo', ['auto', 'moto', 'entrambi'])->default('entrambi');
            $table->enum('alimentazione', ['benzina', 'diesel', 'ibrido', 'elettrico', 'gpl', 'metano', 'tutte'])->default('tutte');
            $table->decimal('prezzo_totale_suggerito', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->boolean('attivo')->default(true);
            $table->integer('utilizzi')->default(0);
            $table->integer('ordinamento')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacchetti_servizio');
    }
};
