<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('veicoli')->cascadeOnDelete()->index();
            $table->foreignId('origin_work_order_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->foreignId('resolved_work_order_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->enum('source', ['declined', 'deadline', 'mileage']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('due_km')->nullable();
            $table->enum('status', ['pending', 'accepted', 'dismissed'])->default('pending');
            $table->string('dismissed_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_recommendations');
    }
};
