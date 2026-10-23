<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function show(Request $request): View
    {
        $account = $request->user();

        return view('wallet.show', ['account' => $account, 'admin' => false, 'transactions' => $account->walletTransactions()->with(['actor', 'broadcast'])->latest('id')->paginate(20)]);
    }
}
