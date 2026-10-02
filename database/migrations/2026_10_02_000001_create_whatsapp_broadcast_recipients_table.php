<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_broadcast_id')->constrained(indexName: 'wa_recipient_broadcast_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 15);
            $table->timestamps();
            $table->unique(['whatsapp_broadcast_id', 'phone_number'], 'wa_recipient_broadcast_phone_unique');
        });

        DB::table('whatsapp_broadcasts')->orderBy('id')->chunkById(100, function ($broadcasts) {
            foreach ($broadcasts as $broadcast) {
                $rows = array_map(fn ($number) => [
                    'whatsapp_broadcast_id' => $broadcast->id,
                    'user_id' => $broadcast->user_id,
                    'phone_number' => $number,
                    'created_at' => $broadcast->created_at,
                    'updated_at' => $broadcast->updated_at,
                ], array_unique(json_decode($broadcast->recipients, true)));
                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table('whatsapp_broadcast_recipients')->insert($chunk);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcast_recipients');
    }
};
