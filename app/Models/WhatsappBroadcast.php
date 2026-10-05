<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappBroadcast extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const SENDING_TIMEZONE = 'Asia/Bangkok';

    public const STATUS_LABELS = [
        'draft' => 'Draft · Belum dikirim',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'processing' => 'Diproses',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = ['name', 'whatsapp_template_id', 'recipients', 'recipient_count', 'sending_starts_at', 'sending_ends_at'];

    protected function casts(): array
    {
        return [
            'recipients' => 'array', 'recipient_count' => 'integer',
            'sending_starts_at' => 'immutable_datetime', 'sending_ends_at' => 'immutable_datetime',
        ];
    }

    public function isWithinSendingWindow(CarbonInterface $time): bool
    {
        return $this->sending_starts_at !== null && $this->sending_ends_at !== null
            && $time->greaterThanOrEqualTo($this->sending_starts_at)
            && $time->lessThan($this->sending_ends_at);
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
