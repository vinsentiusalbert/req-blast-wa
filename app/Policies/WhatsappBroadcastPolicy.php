<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappBroadcast;

class WhatsappBroadcastPolicy
{
    public function view(User $user, WhatsappBroadcast $broadcast): bool
    {
        return $user->role === User::ROLE_USER && $broadcast->user_id === $user->id;
    }

    public function update(User $user, WhatsappBroadcast $broadcast): bool
    {
        return false;
    }
}
