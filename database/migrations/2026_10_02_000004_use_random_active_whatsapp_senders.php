<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep existing values for compatibility; new configuration does not use them.
        Schema::table('whatsapp_senders', function (Blueprint $table) {
            $table->string('name', 100)->nullable()->change();
        });
        Schema::table('whatsapp_campaign_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('whatsapp_sender_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Legacy columns remain nullable to preserve records created without a fixed sender.
    }
};
