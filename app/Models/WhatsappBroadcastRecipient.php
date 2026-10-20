<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappBroadcastRecipient extends Model
{
    protected $fillable = ['msisdn', 'variables'];

    protected function casts(): array
    {
        return ['variables' => 'array'];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsappBroadcast::class, 'whatsapp_broadcast_id');
    }
}
