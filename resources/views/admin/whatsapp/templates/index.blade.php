@extends('layouts.whatsapp')
@section('title', 'Template Approvals')
@section('content')
<div class="page-heading"><div><span class="eyebrow">WHATSAPP · ADMIN</span><h1>Template Approvals</h1><p class="muted">Review all templates and their owners, and manage approvals.</p></div></div>
<section class="card table-card">
    <form method="GET" class="search-form wa-filters wa-review-filters" action="{{ route('admin.whatsapp.templates.index') }}">
        <label class="sr-only" for="review-search">Search template names</label><input id="review-search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search template names...">
        <label class="sr-only" for="review-status">Approval status</label><select id="review-status" name="status"><option value="">All statuses</option>@foreach(\App\Models\WhatsappTemplate::APPROVAL_LABELS as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>
        <label class="sr-only" for="review-user">Created by user</label><select id="review-user" name="user_id"><option value="">All users</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>{{ $user->name }} ({{ '@'.$user->username }})</option>@endforeach</select>
        <button class="button secondary" type="submit">Filter</button>
        @if($status || !empty($filters['user_id']) || !empty($filters['search']))<a class="text-link" href="{{ route('admin.whatsapp.templates.index') }}">Reset filters</a>@endif
    </form>
    <div class="table-scroll" role="region" tabindex="0" aria-label="Antrean persetujuan template"><table><thead><tr><th>Template</th><th>Created by</th><th>Status</th><th>Diajukan</th><th>Actions</th></tr></thead><tbody>
        @forelse($templates as $template)
            <tr><td><strong>{{ $template->name }}</strong><span class="cell-detail">Revision {{ $template->revision }} · {{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</span></td><td>{{ $template->user->name }}<span class="cell-detail">{{ '@'.$template->user->username }}</span></td><td>@include('partials.whatsapp-approval')</td><td>{{ $template->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} UTC+7</td><td><a class="button secondary small" href="{{ route('admin.whatsapp.templates.show', $template) }}">Review Template</a></td></tr>
        @empty
            <tr><td colspan="5" class="empty">No templates match these filters.</td></tr>
        @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $templates])
</section>
@endsection
