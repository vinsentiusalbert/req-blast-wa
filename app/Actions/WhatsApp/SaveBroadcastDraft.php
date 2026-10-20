<?php

namespace App\Actions\WhatsApp;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Services\WhatsApp\BroadcastCsv;
use App\Services\WhatsApp\RecipientParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveBroadcastDraft
{
    public function __construct(private readonly RecipientParser $recipients) {}

    public function handle(User $user, array $data, ?WhatsappBroadcast $broadcast = null): WhatsappBroadcast
    {
        $csv = new BroadcastCsv;
        $records = isset($data['recipients_csv'])
            ? $csv->parse($data['recipients_csv']->getRealPath())
            : (isset($data['recipients']) ? array_map(fn ($number) => ['msisdn' => $number], $this->recipients->parse($data['recipients'])) : ($broadcast?->recipient_records ?? array_map(fn ($number) => ['msisdn' => $number], $broadcast?->recipients ?? [])));
        if (! $records) {
            throw ValidationException::withMessages(['recipients_csv' => 'Unggah CSV penerima terlebih dahulu.']);
        }
        $recipients = array_column($records, 'msisdn');

        return DB::transaction(function () use ($user, $data, $broadcast, $recipients, $records, $csv) {
            $template = $user->whatsappTemplates()->lockForUpdate()->findOrFail($data['whatsapp_template_id']);
            if (! $template->isApproved()) {
                throw ValidationException::withMessages(['whatsapp_template_id' => 'Template belum disetujui admin atau sedang diajukan ulang.']);
            }
            $draft = $broadcast ? $user->whatsappBroadcasts()->lockForUpdate()->findOrFail($broadcast->id) : $user->whatsappBroadcasts()->make();
            if ($draft->exists && ! in_array($draft->status, [WhatsappBroadcast::STATUS_PENDING, WhatsappBroadcast::STATUS_REJECT], true)) {
                throw ValidationException::withMessages(['name' => 'Broadcast yang sudah Approve atau Finish tidak dapat diubah.']);
            }
            $draft->status = WhatsappBroadcast::STATUS_PENDING;
            $draft->rejection_reason = null;
            $draft->rejected_at = null;
            $draft->rejected_by = null;
            $draft->revision = $draft->exists ? $draft->revision + 1 : 1;
            $csv->validateVariables($template, $records);
            $draft->fill([
                'name' => $data['name'],
                'whatsapp_template_id' => $template->id,
                'recipients' => $recipients,
                'recipient_count' => count($recipients),
                'recipient_records' => $records,
            ])->save();

            return $draft;
        });
    }
}
