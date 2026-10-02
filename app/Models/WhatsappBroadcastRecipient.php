<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappBroadcastRecipient extends Model
{
    protected $fillable = ['user_id', 'phone_number', 'variables'];

    protected function casts(): array
    {
        return ['variables' => 'array'];
    }

    public function renderMessage(string $message): string
    {
        return preg_replace_callback('/\{\{\s*(var[1-9][0-9]*)\s*\}\}/',
            fn ($match) => ($this->variables ?? [])[$match[1]] ?? $match[0], $message);
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsappBroadcast::class, 'whatsapp_broadcast_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
