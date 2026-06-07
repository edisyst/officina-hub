<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinistri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->foreignId('compagnia_assicurativa_id')->nullable()
                ->constrained('compagnie_assicurative')->nullOnDelete();
            $table->string('numero_sinistro')->nullable();
            $table->string('numero_polizza_cliente')->nullable();
            $table->string('numero_polizza_controparte')->nullable();
            $table->string('tipo_sinistro', 30);
            $table->date('data_sinistro')->nullable();
            $table->string('luogo_sinistro')->nullable();
            $table->text('descrizione_dinamica')->nullable();
            $table->string('liquidatore_nome')->nullable();
            $table->string('liquidatore_email')->nullable();
            $table->string('liquidatore_telefono', 30)->nullable();
            $table->string('stato', 30)->default('aperto');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinistri');
    }
};
