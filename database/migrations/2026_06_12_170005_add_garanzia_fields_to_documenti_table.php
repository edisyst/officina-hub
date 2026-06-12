<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documenti', function (Blueprint $table) {
            $table->foreignId('casa_madre_id')->nullable()->after('documento_correlato_id')
                ->constrained('case_madri')->nullOnDelete();
            $table->string('tipo_emissione_garanzia')->nullable()->after('casa_madre_id');
        });
    }

    public function down(): void
    {
        Schema::table('documenti', function (Blueprint $table) {
            $table->dropConstrainedForeignId('casa_madre_id');
            $table->dropColumn('tipo_emissione_garanzia');
        });
    }
};
