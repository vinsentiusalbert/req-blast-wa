<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending')->change();
        });
        DB::table('whatsapp_broadcasts')->where('status', 'draft')->update(['status' => 'pending']);
    }

    public function down(): void
    {
        DB::table('whatsapp_broadcasts')->whereIn('status', ['pending', 'approve', 'finish'])->update(['status' => 'draft']);
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('finished_by');
            $table->dropColumn(['revision', 'approved_at', 'finished_at']);
            $table->string('status', 20)->default('draft')->change();
        });
    }
};
