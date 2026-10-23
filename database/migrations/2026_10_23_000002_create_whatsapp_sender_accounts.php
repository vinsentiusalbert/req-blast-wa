<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_sender_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('sender', 15)->unique();
            $table->string('api_base_url', 2048);
            $table->text('api_key');
            $table->string('status', 16)->default('inactive')->index();
            $table->unsignedInteger('min_delay_seconds')->default(30);
            $table->unsignedInteger('max_delay_seconds')->default(60);
            $table->unsignedInteger('daily_limit')->default(100);
            $table->unsignedInteger('failure_cooldown_seconds')->default(900);
            $table->unsignedInteger('failure_threshold')->default(3);
            $table->date('usage_date')->nullable();
            $table->unsignedInteger('daily_attempts')->default(0);
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->dateTime('next_available_at')->nullable()->index();
            $table->dateTime('busy_until')->nullable();
            $table->string('lease_token', 36)->nullable();
            $table->string('last_error', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->foreignId('whatsapp_sender_account_id')->nullable()
                ->constrained('whatsapp_sender_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('whatsapp_sender_account_id');
        });
        Schema::dropIfExists('whatsapp_sender_accounts');
    }
};
