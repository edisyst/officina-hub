<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimenti_magazzino', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articolo_id')->constrained('articoli')->cascadeOnDelete();
            $table->enum('tipo', ['carico', 'scarico', 'rettifica', 'reso_fornitore', 'reso_cliente']);
            $table->integer('quantita');
            $table->integer('giacenza_precedente');
            $table->integer('giacenza_successiva');
            $table->decimal('prezzo_unitario', 10, 2)->nullable();
            $table->foreignId('commessa_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->string('documento_fornitore')->nullable();
            $table->date('data_documento')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // i movimenti sono immutabili: nessun updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimenti_magazzino');
    }
};
