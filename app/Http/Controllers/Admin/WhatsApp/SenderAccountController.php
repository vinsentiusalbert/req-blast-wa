<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\SaveSenderAccountRequest;
use App\Models\WhatsappSenderAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SenderAccountController extends Controller
{
    public function index(): View
    {
        $senders = WhatsappSenderAccount::query()->latest('id')->paginate(15);

        return view('admin.whatsapp.senders.index', compact('senders'));
    }

    public function create(): View
    {
        $sender = new WhatsappSenderAccount([
            'status' => 'inactive', 'min_delay_seconds' => 30, 'max_delay_seconds' => 60,
            'daily_limit' => 100, 'failure_cooldown_seconds' => 900, 'failure_threshold' => 3,
        ]);

        return view('admin.whatsapp.senders.form', compact('sender'));
    }

    public function store(SaveSenderAccountRequest $request): RedirectResponse
    {
        WhatsappSenderAccount::create($request->validated());

        return redirect()->route('admin.whatsapp.senders.index')->with('status', 'Sender account created.');
    }

    public function edit(WhatsappSenderAccount $sender): View
    {
        return view('admin.whatsapp.senders.form', compact('sender'));
    }

    public function update(SaveSenderAccountRequest $request, WhatsappSenderAccount $sender): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }
        // Operational counters and cooldowns are owned by the worker, not this form.
        $sender->update($data);

        return redirect()->route('admin.whatsapp.senders.index')->with('status', 'Sender account updated. Existing cooldowns remain in effect.');
    }
}
