<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('danni_veicolo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->string('zona', 30);
            $table->string('tipo_danno', 30);
            $table->string('descrizione');
            $table->decimal('quantita', 8, 2)->default(1);
            $table->decimal('prezzo_stimato', 10, 2)->nullable();
            $table->decimal('prezzo_perizia', 10, 2)->nullable();
            $table->boolean('incluso_in_perizia')->default(true);
            $table->text('note')->nullable();
            $table->unsignedInteger('ordinamento')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danni_veicolo');
    }
};
