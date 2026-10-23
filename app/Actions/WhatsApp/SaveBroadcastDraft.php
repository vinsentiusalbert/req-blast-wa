<?php

namespace App\Actions\WhatsApp;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Services\WhatsApp\BroadcastCsv;
use App\Services\WhatsApp\RecipientParser;
use Carbon\CarbonImmutable;
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
            throw ValidationException::withMessages(['recipients_csv' => 'Upload the recipient CSV first.']);
        }
        $recipients = array_column($records, 'msisdn');

        return DB::transaction(function () use ($user, $data, $broadcast, $recipients, $records, $csv) {
            $template = $user->whatsappTemplates()->lockForUpdate()->findOrFail($data['whatsapp_template_id']);
            if (! $template->isApproved()) {
                throw ValidationException::withMessages(['whatsapp_template_id' => 'The template is awaiting administrator approval or has been resubmitted.']);
            }
            $draft = $broadcast ? $user->whatsappBroadcasts()->lockForUpdate()->findOrFail($broadcast->id) : $user->whatsappBroadcasts()->make();
            if ($draft->exists && ! in_array($draft->status, [WhatsappBroadcast::STATUS_PENDING, WhatsappBroadcast::STATUS_REJECT], true)) {
                throw ValidationException::withMessages(['name' => 'Approved or finished broadcasts cannot be edited.']);
            }
            $draft->status = WhatsappBroadcast::STATUS_PENDING;
            $draft->rejection_reason = null;
            $draft->rejected_at = null;
            $draft->rejected_by = null;
            $draft->revision = $draft->exists ? $draft->revision + 1 : 1;
            $csv->validateVariables($template, $records);
            $draft->unit_price = User::findOrFail($user->id)->broadcast_rate;
            $draft->recipient_count = count($recipients);
            $draft->estimatedCost();
            $draft->fill([
                'name' => $data['name'],
                'whatsapp_template_id' => $template->id,
                'recipients' => $recipients,
                'recipient_count' => count($recipients),
                'recipient_records' => $records,
                'scheduled_at' => array_key_exists('schedule_date', $data) || array_key_exists('schedule_time', $data)
                    ? (! empty($data['schedule_date']) && ! empty($data['schedule_time'])
                        ? CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['schedule_date'].' '.$data['schedule_time'], 'Asia/Jakarta')->utc() : null)
                    : $draft->scheduled_at,
            ])->save();

            return $draft;
        });
    }
}
