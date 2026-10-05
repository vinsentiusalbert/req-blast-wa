<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappBroadcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecipientExportController extends Controller
{
    public function __invoke(Request $request, WhatsappBroadcast $broadcast): StreamedResponse
    {
        if ($request->user()->role !== User::ROLE_ADMIN) {
            Gate::authorize('view', $broadcast);
        }

        return response()->streamDownload(function () use ($broadcast) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['msisdn'], ',', '"', '');
            if ($broadcast->recipientEntries()->exists()) {
                $broadcast->recipientEntries()->select(['id', 'phone_number'])->chunkById(500, function ($entries) use ($stream) {
                    foreach ($entries as $entry) {
                        fputcsv($stream, [$entry->phone_number], ',', '"', '');
                    }
                });
            } else {
                foreach ($broadcast->recipients ?? [] as $number) {
                    fputcsv($stream, [$number], ',', '"', '');
                }
            }
            fclose($stream);
        }, 'penerima-broadcast-'.$broadcast->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
