<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdi_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->nullable()->constrained('documenti')->nullOnDelete();
            $table->string('azione', 50);
            $table->string('esito', 20);
            $table->text('dettaglio')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdi_log');
    }
};
