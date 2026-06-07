<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commesse', function (Blueprint $table) {
            // sinistro_id senza FK per evitare riferimento circolare con sinistri.commessa_id
            $table->unsignedBigInteger('sinistro_id')->nullable()->after('user_id');
            $table->string('stato_carrozzeria', 40)->nullable()->after('sinistro_id');
            $table->unsignedInteger('km_uscita')->nullable()->after('stato_carrozzeria');
            $table->json('note_accettazione_json')->nullable()->after('km_uscita');
        });
    }

    public function down(): void
    {
        Schema::table('commesse', function (Blueprint $table) {
            $table->dropColumn(['sinistro_id', 'stato_carrozzeria', 'km_uscita', 'note_accettazione_json']);
        });
    }
};
