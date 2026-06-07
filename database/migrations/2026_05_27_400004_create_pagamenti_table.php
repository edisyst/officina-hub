<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documenti')->cascadeOnDelete();
            $table->date('data_pagamento');
            $table->decimal('importo', 12, 2);
            $table->string('metodo', 20);
            $table->string('riferimento')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamenti');
    }
};
