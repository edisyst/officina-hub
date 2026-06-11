<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggiunge colonna sottotipo per distinguere compleanno/campagna (non altera l'ENUM tipo)
        Schema::table('notifiche_log', function (Blueprint $table) {
            $table->string('sottotipo')->nullable()->after('tipo');
            $table->foreignId('campagna_email_id')->nullable()->after('sottotipo')
                ->constrained('campagne_email')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifiche_log', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campagna_email_id');
            $table->dropColumn('sottotipo');
        });
    }
};
