<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clienti', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['fisica', 'giuridica'])->default('fisica');
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('ragione_sociale')->nullable();
            $table->string('codice_fiscale')->nullable()->unique();
            $table->string('partita_iva')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 10)->nullable();
            $table->string('provincia', 5)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clienti');
    }
};
