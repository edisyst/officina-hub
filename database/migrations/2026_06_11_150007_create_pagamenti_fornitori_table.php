<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamenti_fornitori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fattura_acquisto_id')->constrained('fatture_acquisto');
            $table->date('data_pagamento');
            $table->decimal('importo', 12, 2);
            $table->enum('metodo', ['contanti', 'bonifico', 'carta', 'assegno', 'rid', 'riba']);
            $table->string('riferimento')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamenti_fornitori');
    }
};
