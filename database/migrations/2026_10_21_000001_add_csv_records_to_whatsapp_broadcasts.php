<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->json('recipient_records')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcasts', fn (Blueprint $table) => $table->dropColumn('recipient_records'));
    }
};
