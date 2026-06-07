<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commesse', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->foreignId('veicolo_id')->constrained('veicoli');
            $table->enum('tipo', ['meccanica', 'carrozzeria', 'tagliando'])->default('meccanica');
            $table->enum('stato', [
                'bozza',
                'accettata',
                'in_lavorazione',
                'sospesa',
                'completata',
                'consegnata',
                'fatturata',
            ])->default('bozza');
            $table->integer('km_ingresso')->nullable();
            $table->datetime('data_ingresso');
            $table->date('data_uscita_prevista')->nullable();
            $table->datetime('data_consegna')->nullable();
            $table->text('descrizione_cliente');
            $table->text('diagnosi_tecnica')->nullable();
            $table->text('note_interne')->nullable();
            $table->longText('firma_cliente_svg')->nullable();
            $table->longText('firma_consegna_svg')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commesse');
    }
};
