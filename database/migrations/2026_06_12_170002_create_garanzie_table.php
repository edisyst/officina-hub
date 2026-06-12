<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garanzie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veicolo_id')->constrained('veicoli')->cascadeOnDelete();
            $table->foreignId('casa_madre_id')->nullable()->constrained('case_madri')->nullOnDelete();
            $table->string('tipo');
            $table->string('descrizione');
            $table->date('data_inizio');
            $table->date('data_fine')->nullable();
            $table->integer('km_inizio')->nullable();
            $table->integer('km_fine')->nullable();
            $table->string('numero_pratica')->nullable();
            $table->text('note')->nullable();
            $table->boolean('attiva')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garanzie');
    }
};
