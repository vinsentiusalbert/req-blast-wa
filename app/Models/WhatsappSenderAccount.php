<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class WhatsappSenderAccount extends Model
{
    protected $fillable = [
        'name', 'sender', 'api_base_url', 'api_key', 'status',
        'min_delay_seconds', 'max_delay_seconds', 'daily_limit',
        'failure_cooldown_seconds', 'failure_threshold',
    ];

    protected $hidden = ['api_key', 'lease_token'];

    protected function apiKey(): Attribute
    {
        return Attribute::make(get: function (?string $value): ?string {
            if ($value === null) {
                return null;
            }

            // Keep previously encrypted keys usable; new writes are stored as supplied.
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return $value;
            }
        });
    }

    protected function casts(): array
    {
        return [
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
