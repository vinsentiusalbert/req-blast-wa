@extends('layouts.whatsapp')
@section('title', 'Approval Broadcast')
@section('content')
<div class="page-heading"><div><h1>Approval Broadcast</h1><p class="muted">Lihat semua campaign beserta pembuatnya dan kelola status approval.</p></div></div>
<section class="card table-card">
    <form method="GET" class="search-form wa-filters wa-review-filters" action="{{ route('admin.whatsapp.broadcasts.index') }}">
        <label class="sr-only" for="status">Status</label><select id="status" name="status"><option value="">Semua status</option>@foreach(\App\Models\WhatsappBroadcast::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>
        <label class="sr-only" for="campaign-user">User pembuat</label><select id="campaign-user" name="user_id"><option value="">Semua user</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>{{ $user->name }} ({{ '@'.$user->username }})</option>@endforeach</select>
        <button class="button secondary" type="submit">Filter</button>
        @if($status || !empty($filters['user_id']))<a class="text-link" href="{{ route('admin.whatsapp.broadcasts.index') }}">Reset filter</a>@endif
    </form>
    <div class="table-scroll"><table><thead><tr><th>Broadcast</th><th>Dibuat oleh</th><th>Penerima</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($broadcasts as $broadcast)
        <tr><td>{{ $broadcast->name }}<span class="cell-detail">{{ $broadcast->template->name }}</span></td><td>{{ $broadcast->user->name }}<span class="cell-detail">{{ '@'.$broadcast->user->username }}</span></td><td>{{ number_format($broadcast->recipient_count, 0, ',', '.') }}</td><td><span class="badge campaign-status campaign-status--{{ $broadcast->status }}">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span></td><td><a class="button secondary small" href="{{ route('admin.whatsapp.broadcasts.show', $broadcast) }}">Lihat Detail</a></td></tr>
    @empty
        <tr><td colspan="5">Tidak ada campaign yang sesuai dengan filter.</td></tr>
    @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $broadcasts])
</section>
@endsection
