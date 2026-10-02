<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappCampaignSchedule extends Model
{
    protected $fillable = ['send_date', 'timezone', 'message_count'];

    protected function casts(): array
    {
        return ['send_date' => 'date', 'message_count' => 'integer'];
    }
}
