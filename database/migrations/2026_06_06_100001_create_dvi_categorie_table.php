<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dvi_categorie', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('icona_css')->nullable();
            $table->enum('colore_default', ['ok', 'attenzione', 'urgente'])->nullable();
            $table->integer('ordinamento')->default(0);
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvi_categorie');
    }
};
