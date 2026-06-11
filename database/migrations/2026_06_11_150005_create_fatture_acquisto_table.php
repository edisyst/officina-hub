<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fatture_acquisto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornitore_id')->constrained('fornitori');
            $table->foreignId('ddt_fornitore_id')->nullable()->constrained('ddt_fornitori')->nullOnDelete();
            $table->string('numero_fattura_fornitore');
            $table->date('data_fattura');
            $table->date('data_ricezione');
            $table->date('data_scadenza')->nullable();
            $table->decimal('imponibile', 12, 2)->default(0);
            $table->decimal('iva_totale', 12, 2)->default(0);
            $table->decimal('totale', 12, 2)->default(0);
            $table->enum('stato', ['ricevuta', 'registrata', 'pagata', 'contestata'])->default('ricevuta');
            $table->enum('metodo_pagamento', ['contanti', 'bonifico', 'carta', 'assegno', 'rid', 'riba'])->nullable();
            $table->string('xml_sdi_path')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fatture_acquisto');
    }
};
