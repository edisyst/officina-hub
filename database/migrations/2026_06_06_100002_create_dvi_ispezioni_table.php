<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dvi_ispezioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('stato', ['bozza', 'inviata_cliente', 'approvata', 'parzialmente_approvata', 'rifiutata'])
                  ->default('bozza');
            $table->string('link_token', 64)->unique()->nullable();
            $table->dateTime('link_scade_at')->nullable();
            $table->dateTime('inviata_at')->nullable();
            $table->dateTime('approvata_at')->nullable();
            $table->text('note_meccanico')->nullable();
            $table->text('note_cliente')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvi_ispezioni');
    }
};
