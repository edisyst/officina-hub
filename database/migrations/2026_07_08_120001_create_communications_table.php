<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('clienti')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('commesse')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('channel', ['whatsapp', 'sms', 'email', 'phone', 'note']);
            $table->enum('direction', ['outbound', 'inbound']);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->dateTime('occurred_at')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('work_order_id');
        });

        // FULLTEXT on body (MySQL/MariaDB only — SQLite test env skipped)
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE communications ADD FULLTEXT fulltext_body (body)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
