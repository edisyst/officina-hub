<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie_articoli', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('descrizione')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categorie_articoli')->nullOnDelete();
            $table->integer('ordinamento')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_articoli');
    }
};
