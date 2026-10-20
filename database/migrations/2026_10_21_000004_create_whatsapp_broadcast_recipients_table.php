<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn', 15)->index();
            $table->json('variables');
            $table->foreignId('whatsapp_broadcast_id')->constrained('whatsapp_broadcasts')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['whatsapp_broadcast_id', 'msisdn'], 'broadcast_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcast_recipients');
    }
};
