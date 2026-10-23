<?php

namespace App\Actions\WhatsApp;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WhatsappBroadcast;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChargeBroadcast
{
    // Runs within the approval transaction, after locking the broadcast row.
    public function handle(WhatsappBroadcast $broadcast, User $admin): void
    {
        $owner = User::lockForUpdate()->findOrFail($broadcast->user_id);
        if ($broadcast->charged_at || WalletTransaction::where('whatsapp_broadcast_id', $broadcast->id)->exists()) {
            throw ValidationException::withMessages(['balance' => 'This broadcast has already been charged. Reload the page.']);
        }
        $count = count($broadcast->recipient_records ?: $broadcast->recipients ?: []);
        if ($count < 1 || $count !== $broadcast->recipient_count) {
            throw ValidationException::withMessages(['balance' => 'The recipient count does not match. Revise the broadcast submission.']);
        }
        $amount = $broadcast->estimatedCost();
        if ($owner->balance < $amount) {
            throw ValidationException::withMessages(['balance' => 'Insufficient user balance. Cost: IDR '.number_format($amount, 0, '.', ',').'; balance: IDR '.number_format($owner->balance, 0, '.', ',').'. Add funds before approving.']);
        }
        $owner->balance -= $amount;
        $owner->save();
        WalletTransaction::create([
            'user_id' => $owner->id, 'actor_id' => $admin->id,
            'whatsapp_broadcast_id' => $broadcast->id, 'reference' => (string) Str::uuid(),
            'type' => 'broadcast', 'amount' => -$amount, 'balance_after' => $owner->balance,
            'note' => $count.' numbers × IDR '.number_format($broadcast->unit_price, 0, '.', ','),
        ]);
        $broadcast->charged_amount = $amount;
        $broadcast->charged_at = now();
    }
}
