<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->date('data_nascita')->nullable()->after('note');
            $table->string('professione')->nullable()->after('data_nascita');
            $table->string('come_ci_ha_conosciuto')->nullable()->after('professione');
            $table->boolean('consenso_marketing')->default(false)->after('come_ci_ha_conosciuto');
            $table->dateTime('consenso_marketing_at')->nullable()->after('consenso_marketing');
            $table->decimal('valore_lifetime', 12, 2)->default(0)->after('consenso_marketing_at');
            $table->integer('numero_visite')->default(0)->after('valore_lifetime');
            $table->dateTime('ultima_visita_at')->nullable()->after('numero_visite');
            $table->string('segmento_crm')->nullable()->after('ultima_visita_at');
        });
    }

    public function down(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->dropColumn([
                'data_nascita', 'professione', 'come_ci_ha_conosciuto',
                'consenso_marketing', 'consenso_marketing_at',
                'valore_lifetime', 'numero_visite', 'ultima_visita_at', 'segmento_crm',
            ]);
        });
    }
};
