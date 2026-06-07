<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veicoli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clienti')->nullOnDelete();
            $table->enum('tipo', ['auto', 'moto', 'furgone'])->default('auto');
            $table->string('targa', 20)->nullable()->unique();
            $table->string('vin', 17)->nullable()->unique();
            $table->string('marca');
            $table->string('modello');
            $table->string('versione')->nullable();
            $table->enum('alimentazione', ['benzina', 'diesel', 'ibrido', 'elettrico', 'gpl', 'metano'])->default('benzina');
            $table->integer('cilindrata')->nullable();
            $table->year('anno_immatricolazione')->nullable();
            $table->string('colore')->nullable();
            $table->integer('km_attuali')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veicoli');
    }
};
