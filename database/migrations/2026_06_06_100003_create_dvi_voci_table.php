<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dvi_voci', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dvi_ispezione_id')->constrained('dvi_ispezioni')->cascadeOnDelete();
            $table->string('categoria');
            $table->string('descrizione');
            $table->enum('urgenza', ['ok', 'attenzione', 'urgente'])->default('ok');
            $table->decimal('prezzo_stimato', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->integer('ordinamento')->default(0);
            $table->enum('stato_approvazione', ['in_attesa', 'approvato', 'rimandato'])->nullable();
            $table->dateTime('approvato_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvi_voci');
    }
};
