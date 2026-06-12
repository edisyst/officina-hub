<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commesse', function (Blueprint $table) {
            $table->boolean('ha_righe_garanzia')->default(false)->after('dvi_approvazione_importo');
        });
    }

    public function down(): void
    {
        Schema::table('commesse', function (Blueprint $table) {
            $table->dropColumn('ha_righe_garanzia');
        });
    }
};
