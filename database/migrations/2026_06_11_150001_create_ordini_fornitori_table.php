<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordini_fornitori', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->unsignedSmallInteger('anno');
            $table->unsignedInteger('progressivo');
            $table->foreignId('fornitore_id')->constrained('fornitori');
            $table->enum('stato', [
                'bozza', 'inviato', 'confermato',
                'parzialmente_ricevuto', 'ricevuto', 'annullato',
            ])->default('bozza');
            $table->date('data_ordine');
            $table->date('data_consegna_prevista')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordini_fornitori');
    }
};
