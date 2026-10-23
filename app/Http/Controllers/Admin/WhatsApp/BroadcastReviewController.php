<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Actions\WhatsApp\ChargeBroadcast;
use App\Actions\WhatsApp\StoreApprovedRecipients;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Services\WhatsApp\BroadcastCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BroadcastReviewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(WhatsappBroadcast::STATUS_LABELS))],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $status = $filters['status'] ?? '';
        $broadcasts = WhatsappBroadcast::with(['user', 'template'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest('id')->paginate(15)->withQueryString();

        $users = User::whereHas('whatsappBroadcasts')->orderBy('name')->get(['id', 'name', 'username']);

        return view('admin.whatsapp.broadcasts.index', compact('broadcasts', 'status', 'filters', 'users'));
    }

    public function show(WhatsappBroadcast $broadcast, BroadcastCsv $csv)
    {
        $broadcast->load(['user', 'template']);
        $previewTemplate = clone $broadcast->template;
        $first = $broadcast->recipient_records[0] ?? [];
        $previewTemplate->body = $csv->render($previewTemplate->body, $first);
        $previewTemplate->header_text = $csv->render($previewTemplate->header_text, $first);

        $records = $broadcast->recipient_records ?: array_map(fn ($number) => ['msisdn' => $number], $broadcast->recipients);
        $variableColumns = array_values(array_filter(array_keys($records[0] ?? []), fn ($key) => $key !== 'msisdn'));

        return view('admin.whatsapp.broadcasts.show', compact('broadcast', 'previewTemplate', 'records', 'variableColumns'));
    }

    public function update(Request $request, WhatsappBroadcast $broadcast, StoreApprovedRecipients $storeRecipients, ChargeBroadcast $charge)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approve', 'finish', 'reject'])],
            'revision' => ['required', 'integer', 'min:1'],
            'rejection_reason' => ['exclude_unless:status,reject', 'required', 'string', 'max:2000'],
        ], [
            'rejection_reason.required' => 'A rejection reason is required.',
            'rejection_reason.max' => 'The rejection reason may not exceed 2,000 characters.',
        ]);
        DB::transaction(function () use ($request, $broadcast, $data, $storeRecipients, $charge) {
            $current = WhatsappBroadcast::lockForUpdate()->findOrFail($broadcast->id);
            $expected = $data['status'] === 'finish' ? 'approve' : 'pending';
            if ($current->status !== $expected || (int) $current->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['status' => 'The broadcast has changed. Reload the page before continuing.']);
            }
            if ($data['status'] === 'approve' && ! $current->template->isApproved()) {
                throw ValidationException::withMessages(['status' => 'The template must be approved before the broadcast can be approved.']);
            }
            if ($data['status'] === 'approve') {
                $charge->handle($current, $request->user());
                $storeRecipients->handle($current);
            }
            $prefix = match ($data['status']) {
                'approve' => 'approved',
                'reject' => 'rejected',
                'finish' => 'finished',
            };
            $current->forceFill([
                'status' => $data['status'],
                'rejection_reason' => $data['status'] === 'reject' ? $data['rejection_reason'] : null,
                $prefix.'_at' => now(),
                $prefix.'_by' => $request->user()->id,
            ])->save();
        }, 3);

        return back()->with('status', 'The broadcast status was changed to '.WhatsappBroadcast::STATUS_LABELS[$data['status']].'.');
    }
}
