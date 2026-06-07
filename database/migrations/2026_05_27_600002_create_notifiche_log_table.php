<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifiche_log', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['email', 'sms', 'whatsapp'])->default('email');
            $table->string('destinatario');
            $table->string('oggetto')->nullable();
            $table->text('corpo');
            $table->enum('stato', ['in_coda', 'inviata', 'fallita', 'rimbalzata'])->default('in_coda');
            $table->text('errore')->nullable();
            $table->foreignId('scadenza_id')->nullable()->constrained('scadenze')->nullOnDelete();
            $table->foreignId('commessa_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clienti')->nullOnDelete();
            $table->integer('tentativi')->default(0);
            $table->dateTime('inviata_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifiche_log');
    }
};
