<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            $table->boolean('in_garanzia')->default(false)->after('ordinamento');
            $table->foreignId('garanzia_id')->nullable()->after('in_garanzia')
                ->constrained('garanzie')->nullOnDelete();
            $table->foreignId('casa_madre_id')->nullable()->after('garanzia_id')
                ->constrained('case_madri')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commessa_righe', function (Blueprint $table) {
            $table->dropConstrainedForeignId('garanzia_id');
            $table->dropConstrainedForeignId('casa_madre_id');
            $table->dropColumn('in_garanzia');
        });
    }
};
