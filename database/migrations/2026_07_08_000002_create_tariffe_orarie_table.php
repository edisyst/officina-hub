<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffe_orarie', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->decimal('tariffa_oraria', 8, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_attiva')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffe_orarie');
    }
};
