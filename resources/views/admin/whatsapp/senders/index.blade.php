@extends('layouts.app')
@section('title', 'WhatsApp Senders')
@section('content')
<div class="page-heading"><div><span class="eyebrow">WHATSAPP SETTINGS</span><h1>WhatsApp senders<span class="accent-dot">.</span></h1><p class="muted">Active senders are selected randomly when their cooldown and daily limit allow a new send.</p></div><a class="button" href="{{ route('admin.whatsapp.senders.create') }}">+ Add sender</a></div>
<section class="card table-card">
    <div class="table-scroll" tabindex="0" role="region" aria-label="Sender accounts and limits"><table><thead><tr><th>Sender</th><th>Status</th><th>Random delay</th><th>Daily attempts (UTC+7)</th><th>Available after</th><th>Health</th><th>Manage</th></tr></thead><tbody>
        @forelse($senders as $sender)
            <tr>
                <td><strong>{{ $sender->name }}</strong><span class="cell-detail">{{ $sender->sender }}</span></td>
                <td><span class="badge {{ $sender->status === 'active' ? 'approved' : 'rejected' }}">{{ $sender->status === 'active' ? 'Active' : 'Non-Active' }}</span></td>
                <td>{{ $sender->min_delay_seconds }}&ndash;{{ $sender->max_delay_seconds }} seconds</td>
                <td>{{ $sender->usage_date?->format('Y-m-d') === now('Asia/Jakarta')->format('Y-m-d') ? $sender->daily_attempts : 0 }} / {{ $sender->daily_limit }}</td>
                <td>{{ $sender->next_available_at?->timezone('Asia/Jakarta')->format('d M Y H:i:s') ?? 'Ready' }}<span class="cell-detail">UTC+7</span></td>
                <td>{{ $sender->consecutive_failures }} consecutive failures<span class="cell-detail">{{ $sender->last_error }}</span></td>
                <td><a class="text-link" href="{{ route('admin.whatsapp.senders.edit', $sender) }}">Edit settings</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No sender accounts. Add an account and set it to Active to enable sending.</td></tr>
        @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $senders])
</section>
@endsection
