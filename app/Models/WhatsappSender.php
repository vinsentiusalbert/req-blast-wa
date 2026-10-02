<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappSender extends Model
{
    protected $fillable = ['phone_number', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
