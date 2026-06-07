<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffe_manodopera', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('descrizione');
            $table->string('categoria');
            $table->integer('minuti_standard');
            $table->decimal('prezzo_listino', 10, 2);
            $table->decimal('iva_percentuale', 5, 2)->default(22);
            $table->enum('tipo_veicolo', ['auto', 'moto', 'entrambi'])->default('entrambi');
            $table->boolean('attivo')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffe_manodopera');
    }
};
