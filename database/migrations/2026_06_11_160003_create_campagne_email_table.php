<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagne_email', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('oggetto');
            $table->text('corpo');
            $table->string('stato')->default('bozza'); // bozza|pianificata|in_invio|completata|annullata
            $table->string('segmento_target'); // tutti|nuovi|attivi|a_rischio|perso|vip|personalizzato
            $table->json('filtro_json')->nullable();
            $table->dateTime('pianificata_at')->nullable();
            $table->dateTime('inviata_at')->nullable();
            $table->integer('totale_destinatari')->nullable();
            $table->integer('totale_inviati')->default(0);
            $table->integer('totale_errori')->default(0);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagne_email');
    }
};
