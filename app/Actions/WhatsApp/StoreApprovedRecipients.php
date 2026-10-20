<?php

namespace App\Actions\WhatsApp;

use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;

class StoreApprovedRecipients
{
    // Called inside the approval transaction while the campaign row is locked.
    public function handle(WhatsappBroadcast $broadcast): void
    {
        $records = $broadcast->recipient_records
            ?: array_map(fn ($number) => ['msisdn' => $number], $broadcast->recipients);
        $timestamp = now();
        $batch = [];
        foreach ($records as $record) {
            $number = (string) $record['msisdn'];
            unset($record['msisdn']);
            $batch[] = [
                'msisdn' => $number,
                'variables' => json_encode((object) $record, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'whatsapp_broadcast_id' => $broadcast->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
            if (count($batch) === 100) {
                WhatsappBroadcastRecipient::insert($batch);
                $batch = [];
            }
        }
        if ($batch) {
            WhatsappBroadcastRecipient::insert($batch);
        }
    }
}
