<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documenti')->cascadeOnDelete();
            $table->foreignId('commessa_riga_id')->nullable()->constrained('commessa_righe')->nullOnDelete();
            $table->string('descrizione');
            $table->string('unita_misura', 10)->default('pz');
            $table->decimal('quantita', 8, 2)->default(1);
            $table->decimal('prezzo_unitario', 10, 2)->default(0);
            $table->decimal('sconto_percentuale', 5, 2)->default(0);
            $table->decimal('iva_percentuale', 5, 2)->default(22);
            $table->string('natura_iva', 4)->nullable();
            $table->decimal('imponibile_riga', 12, 2)->default(0);
            $table->decimal('iva_riga', 12, 2)->default(0);
            $table->unsignedInteger('ordinamento')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_righe');
    }
};
