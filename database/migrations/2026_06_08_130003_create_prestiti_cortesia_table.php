<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestiti_cortesia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veicolo_cortesia_id')->constrained('veicoli_cortesia');
            $table->foreignId('commessa_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->foreignId('user_id_consegna')->constrained('users');
            $table->foreignId('user_id_rientro')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('data_consegna');
            $table->date('data_rientro_prevista');
            $table->dateTime('data_rientro_effettiva')->nullable();
            $table->integer('km_consegna');
            $table->integer('km_rientro')->nullable();
            $table->unsignedTinyInteger('carburante_consegna')->default(100);
            $table->unsignedTinyInteger('carburante_rientro')->nullable();
            $table->decimal('cauzione_importo', 10, 2)->default(0);
            $table->boolean('cauzione_pagata')->default(false);
            $table->longText('firma_consegna_svg')->nullable();
            $table->longText('firma_rientro_svg')->nullable();
            $table->text('note_consegna')->nullable();
            $table->text('note_rientro')->nullable();
            $table->enum('stato', ['prenotato', 'in_corso', 'rientrato', 'annullato'])->default('prenotato');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestiti_cortesia');
    }
};
