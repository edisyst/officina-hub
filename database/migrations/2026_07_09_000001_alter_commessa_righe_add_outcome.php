<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('commessa_righe', 'outcome')) {
            return;
        }

        Schema::table('commessa_righe', function (Blueprint $table) {
            $table->enum('outcome', ['completed', 'declined'])->default('completed')->after('ordinamento');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('commessa_righe', 'outcome')) {
            Schema::table('commessa_righe', function (Blueprint $table) {
                $table->dropColumn('outcome');
            });
        }
    }
};
