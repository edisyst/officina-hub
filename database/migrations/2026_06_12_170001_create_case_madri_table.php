<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_madri', function (Blueprint $table) {
            $table->id();
            $table->string('ragione_sociale');
            $table->string('partita_iva')->nullable();
            $table->string('codice_destinatario_sdi', 7)->nullable();
            $table->string('pec')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('codice_convenzionamento')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_madri');
    }
};
