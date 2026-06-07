<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_compilate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained('checklist_templates');
            $table->foreignId('commessa_id')->constrained('commesse')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('completata_at')->nullable();
            $table->timestamps();
            $table->unique(['checklist_template_id', 'commessa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_compilate');
    }
};
