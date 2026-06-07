<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dvi_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dvi_voce_id')->constrained('dvi_voci')->cascadeOnDelete();
            $table->foreignId('dvi_ispezione_id')->constrained('dvi_ispezioni')->cascadeOnDelete();
            $table->enum('tipo', ['foto', 'video']);
            $table->string('percorso');
            $table->string('nome_file');
            $table->string('mime_type');
            $table->integer('dimensione_bytes');
            $table->integer('durata_secondi')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvi_media');
    }
};
