<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compagnie_assicurative', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('codice_abi', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('pec')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('referente')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compagnie_assicurative');
    }
};
