<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappBroadcast extends Model
{
    public const STATUS_DRAFT = 'draft';

    protected $fillable = ['name', 'whatsapp_template_id', 'recipients', 'recipient_count'];

    protected function casts(): array
    {
        return ['recipients' => 'array', 'recipient_count' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }
}
