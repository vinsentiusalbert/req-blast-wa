@extends('layouts.whatsapp')
@section('title', 'Nomor Pengirim WhatsApp')
@section('content')
<div class="page-heading"><div><h1>Nomor Pengirim WhatsApp</h1><p class="muted">Kelola nomor yang dapat digunakan untuk jadwal campaign.</p></div></div>
<section class="card">
    <h2>Tambah nomor pengirim</h2>
    <form method="POST" action="{{ route('admin.whatsapp.senders.store') }}" class="wa-builder">
        @csrf
        <div class="template-builder-field"><label for="sender-phone">Nomor WhatsApp</label><input id="sender-phone" name="phone_number" value="{{ old('phone_number') }}" placeholder="6281234567890" required></div>
        <div class="template-builder-field"><label for="sender-active">Status</label><select id="sender-active" name="is_active"><option value="1" @selected(old('is_active', '1') == '1')>Aktif</option><option value="0" @selected(old('is_active', '1') == '0')>Nonaktif</option></select></div>
        <p><button class="button" type="submit">Tambah Nomor</button></p>
    </form>
</section>
<section class="card table-card">
    <div class="table-scroll"><table><thead><tr><th>Nomor</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($senders as $sender)
        <tr><td>+{{ $sender->phone_number }}</td><td>{{ $sender->is_active ? 'Aktif' : 'Nonaktif' }}</td><td><form method="POST" action="{{ route('admin.whatsapp.senders.update', $sender) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $sender->is_active ? '0' : '1' }}"><button class="button secondary small" type="submit">{{ $sender->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>
        @empty<tr><td colspan="3" class="empty">Belum ada nomor pengirim.</td></tr>@endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $senders])
</section>
@endsection
