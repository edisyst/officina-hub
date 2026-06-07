<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documenti', function (Blueprint $table) {
            $table->unsignedBigInteger('sinistro_id')->nullable()->after('commessa_id');
            $table->string('tipo_emissione', 20)->nullable()->after('sinistro_id');
            $table->foreignId('documento_correlato_id')->nullable()
                ->constrained('documenti')->nullOnDelete()->after('tipo_emissione');
        });
    }

    public function down(): void
    {
        Schema::table('documenti', function (Blueprint $table) {
            $table->dropForeign(['documento_correlato_id']);
            $table->dropColumn(['sinistro_id', 'tipo_emissione', 'documento_correlato_id']);
        });
    }
};
