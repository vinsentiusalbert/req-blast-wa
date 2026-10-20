<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappBroadcast extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DRAFT = self::STATUS_PENDING;

    public const STATUS_APPROVE = 'approve';

    public const STATUS_FINISH = 'finish';

    public const STATUS_REJECT = 'reject';

    public const STATUS_LABELS = ['pending' => 'Pending', 'approve' => 'Approve', 'reject' => 'Reject', 'finish' => 'Finish'];

    protected $attributes = ['status' => self::STATUS_PENDING];

    protected $fillable = ['name', 'whatsapp_template_id', 'recipients', 'recipient_count', 'recipient_records'];

    protected function casts(): array
    {
        return ['recipients' => 'array', 'recipient_count' => 'integer', 'recipient_records' => 'array'];
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
