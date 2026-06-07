<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commessa_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->string('stato_da')->nullable();
            $table->string('stato_a');
            $table->foreignId('user_id')->constrained('users');
            $table->text('nota')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commessa_log');
    }
};
