<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perizie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinistro_id')->constrained('sinistri')->cascadeOnDelete();
            $table->string('perito_nome')->nullable();
            $table->string('perito_email')->nullable();
            $table->date('data_sopralluogo')->nullable();
            $table->date('data_ricezione')->nullable();
            $table->decimal('importo_liquidato', 12, 2)->nullable();
            $table->decimal('importo_franchigia', 12, 2)->default(0);
            $table->decimal('importo_scoperto_percentuale', 5, 2)->default(0);
            $table->decimal('importo_netto_liquidato', 12, 2)->nullable();
            $table->text('note_perito')->nullable();
            $table->string('allegato_perizia_path')->nullable();
            $table->boolean('accettata')->nullable();
            $table->text('motivo_contestazione')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perizie');
    }
};
