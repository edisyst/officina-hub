<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('commessa_righe', 'tariffa_oraria_id')) {
            Schema::table('commessa_righe', function (Blueprint $table) {
                $table->foreignId('tariffa_oraria_id')
                    ->nullable()
                    ->after('tariffa_manodopera_id')
                    ->constrained('tariffe_orarie')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('commessa_righe', 'tariffa_oraria_id')) {
            Schema::table('commessa_righe', function (Blueprint $table) {
                $table->dropForeignIdFor(\App\Models\TariffaOraria::class);
                $table->dropColumn('tariffa_oraria_id');
            });
        }
    }
};
