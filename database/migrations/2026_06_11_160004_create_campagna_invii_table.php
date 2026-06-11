<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagna_invii', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campagna_email_id')->constrained('campagne_email')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clienti')->cascadeOnDelete();
            $table->string('stato')->default('in_coda'); // in_coda|inviata|fallita
            $table->dateTime('inviata_at')->nullable();
            $table->text('errore')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagna_invii');
    }
};
