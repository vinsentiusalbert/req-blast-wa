<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSender;
use App\Services\WhatsApp\RecipientParser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SenderController extends Controller
{
    public function index()
    {
        return view('admin.whatsapp.senders.index', ['senders' => WhatsappSender::orderBy('id')->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['phone_number' => ['required', 'string', 'max:30'], 'is_active' => ['required', 'boolean']]);
        try {
            $numbers = app(RecipientParser::class)->parse($data['phone_number']);
            if (count($numbers) !== 1) {
                throw new InvalidArgumentException('Masukkan satu nomor pengirim.');
            }
            $data['phone_number'] = $numbers[0];
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['phone_number' => $exception->getMessage()]);
        }
        validator($data, ['phone_number' => [Rule::unique('whatsapp_senders', 'phone_number')]])->validate();
        WhatsappSender::create($data);

        return to_route('admin.whatsapp.senders.index')->with('status', 'Nomor pengirim berhasil ditambahkan.');
    }

    public function update(Request $request, WhatsappSender $sender)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $sender->update($data);

        return to_route('admin.whatsapp.senders.index')->with('status', 'Status nomor pengirim diperbarui.');
    }
}
