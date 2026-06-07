<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appuntamenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commessa_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clienti')->nullOnDelete();
            $table->foreignId('veicolo_id')->nullable()->constrained('veicoli')->nullOnDelete();
            $table->foreignId('ponte_id')->nullable()->constrained('ponti')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titolo');
            $table->text('note')->nullable();
            $table->dateTime('data_ora_inizio');
            $table->dateTime('data_ora_fine');
            $table->boolean('tutto_il_giorno')->default(false);
            $table->enum('stato', ['pianificato', 'confermato', 'in_corso', 'completato', 'annullato'])->default('pianificato');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appuntamenti');
    }
};
