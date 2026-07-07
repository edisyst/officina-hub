<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commesse', function (Blueprint $table) {
            $table->unsignedInteger('board_position')->nullable()->after('stato');
            $table->index(['stato', 'board_position']);
        });

        // Backfill: compact positions per stato ordered by data_ingresso
        DB::table('commesse')
            ->whereNull('deleted_at')
            ->orderBy('data_ingresso')
            ->get(['id', 'stato'])
            ->groupBy('stato')
            ->each(function ($gruppo) {
                foreach ($gruppo as $i => $c) {
                    DB::table('commesse')
                        ->where('id', $c->id)
                        ->update(['board_position' => $i + 1]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('commesse', function (Blueprint $table) {
            $table->dropIndex(['stato', 'board_position']);
            $table->dropColumn('board_position');
        });
    }
};
