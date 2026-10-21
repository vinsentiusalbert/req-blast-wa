<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;
use App\Services\WhatsApp\ReportExcel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WhatsAppReportController extends Controller
{
    public const STATUSES = ['pending' => 'Menunggu', 'sent' => 'Terkirim', 'failed' => 'Gagal'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(array_keys(WhatsappBroadcast::STATUS_LABELS))],
        ]);
        $query = WhatsappBroadcast::query()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($request->user()->isAdmin() && ! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', CarbonImmutable::parse($date, 'Asia/Jakarta')->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('created_at', '<', CarbonImmutable::parse($date, 'Asia/Jakarta')->addDay()->startOfDay()->utc()))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        $total = (clone $query)->count();
        $recipientTotal = (clone $query)->sum('recipient_count');
        $counts = WhatsappBroadcastRecipient::query()
            ->whereIn('whatsapp_broadcast_id', (clone $query)->select('id'))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $broadcasts = $query->with('user', 'template')->withCount([
            'approvedRecipients as sent_count' => fn ($q) => $q->where('status', 'sent'),
            'approvedRecipients as failed_count' => fn ($q) => $q->where('status', 'failed'),
        ])->latest('id')->paginate(15)->withQueryString();

        return view('reports.index', compact('filters', 'total', 'recipientTotal', 'counts', 'broadcasts') + $this->context($request) + [
            'users' => $request->user()->isAdmin() ? User::orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function show(Request $request, WhatsappBroadcast $broadcast)
    {
        $this->authorizeOwner($request, $broadcast);
        $filters = $this->recipientFilters($request);
        $counts = $broadcast->approvedRecipients()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $recipients = $this->recipients($broadcast, $filters)->orderBy('id')->paginate(25)->withQueryString();
        $broadcast->load('user', 'template');

        return view('reports.show', compact('broadcast', 'filters', 'counts', 'recipients') + $this->context($request));
    }

    public function export(Request $request, WhatsappBroadcast $broadcast)
    {
        $this->authorizeOwner($request, $broadcast);
        $query = $this->recipients($broadcast, $this->recipientFilters($request));

        $format = $request->validate(['format' => ['nullable', Rule::in(['csv', 'xlsx'])]])['format'] ?? 'csv';
        if ($format === 'xlsx') {
            $path = (new ReportExcel)->create($this->exportRows($query));

            return response()->download($path, 'report-whatsapp-'.$broadcast->id.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            foreach ($this->exportRows($query) as $cells) {
                $cells = array_map(function ($value) {
                    $value = (string) $value;

                    return preg_match('/^[\s]*[=+@-]|^[\t\r\n]/u', $value) ? "'".$value : $value;
                }, $cells);
                fputcsv($file, $cells, ',', '"', '');
            }
            fclose($file);
        }, 'report-whatsapp-'.$broadcast->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportRows($query): \Generator
    {
        yield ['Nomor WhatsApp', 'Status', 'Percobaan', 'Terakhir dicoba (WIB)', 'Terkirim (WIB)', 'Gagal (WIB)', 'Alasan gagal'];
        foreach ($query->lazyById(500) as $row) {
            yield [$row->msisdn, self::STATUSES[$row->status] ?? $row->status, $row->attempts,
                $row->last_attempt_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'), $row->sent_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                $row->status === 'failed' ? $row->failed_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '',
                $row->status === 'failed' ? $row->failure_reason : ''];
        }
    }

    private function authorizeOwner(Request $request, WhatsappBroadcast $broadcast): void
    {
        abort_unless($request->user()->isAdmin() || $broadcast->user_id === $request->user()->id, 403);
    }

    private function recipientFilters(Request $request): array
    {
        return $request->validate(['status' => ['nullable', Rule::in(array_keys(self::STATUSES))], 'msisdn' => ['nullable', 'regex:/^[0-9]+$/', 'max:15']]);
    }

    private function recipients(WhatsappBroadcast $broadcast, array $filters)
    {
        return $broadcast->approvedRecipients()
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['msisdn'] ?? null, fn ($q, $value) => $q->where('msisdn', 'like', '%'.$value.'%'));
    }

    private function context(Request $request): array
    {
        return ['prefix' => ($request->user()->isAdmin() ? 'admin' : 'user').'.whatsapp.reports', 'statuses' => self::STATUSES];
    }
}
