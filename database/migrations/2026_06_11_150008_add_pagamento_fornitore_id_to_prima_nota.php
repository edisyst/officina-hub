<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prima_nota', function (Blueprint $table) {
            $table->foreignId('pagamento_fornitore_id')
                ->nullable()
                ->after('pagamento_id')
                ->constrained('pagamenti_fornitori')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prima_nota', function (Blueprint $table) {
            $table->dropForeign(['pagamento_fornitore_id']);
            $table->dropColumn('pagamento_fornitore_id');
        });
    }
};
