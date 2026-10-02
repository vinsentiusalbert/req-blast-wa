<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(WhatsappBroadcast::STATUS_LABELS))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $broadcasts = WhatsappBroadcast::with(['user', 'template'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->latest('id')->paginate(10)->withQueryString();

        return view('admin.whatsapp.campaigns.index', compact('broadcasts', 'filters'));
    }

    public function show(WhatsappBroadcast $broadcast): View
    {
        $broadcast->load(['user', 'template', 'schedules']);
        $senders = WhatsappSender::where('is_active', true)->orderBy('phone_number')->get();

        return view('admin.whatsapp.campaigns.show', compact('broadcast', 'senders'));
    }

    public function update(Request $request, WhatsappBroadcast $broadcast): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(WhatsappBroadcast::STATUS_LABELS))]]);
        DB::transaction(function () use ($broadcast, $data) {
            $campaign = WhatsappBroadcast::lockForUpdate()->findOrFail($broadcast->id);
            if ($data['status'] === 'draft' && $campaign->schedules()->exists()) {
                throw ValidationException::withMessages(['status' => 'Campaign yang sudah memiliki jadwal tidak dapat dikembalikan ke draft.']);
            }
            $template = $campaign->template()->lockForUpdate()->firstOrFail();
            if (in_array($data['status'], ['accepted', 'processing'], true) && ! $template->isApproved()) {
                throw ValidationException::withMessages(['status' => 'Template harus disetujui sebelum campaign diterima atau diproses.']);
            }
            $campaign->status = $data['status'];
            $campaign->save();
        });

        return to_route('admin.whatsapp.campaigns.show', $broadcast)->with('status', 'Status campaign berhasil diperbarui.');
    }
}
