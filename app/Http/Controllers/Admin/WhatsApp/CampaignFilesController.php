<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappBroadcast;
use App\Services\WhatsApp\CampaignArchive;
use App\Services\WhatsApp\CampaignReportImport;
use Illuminate\Http\Request;

class CampaignFilesController extends Controller
{
    public function download(WhatsappBroadcast $broadcast, CampaignArchive $archive)
    {
        abort_unless(in_array($broadcast->status, ['approve', 'finish'], true), 409, 'Approve the campaign before downloading its recipients.');

        return response()->download($archive->create($broadcast), 'campaign-'.$broadcast->id.'.tar.gz', [
            'Content-Type' => 'application/gzip',
        ])->deleteFileAfterSend(true);
    }

    public function upload(Request $request, WhatsappBroadcast $broadcast, CampaignReportImport $import)
    {
        $request->validate(['report_csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $count = $import->import($broadcast, $request->file('report_csv')->getRealPath());

        return redirect()->route('admin.whatsapp.reports.show', $broadcast)
            ->with('status', "Report uploaded. {$count} recipient results updated for this campaign.");
    }
}
