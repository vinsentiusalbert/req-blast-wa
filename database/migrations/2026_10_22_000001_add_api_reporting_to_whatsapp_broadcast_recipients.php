<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing installations may already have the original reporting migration.
        if (Schema::hasColumns('whatsapp_broadcast_recipients', [
            'status', 'attempts', 'last_attempt_at', 'sent_at', 'failed_at',
            'failure_reason', 'http_status', 'provider_response',
        ])) {
            return;
        }

        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->longText('provider_response')->nullable();
            $table->index(['whatsapp_broadcast_id', 'status'], 'wa_broadcast_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->dropIndex('wa_broadcast_status_idx');
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status', 'attempts', 'last_attempt_at', 'sent_at', 'failed_at',
                'failure_reason', 'http_status', 'provider_response',
            ]);
        });
    }
};
