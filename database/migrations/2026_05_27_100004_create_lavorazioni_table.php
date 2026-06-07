<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lavorazioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->foreignId('commessa_riga_id')->nullable()->constrained('commessa_righe')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ponte_id')->nullable()->constrained('ponti')->nullOnDelete();
            $table->string('descrizione');
            $table->integer('minuti_preventivati')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('stopped_at')->nullable();
            $table->integer('minuti_effettivi')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lavorazioni');
    }
};
