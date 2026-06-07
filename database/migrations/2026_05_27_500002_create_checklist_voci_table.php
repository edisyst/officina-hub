<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_voci', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->string('etichetta', 200);
            $table->string('tipo', 30)->default('si_no');  // si_no | numerico | testo_libero | foto_obbligatoria
            $table->boolean('obbligatoria')->default(false);
            $table->string('unita_misura', 20)->nullable();  // per tipo numerico
            $table->unsignedSmallInteger('ordinamento')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_voci');
    }
};
