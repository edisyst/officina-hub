<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_iva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documenti')->cascadeOnDelete();
            $table->string('tipo_registro', 20);
            $table->date('data_registrazione');
            $table->string('numero_documento', 30);
            $table->string('cliente_fornitore');
            $table->string('partita_iva', 20)->nullable();
            $table->string('codice_fiscale', 20)->nullable();
            $table->decimal('imponibile', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('totale', 12, 2);
            $table->decimal('aliquota_iva', 5, 2);
            $table->string('natura_iva', 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_iva');
    }
};
