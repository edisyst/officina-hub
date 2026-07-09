<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('every_km')->nullable();
            $table->unsignedInteger('every_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_rules');
    }
};
