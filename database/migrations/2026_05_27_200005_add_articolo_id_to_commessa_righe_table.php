<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            $table->foreignId('articolo_id')->nullable()->after('tipo')->constrained('articoli')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Articolo::class);
            $table->dropColumn('articolo_id');
        });
    }
};
