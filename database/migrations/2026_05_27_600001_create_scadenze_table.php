<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scadenze', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veicolo_id')->constrained('veicoli')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clienti')->cascadeOnDelete();
            $table->enum('tipo', ['revisione', 'tagliando', 'assicurazione', 'bollo', 'altro']);
            $table->string('descrizione')->nullable();
            $table->date('data_scadenza');
            $table->integer('km_scadenza')->nullable();
            $table->integer('km_attuali_al_momento')->nullable();
            $table->integer('notifica_giorni_prima')->default(30);
            $table->dateTime('notifica_inviata_at')->nullable();
            $table->boolean('notifica_disabilitata')->default(false);
            $table->foreignId('commessa_origine_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scadenze');
    }
};
