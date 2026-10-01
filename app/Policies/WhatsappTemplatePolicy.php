<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappTemplate;

class WhatsappTemplatePolicy
{
    public function view(User $user, WhatsappTemplate $template): bool
    {
        return $user->isAdmin() || ($user->role === User::ROLE_USER && $template->user_id === $user->id);
    }

    public function update(User $user, WhatsappTemplate $template): bool
    {
        return $user->role === User::ROLE_USER && $template->user_id === $user->id;
    }

    public function review(User $user, WhatsappTemplate $template): bool
    {
        return $user->isAdmin();
    }
}
