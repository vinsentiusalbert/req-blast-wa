<?php

namespace App\Actions\WhatsApp;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Services\WhatsApp\RecipientParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveBroadcastDraft
{
    public function __construct(private readonly RecipientParser $recipients) {}

    public function handle(User $user, array $data, ?WhatsappBroadcast $broadcast = null): WhatsappBroadcast
    {
        $recipients = $this->recipients->parse($data['recipients']);

        return DB::transaction(function () use ($user, $data, $broadcast, $recipients) {
            $template = $user->whatsappTemplates()->lockForUpdate()->findOrFail($data['whatsapp_template_id']);
            if (! $template->isApproved()) {
                throw ValidationException::withMessages(['whatsapp_template_id' => 'Template belum disetujui admin atau sedang diajukan ulang.']);
            }
            $draft = $broadcast ?? $user->whatsappBroadcasts()->make();
            $draft->fill([
                'name' => $data['name'],
                'whatsapp_template_id' => $template->id,
                'recipients' => $recipients,
                'recipient_count' => count($recipients),
            ])->save();

            return $draft;
        });
    }
}
