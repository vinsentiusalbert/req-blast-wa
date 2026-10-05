<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappBroadcastRecipient extends Model
{
    public const SUCCESS_STATUSES = ['success', 'delivered', 'read'];

    public const REPORT_LABELS = [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'success' => 'Success',
        'failed' => 'Failed',
    ];

    protected $fillable = ['user_id', 'phone_number', 'variables'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'sent_at' => 'datetime'];
    }

    public function reportStatus(): string
    {
        return match (true) {
            in_array($this->delivery_status, self::SUCCESS_STATUSES, true) => 'success',
            $this->delivery_status === 'failed' => 'failed',
            $this->delivery_status === 'sent' => 'sent',
            default => 'pending',
        };
    }

    public function failureDescription(): string
    {
        return $this->reportStatus() === 'failed'
            ? ($this->last_error ?: 'Alasan kegagalan belum tersedia.')
            : '—';
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
