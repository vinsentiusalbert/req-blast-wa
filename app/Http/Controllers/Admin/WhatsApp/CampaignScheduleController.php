<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappSender;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignScheduleController extends Controller
{
    public function update(Request $request, WhatsappBroadcast $broadcast)
    {
        $data = $request->validate([
            'schedules' => ['required', 'array', 'min:1', 'max:100'],
            'schedules.*.send_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Bangkok')->toDateString()],
            'schedules.*.message_count' => ['required', 'integer', 'min:1'],
            'schedules.*.whatsapp_sender_id' => ['prohibited'],
        ]);
        DB::transaction(function () use ($broadcast, $data) {
            $campaign = WhatsappBroadcast::lockForUpdate()->findOrFail($broadcast->id);
            if ($campaign->status !== 'accepted') {
                throw ValidationException::withMessages(['schedules' => 'Campaign harus berstatus Diterima untuk mengatur jadwal.']);
            }
            if (! $campaign->template()->lockForUpdate()->firstOrFail()->isApproved()) {
                throw ValidationException::withMessages(['schedules' => 'Template campaign harus tetap disetujui.']);
            }
            if ($campaign->schedules()->where('status', '!=', 'pending')->exists() || $campaign->recipientEntries()->where('delivery_status', '!=', 'pending')->exists()) {
                throw ValidationException::withMessages(['schedules' => 'Jadwal yang sudah mulai dijalankan tidak dapat diganti.']);
            }
            if ($campaign->sending_starts_at && $campaign->sending_ends_at) {
                foreach ($data['schedules'] as $index => $row) {
                    $day = CarbonImmutable::parse($row['send_date'], WhatsappBroadcast::SENDING_TIMEZONE)->startOfDay();
                    if ($day->greaterThanOrEqualTo($campaign->sending_ends_at) || $day->addDay()->lessThanOrEqualTo($campaign->sending_starts_at)) {
                        throw ValidationException::withMessages(['schedules.'.$index.'.send_date' => 'Tanggal partisi harus berada dalam rentang pengiriman broadcast (UTC+7).']);
                    }
                }
            }
            $entries = $campaign->recipientEntries()->orderBy('id')->lockForUpdate()->get();
            if (array_sum(array_column($data['schedules'], 'message_count')) !== $entries->count()) {
                throw ValidationException::withMessages(['schedules' => 'Total pesan seluruh partisi harus sama dengan jumlah penerima ('.$entries->count().').']);
            }
            if (! WhatsappSender::where('is_active', true)->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['schedules' => 'Aktifkan minimal satu nomor pengirim terlebih dahulu.']);
            }
            $campaign->schedules()->delete();
            $rows = $data['schedules'];
            usort($rows, fn ($a, $b) => strcmp($a['send_date'], $b['send_date']));
            $offset = 0;
            foreach ($rows as $row) {
                $schedule = $campaign->schedules()->create($row + ['timezone' => 'Asia/Bangkok']);
                $ids = $entries->slice($offset, (int) $row['message_count'])->pluck('id');
                $campaign->recipientEntries()->whereIn('id', $ids)->update(['whatsapp_campaign_schedule_id' => $schedule->id]);
                $offset += (int) $row['message_count'];
            }
        });

        return to_route('admin.whatsapp.campaigns.show', $broadcast)->with('status', 'Partisi jadwal berhasil disimpan. Semua penerima telah mendapat jadwal.');
    }
}
