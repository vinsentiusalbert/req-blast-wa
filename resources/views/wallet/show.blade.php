@extends('layouts.app')
@section('title', $admin ? 'Manage User Balance' : 'Balance & Transactions')
@section('content')
@if($admin)<a class="back-link" href="{{ route('admin.users.index') }}">&larr; Back to users</a>@endif
<div class="page-heading"><div><span class="eyebrow">BALANCE & TRANSACTIONS</span><h1>{{ $admin ? $account->name : 'Your balance' }}</h1><p class="muted">{{ $admin ? '@'.$account->username.' · '.$account->email : 'Track your balance and broadcast costs.' }}</p></div></div>
<div class="wallet-summary">
    <article class="card"><span class="banner-eyebrow">AVAILABLE BALANCE</span><strong class="wallet-value">IDR {{ number_format($account->balance, 0, '.', ',') }}</strong><small>Your balance is charged when an administrator approves a broadcast.</small></article>
    <article class="card"><span class="banner-eyebrow">{{ $admin ? 'USER RATE' : 'YOUR BROADCAST RATE' }}</span><strong class="wallet-value">IDR {{ number_format($account->broadcast_rate, 0, '.', ',') }} <small>/ number</small></strong><small>The rate is locked when a broadcast is submitted or resubmitted.</small></article>
</div>
@if($admin)
<div class="wallet-summary">
    <form class="card wallet-form" method="POST" action="{{ route('admin.users.wallet.credit', $account) }}">
        @csrf
        <input type="hidden" name="reference" value="{{ old('reference', (string) \Illuminate\Support\Str::uuid()) }}">
        <h2>Add funds</h2>
        <div class="field-group"><label for="credit-amount" class="field-label">Amount (IDR)</label><input id="credit-amount" class="text-input" name="amount" type="number" min="1" max="{{ \App\Models\User::MAX_BALANCE }}" step="1" value="{{ old('amount') }}" placeholder="Example: 100000" required></div>
        <div class="field-group"><label for="credit-note" class="field-label">Note (optional)</label><input id="credit-note" class="text-input" name="note" maxlength="255" value="{{ old('note') }}" placeholder="Example: Bank transfer deposit"></div>
        <button class="button" type="submit">Add funds</button>
    </form>
    <form class="card wallet-form" method="POST" action="{{ route('admin.users.wallet.rate', $account) }}">
        @csrf @method('PATCH')
        <h2>Rate per number</h2><p class="muted dashboard-description">Set a custom broadcast rate for this user. The default is IDR 500 per number.</p>
        <div class="field-group"><label for="broadcast-rate" class="field-label">Price per number (IDR)</label><input id="broadcast-rate" class="text-input" name="broadcast_rate" type="number" min="0" max="{{ \App\Models\User::MAX_BROADCAST_RATE }}" step="1" value="{{ old('broadcast_rate', $account->broadcast_rate) }}" required><small class="field-hint">IDR 0 means free. Existing submissions keep their quoted rate.</small></div>
        <button class="button secondary" type="submit">Save rate</button>
    </form>
</div>
@else
<p class="wa-info">Contact your administrator to add funds. Pending and rejected submissions are not charged. Total cost = recipient numbers × the submitted rate.</p>
@endif
<section class="card table-card">
    <div class="section-heading"><div><h2>Transaction history</h2><p class="muted">Balance credits and broadcast approval charges.</p></div></div>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Balance history"><table class="wallet-ledger"><thead><tr><th>Time (UTC+7)</th><th>Transaction</th><th>Amount</th><th>Balance after</th></tr></thead><tbody>
        @forelse($transactions as $transaction)
        <tr><td>{{ $transaction->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</td><td><strong>{{ $transaction->type === 'credit' ? 'Balance credit' : 'Broadcast cost' }}</strong>
            @if($transaction->broadcast)<span class="cell-detail"><a class="text-link" href="{{ route($admin ? 'admin.whatsapp.broadcasts.show' : 'user.whatsapp.broadcasts.show', $transaction->broadcast) }}">{{ $transaction->broadcast->name }}</a></span>@endif
            @if($transaction->type === 'broadcast' && $transaction->broadcast)
                <span class="cell-detail">{{ number_format($transaction->broadcast->recipient_count) }} {{ $transaction->broadcast->recipient_count === 1 ? 'number' : 'numbers' }} × IDR {{ number_format($transaction->broadcast->unit_price) }}</span>
            @elseif($transaction->note)<span class="cell-detail">{{ $transaction->note }}</span>@endif
            @if($admin)<span class="cell-detail">By {{ $transaction->actor?->name ?? 'Admin' }}</span>@endif
        </td><td class="{{ $transaction->amount > 0 ? 'wallet-credit' : '' }}">{{ $transaction->amount > 0 ? '+' : ($transaction->amount < 0 ? '−' : '') }}IDR {{ number_format(abs($transaction->amount), 0, '.', ',') }}</td><td>IDR {{ number_format($transaction->balance_after, 0, '.', ',') }}</td></tr>
        @empty<tr><td colspan="4" class="empty">No balance transactions yet.</td></tr>@endforelse
    </tbody></table></div>
    @if($transactions->hasPages())<nav class="pagination" aria-label="Transaction pages"><span>{{ $transactions->currentPage() }} / {{ $transactions->lastPage() }}</span><div>@if($transactions->previousPageUrl())<a href="{{ $transactions->previousPageUrl() }}">&larr; Previous</a>@endif @if($transactions->nextPageUrl())<a href="{{ $transactions->nextPageUrl() }}">Next &rarr;</a>@endif</div></nav>@endif
</section>
@endsection
