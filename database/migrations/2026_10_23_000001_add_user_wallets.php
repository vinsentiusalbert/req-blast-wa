<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('broadcast_rate')->default(500);
        });
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price')->default(500);
            $table->unsignedBigInteger('charged_amount')->nullable();
            $table->timestamp('charged_at')->nullable();
        });
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('whatsapp_broadcast_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->uuid('reference')->unique();
            $table->string('type', 20);
            $table->bigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::table('whatsapp_broadcasts', fn (Blueprint $table) => $table->dropColumn(['unit_price', 'charged_amount', 'charged_at']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['balance', 'broadcast_rate']));
    }
};
