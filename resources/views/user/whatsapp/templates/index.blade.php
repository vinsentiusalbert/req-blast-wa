@extends('user.whatsapp.layout')
@section('title', 'WhatsApp Templates')
@section('content')
<div class="page-heading"><div><span class="eyebrow">WHATSAPP BROADCAST</span><h1>WhatsApp Templates</h1><p class="muted">Create and manage message templates, then submit them for administrator approval.</p></div><a class="button" href="{{ route('user.whatsapp.templates.create') }}">+ Create Template</a></div>
<section class="card table-card">
    <div class="section-heading"><h2>Template list <span class="badge">{{ $templates->total() }}</span></h2><span class="wa-local-status">Saved to your account</span></div>
    <form method="GET" action="{{ route('user.whatsapp.templates.index') }}" class="search-form wa-filters">
        <label class="sr-only" for="template-search">Search template names</label><input id="template-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search template names..." maxlength="100">
        <label class="sr-only" for="language">Language</label><select id="language" name="language"><option value="">All languages</option>@foreach(\App\Models\WhatsappTemplate::LANGUAGES as $value => $label)<option value="{{ $value }}" @selected(($filters['language'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        <button class="button secondary" type="submit">Filter</button>
        @if(!empty($filters['search']) || !empty($filters['language']))<a href="{{ route('user.whatsapp.templates.index') }}">Reset</a>@endif
    </form>
    <div class="table-scroll" tabindex="0" role="region" aria-label="WhatsApp templates"><table><thead><tr><th>Template name</th><th>Language</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
        @forelse($templates as $template)
        <tr><td><a class="text-link" href="{{ route('user.whatsapp.templates.show', $template) }}">{{ $template->name }}</a><span class="cell-detail">{{ \Illuminate\Support\Str::limit($template->body, 65) }}</span></td><td>{{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</td><td>@include('partials.whatsapp-approval')</td><td>{{ $template->updated_at->timezone('Asia/Jakarta')->format('d M Y') }}</td><td><a class="button secondary small" href="{{ route('user.whatsapp.templates.show', $template) }}">View Details</a></td></tr>
        @empty
        <tr><td colspan="5"><div class="wa-empty"><span aria-hidden="true">☷</span><h2>{{ $templates->total() === 0 && empty($filters['search']) && empty($filters['language']) ? 'Create your first template' : 'No templates found' }}</h2><p>Compose a message, add an image, and preview it in WhatsApp style.</p><a class="button" href="{{ route('user.whatsapp.templates.create') }}">+ Create Template</a></div></td></tr>
        @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $templates])
</section>
@endsection
