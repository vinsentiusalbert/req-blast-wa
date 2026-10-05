<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;
use App\Queries\WhatsApp\DeliveryReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryReportController extends Controller
{
    public function export(Request $request, WhatsappBroadcast $broadcast, DeliveryReport $report): StreamedResponse
    {
        if ($request->user()->role !== User::ROLE_ADMIN) {
            Gate::authorize('view', $broadcast);
        }
        $query = $report->query($broadcast, $report->filters($request));

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Nomor WhatsApp', 'Status', 'Waktu kirim (UTC+7)', 'Keterangan failed'], ',', '"', '');
            $query->chunkById(500, function ($entries) use ($stream) {
                foreach ($entries as $entry) {
                    $error = $entry->failureDescription();
                    // Keep provider error text from becoming a spreadsheet formula.
                    if (preg_match('/^[\s]*[=+@-]|^[\t\r\n]/u', $error)) {
                        $error = "'".$error;
                    }
                    fputcsv($stream, [
                        $entry->phone_number,
                        WhatsappBroadcastRecipient::REPORT_LABELS[$entry->reportStatus()],
                        $entry->sent_at?->timezone('Asia/Bangkok')->format('Y-m-d H:i:s') ?? '',
                        $error,
                    ], ',', '"', '');
                }
            });
            fclose($stream);
        }, 'dlr-broadcast-'.$broadcast->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
