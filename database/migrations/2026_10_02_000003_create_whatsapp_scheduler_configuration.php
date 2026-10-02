<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_senders')) {
            Schema::create('whatsapp_senders', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('phone_number', 15)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('whatsapp_campaign_schedules')) {
            Schema::create('whatsapp_campaign_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_broadcast_id')->constrained()->cascadeOnDelete();
                $table->foreignId('whatsapp_sender_id')->constrained()->restrictOnDelete();
                $table->date('send_date');
                $table->string('timezone', 50)->default('Asia/Bangkok');
                $table->unsignedInteger('message_count');
                $table->string('status', 20)->default('pending');
                $table->timestamps();
                $table->index(['status', 'send_date']);
            });
        }
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_broadcast_recipients', 'whatsapp_campaign_schedule_id')) {
                $table->foreignId('whatsapp_campaign_schedule_id')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_broadcast_recipients', 'delivery_status')) {
                $table->string('delivery_status', 20)->default('pending');
            }
            if (! Schema::hasColumn('whatsapp_broadcast_recipients', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_broadcast_recipients', 'last_error')) {
                $table->text('last_error')->nullable();
            }
        });
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            if (! collect(Schema::getForeignKeys('whatsapp_broadcast_recipients'))->contains('name', 'wa_recipient_schedule_fk')) {
                $table->foreign('whatsapp_campaign_schedule_id', 'wa_recipient_schedule_fk')->references('id')->on('whatsapp_campaign_schedules')->nullOnDelete();
            }
            if (! Schema::hasIndex('whatsapp_broadcast_recipients', 'wa_delivery_queue_index')) {
                $table->index(['whatsapp_campaign_schedule_id', 'delivery_status'], 'wa_delivery_queue_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->dropIndex('wa_delivery_queue_index');
            $table->dropForeign('wa_recipient_schedule_fk');
            $table->dropColumn('whatsapp_campaign_schedule_id');
            $table->dropColumn(['delivery_status', 'sent_at', 'last_error']);
        });
        Schema::dropIfExists('whatsapp_campaign_schedules');
        Schema::dropIfExists('whatsapp_senders');
    }
};
