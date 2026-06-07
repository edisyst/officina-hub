<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documenti', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('numero', 30)->unique();
            $table->unsignedSmallInteger('anno');
            $table->unsignedInteger('progressivo');
            $table->foreignId('commessa_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->date('data_emissione');
            $table->date('data_scadenza')->nullable();
            $table->decimal('imponibile', 12, 2)->default(0);
            $table->decimal('iva_totale', 12, 2)->default(0);
            $table->decimal('totale', 12, 2)->default(0);
            $table->string('stato', 20)->default('bozza');
            $table->string('metodo_pagamento', 20)->nullable();
            $table->text('note')->nullable();
            $table->longText('xml_generato')->nullable();
            $table->string('xml_hash', 64)->nullable();
            $table->string('nome_file_sdi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo', 'anno', 'progressivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documenti');
    }
};
