<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->string('patente_numero')->nullable()->after('note');
            $table->date('patente_scadenza')->nullable()->after('patente_numero');
        });
    }

    public function down(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->dropColumn(['patente_numero', 'patente_scadenza']);
        });
    }
};
