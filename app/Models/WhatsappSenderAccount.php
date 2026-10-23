<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappSenderAccount extends Model
{
    protected $fillable = [
        'name', 'sender', 'api_base_url', 'api_key', 'status',
        'min_delay_seconds', 'max_delay_seconds', 'daily_limit',
        'failure_cooldown_seconds', 'failure_threshold',
    ];

    protected $hidden = ['api_key', 'lease_token'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'min_delay_seconds' => 'integer',
            'max_delay_seconds' => 'integer',
            'daily_limit' => 'integer',
            'failure_cooldown_seconds' => 'integer',
            'failure_threshold' => 'integer',
            'daily_attempts' => 'integer',
            'consecutive_failures' => 'integer',
            'usage_date' => 'date',
            'next_available_at' => 'immutable_datetime',
            'busy_until' => 'immutable_datetime',
        ];
    }
}
