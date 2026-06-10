<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prima_nota', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('causale');
            $table->enum('tipo', ['entrata', 'uscita']);
            $table->decimal('importo', 12, 2);
            $table->enum('metodo', ['contanti', 'bonifico', 'carta', 'assegno', 'rid', 'altro'])->default('contanti');
            $table->enum('conto', ['cassa', 'banca', 'pos'])->default('cassa');
            $table->foreignId('documento_id')->nullable()->constrained('documenti')->nullOnDelete();
            $table->foreignId('pagamento_id')->nullable()->constrained('pagamenti')->nullOnDelete();
            $table->foreignId('fornitore_id')->nullable()->constrained('fornitori')->nullOnDelete();
            $table->text('note')->nullable();
            $table->boolean('automatico')->default(false);
            $table->foreignId('user_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prima_nota');
    }
};
