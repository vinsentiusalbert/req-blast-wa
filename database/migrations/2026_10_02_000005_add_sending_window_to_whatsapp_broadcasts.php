<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->timestamp('sending_starts_at')->nullable();
            $table->timestamp('sending_ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->dropColumn(['sending_starts_at', 'sending_ends_at']);
        });
    }
};
