<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depositi_pneumatici', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pneumatico_id')->constrained('pneumatici')->cascadeOnDelete();
            $table->enum('azione', ['deposito', 'ritiro', 'smaltimento', 'cambio_stagionale']);
            $table->foreignId('commessa_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->date('data_azione');
            $table->string('ubicazione')->nullable();
            $table->unsignedTinyInteger('usura_percentuale')->nullable();
            $table->string('usura_note')->nullable();
            $table->unsignedInteger('km_al_momento')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depositi_pneumatici');
    }
};
