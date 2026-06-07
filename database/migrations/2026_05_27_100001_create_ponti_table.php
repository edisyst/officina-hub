<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponti', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['meccanica', 'carrozzeria', 'diagnosi'])->default('meccanica');
            $table->string('descrizione')->nullable();
            $table->boolean('attivo')->default(true);
            $table->integer('ordinamento')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ponti');
    }
};
