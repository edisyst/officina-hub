<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foto_danni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('danno_veicolo_id')->nullable()
                ->constrained('danni_veicolo')->nullOnDelete();
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->string('percorso');
            $table->string('nome_file');
            $table->string('mime_type', 50);
            $table->unsignedInteger('dimensione_bytes');
            $table->string('fase', 20);
            $table->string('descrizione')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foto_danni');
    }
};
