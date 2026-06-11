<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_iva', function (Blueprint $table) {
            $table->foreignId('fattura_acquisto_id')
                ->nullable()
                ->after('documento_id')
                ->constrained('fatture_acquisto')
                ->nullOnDelete();
            $table->foreignId('documento_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registro_iva', function (Blueprint $table) {
            $table->dropForeign(['fattura_acquisto_id']);
            $table->dropColumn('fattura_acquisto_id');
        });
    }
};
