<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ddt_fornitori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordine_fornitore_id')->nullable()->constrained('ordini_fornitori')->nullOnDelete();
            $table->foreignId('fornitore_id')->constrained('fornitori');
            $table->string('numero_ddt');
            $table->date('data_ddt');
            $table->date('data_ricezione');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ddt_fornitori');
    }
};
