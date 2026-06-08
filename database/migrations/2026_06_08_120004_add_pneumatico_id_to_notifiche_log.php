<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifiche_log', function (Blueprint $table) {
            $table->foreignId('pneumatico_id')->nullable()->after('cliente_id')
                ->constrained('pneumatici')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifiche_log', function (Blueprint $table) {
            $table->dropForeign(['pneumatico_id']);
            $table->dropColumn('pneumatico_id');
        });
    }
};
