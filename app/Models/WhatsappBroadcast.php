<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class WhatsappBroadcast extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DRAFT = self::STATUS_PENDING;

    public const STATUS_APPROVE = 'approve';

    public const STATUS_FINISH = 'finish';

    public const STATUS_REJECT = 'reject';

    public const STATUS_LABELS = ['pending' => 'Pending', 'approve' => 'Approved', 'reject' => 'Rejected', 'finish' => 'Finished'];

    protected $attributes = ['status' => self::STATUS_PENDING, 'unit_price' => 500];

    protected $fillable = ['name', 'whatsapp_template_id', 'recipients', 'recipient_count', 'recipient_records', 'scheduled_at'];

    protected function casts(): array
    {
        return ['recipients' => 'array', 'recipient_count' => 'integer', 'recipient_records' => 'array', 'scheduled_at' => 'immutable_datetime', 'unit_price' => 'integer', 'charged_amount' => 'integer', 'charged_at' => 'immutable_datetime'];
    }

    public function estimatedCost(): int
    {
        if ($this->unit_price < 0 || $this->recipient_count < 0 || ($this->unit_price > 0 && $this->recipient_count > intdiv(User::MAX_BALANCE, $this->unit_price))) {
            throw ValidationException::withMessages(['balance' => 'The broadcast cost exceeds the supported balance limit. Reduce the recipient count.']);
        }

        return $this->unit_price * $this->recipient_count;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedRecipients(): HasMany
    {
        return $this->hasMany(WhatsappBroadcastRecipient::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }
}
