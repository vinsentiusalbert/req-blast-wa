<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappBroadcast extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_LABELS = [
        'draft' => 'Draft · Belum dikirim',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'processing' => 'Diproses',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = ['name', 'whatsapp_template_id', 'recipients', 'recipient_count'];

    protected function casts(): array
    {
        return ['recipients' => 'array', 'recipient_count' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipientEntries(): HasMany
    {
        return $this->hasMany(WhatsappBroadcastRecipient::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(WhatsappCampaignSchedule::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }
}
