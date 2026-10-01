<?php

namespace App\Http\Controllers\User\WhatsApp;

use App\Actions\WhatsApp\SaveTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\SaveTemplateRequest;
use App\Models\WhatsappTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'language' => ['nullable', 'in:id,en']]);
        $templates = $request->user()->whatsappTemplates()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($filters['language'] ?? null, fn ($query, $language) => $query->where('language', $language))
            ->latest('id')->paginate(10)->withQueryString();

        return view('user.whatsapp.templates.index', compact('templates', 'filters'));
    }

    public function create(): View
    {
        return view('user.whatsapp.templates.form', ['template' => new WhatsappTemplate(['language' => 'id', 'header_type' => 'NONE', 'buttons' => []])]);
    }

    public function store(SaveTemplateRequest $request, SaveTemplate $action): RedirectResponse
    {
        $template = $action->handle($request->user(), $request->validated());

        return to_route('user.whatsapp.templates.show', $template)->with('status', 'Template berhasil diajukan. Tunggu persetujuan admin sebelum digunakan untuk broadcast.');
    }

    public function show(WhatsappTemplate $template): View
    {
        Gate::authorize('view', $template);

        return view('user.whatsapp.templates.show', compact('template'));
    }

    public function edit(WhatsappTemplate $template): View
    {
        Gate::authorize('update', $template);

        return view('user.whatsapp.templates.form', compact('template'));
    }

    public function update(SaveTemplateRequest $request, WhatsappTemplate $template, SaveTemplate $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated(), $template);

        return to_route('user.whatsapp.templates.show', $template)->with('status', 'Template diperbarui dan diajukan ulang. Persetujuan admin diperlukan kembali.');
    }

    public function asset(WhatsappTemplate $template): StreamedResponse
    {
        Gate::authorize('view', $template);
        abort_unless($template->asset_path && Storage::disk('local')->exists($template->asset_path), 404);

        return Storage::disk('local')->response($template->asset_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
