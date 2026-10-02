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
            $rows = $data['recipient_rows'] ?? ($broadcast
                ? $broadcast->recipientEntries()->get()->map(fn ($entry) => ['phone_number' => $entry->phone_number, 'variables' => $entry->variables ?? []])->all()
                : array_map(fn ($number) => ['phone_number' => $number, 'variables' => []], $recipients));
            preg_match_all('/\{\{\s*(var[1-9][0-9]*)\s*\}\}/', ($template->header_text ?? '')."\n".$template->body, $matches);
            foreach ($rows as $row) {
                foreach (array_unique($matches[1]) as $variable) {
                    if (trim($row['variables'][$variable] ?? '') === '') {
                        throw ValidationException::withMessages(['recipient_file' => 'Nilai '.$variable.' wajib diisi untuk nomor '.$row['phone_number'].'.']);
                    }
                }
            }
            $draft = $broadcast ?? $user->whatsappBroadcasts()->make();
            $draft->fill([
                'name' => $data['name'],
                'whatsapp_template_id' => $template->id,
                'recipients' => $recipients,
                'recipient_count' => count($recipients),
            ])->save();

            $draft->recipientEntries()->delete();
            $draft->recipientEntries()->createMany(array_map(fn ($row) => [
                'user_id' => $user->id,
                'phone_number' => $row['phone_number'],
                'variables' => $row['variables'],
            ], $rows));

            return $draft;
        });
    }
}
