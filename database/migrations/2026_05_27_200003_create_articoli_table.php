<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articoli', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('codice_fornitore')->nullable();
            $table->string('descrizione');
            $table->text('descrizione_estesa')->nullable();
            $table->foreignId('categoria_articolo_id')->nullable()->constrained('categorie_articoli')->nullOnDelete();
            $table->foreignId('fornitore_id')->nullable()->constrained('fornitori')->nullOnDelete();
            $table->enum('unita_misura', ['pz', 'lt', 'kg', 'ml', 'gr', 'mt'])->default('pz');
            $table->decimal('prezzo_acquisto', 10, 2)->default(0);
            $table->decimal('prezzo_vendita', 10, 2)->default(0);
            $table->decimal('iva_percentuale', 5, 2)->default(22);
            $table->integer('scorta_minima')->default(0);
            $table->integer('scorta_massima')->nullable();
            $table->integer('giacenza_attuale')->default(0);
            $table->string('ubicazione')->nullable();
            $table->boolean('attivo')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articoli');
    }
};
