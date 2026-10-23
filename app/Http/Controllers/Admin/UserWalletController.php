<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserWalletController extends Controller
{
    public function show(User $user): View
    {
        return view('wallet.show', ['account' => $user, 'admin' => true, 'transactions' => $user->walletTransactions()->with(['actor', 'broadcast'])->latest('id')->paginate(20)]);
    }

    public function credit(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.User::MAX_BALANCE],
            'reference' => ['required', 'uuid'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($request, $user, $data) {
            $account = User::lockForUpdate()->findOrFail($user->id);
            $existing = WalletTransaction::where('reference', $data['reference'])->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->user_id !== $account->id || $existing->type !== 'credit' || $existing->amount !== (int) $data['amount']) {
                    throw ValidationException::withMessages(['amount' => 'This transaction reference has already been used. Reload the page.']);
                }

                return;
            }
            if ($account->balance > User::MAX_BALANCE - (int) $data['amount']) {
                throw ValidationException::withMessages(['amount' => 'This credit would exceed the balance limit of IDR '.number_format(User::MAX_BALANCE, 0, '.', ',').'.']);
            }
            $account->balance += (int) $data['amount'];
            $account->save();
            WalletTransaction::create([
                'user_id' => $account->id, 'actor_id' => $request->user()->id,
                'reference' => $data['reference'], 'type' => 'credit', 'amount' => $data['amount'],
                'balance_after' => $account->balance, 'note' => $data['note'] ?? null,
            ]);
        }, 3);

        return to_route('admin.users.wallet', $user)->with('status', 'The balance credit was recorded successfully.');
    }

    public function rate(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['broadcast_rate' => ['required', 'integer', 'min:0', 'max:'.User::MAX_BROADCAST_RATE]]);
        DB::transaction(function () use ($user, $data) {
            $account = User::lockForUpdate()->findOrFail($user->id);
            $account->broadcast_rate = (int) $data['broadcast_rate'];
            $account->save();
        });

        return to_route('admin.users.wallet', $user)->with('status', 'The rate per number has been updated. It applies to new submissions and resubmissions.');
    }
}
