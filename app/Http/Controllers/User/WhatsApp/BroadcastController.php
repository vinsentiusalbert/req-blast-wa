<?php

namespace App\Http\Controllers\User\WhatsApp;

use App\Actions\WhatsApp\SaveBroadcastDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\SaveBroadcastRequest;
use App\Models\WhatsappBroadcast;
use App\Queries\WhatsApp\DeliveryReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BroadcastController extends Controller
{
    public function index(Request $request): View
    {
        $broadcasts = $request->user()->whatsappBroadcasts()->with('template')->latest('id')->paginate(10);
        $hasApprovedTemplates = $request->user()->whatsappTemplates()->approved()->exists();

        return view('user.whatsapp.broadcasts.index', compact('broadcasts', 'hasApprovedTemplates'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->whatsappTemplates()->approved()->exists()) {
            return to_route('user.whatsapp.broadcasts.index')->with('status', 'Belum ada template yang disetujui admin. Broadcast dapat dibuat setelah template Anda disetujui.');
        }

        $data = $request->validate(['template' => ['nullable', 'integer']]);
        $selectedTemplate = isset($data['template'])
            ? $request->user()->whatsappTemplates()->approved()->findOrFail($data['template'])->id
            : null;

        return $this->form($request, new WhatsappBroadcast(['whatsapp_template_id' => $selectedTemplate]));
    }

    public function store(SaveBroadcastRequest $request, SaveBroadcastDraft $action): RedirectResponse
    {
        $broadcast = $action->handle($request->user(), $request->validated());

        return to_route('user.whatsapp.broadcasts.show', $broadcast)->with('status', 'Campaign berhasil dibuat dan tidak dapat diedit. Pesan belum dikirim.');
    }

    public function show(Request $request, WhatsappBroadcast $broadcast): View
    {
        Gate::authorize('view', $broadcast);
        $broadcast->load('template');

        $deliveryReport = app(DeliveryReport::class)->forBroadcast($request, $broadcast);

        return view('user.whatsapp.broadcasts.show', compact('broadcast') + $deliveryReport);
    }

    public function edit(Request $request, WhatsappBroadcast $broadcast): View
    {
        Gate::authorize('update', $broadcast);

        return $this->form($request, $broadcast);
    }

    public function update(SaveBroadcastRequest $request, WhatsappBroadcast $broadcast, SaveBroadcastDraft $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated(), $broadcast);

        return to_route('user.whatsapp.broadcasts.show', $broadcast)->with('status', 'Draft broadcast berhasil diperbarui.');
    }

    private function form(Request $request, WhatsappBroadcast $broadcast): View
    {
        $templates = $request->user()->whatsappTemplates()->approved()->latest('id')->get();
        $previews = $templates->mapWithKeys(fn ($template) => [$template->id => [
            'header_type' => $template->header_type,
            'header_text' => $template->header_text,
            'image' => $template->asset_path ? route('user.whatsapp.templates.asset', $template) : null,
            'body' => $template->body,
        ]]);

        return view('user.whatsapp.broadcasts.form', compact('broadcast', 'templates', 'previews'));
    }
}
