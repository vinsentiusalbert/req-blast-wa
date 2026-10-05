<?php

namespace App\Queries\WhatsApp;

use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryReport
{
    public function filters(Request $request): array
    {
        $filters = $request->validate([
            'dlr_status' => ['nullable', Rule::in(array_keys(WhatsappBroadcastRecipient::REPORT_LABELS))],
            'dlr_search' => ['nullable', 'string', 'max:30', 'regex:/^[+()\s.\-0-9]*[0-9][+()\s.\-0-9]*$/'],
            'dlr_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'dlr_status' => $filters['dlr_status'] ?? '',
            'dlr_search' => trim($filters['dlr_search'] ?? ''),
        ];
    }

    public function query(WhatsappBroadcast $broadcast, array $filters): Builder
    {
        $query = $broadcast->recipientEntries()->getQuery();
        $status = $filters['dlr_status'];
        $successStatuses = WhatsappBroadcastRecipient::SUCCESS_STATUSES;
        if ($status === 'success') {
            $query->whereIn('delivery_status', $successStatuses);
        } elseif ($status === 'pending') {
            $query->whereNotIn('delivery_status', [...$successStatuses, 'sent', 'failed']);
        } elseif ($status !== '') {
            $query->where('delivery_status', $status);
        }

        $number = preg_replace('/\D/', '', $filters['dlr_search']);
        if (str_starts_with($number, '08')) {
            $number = '628'.substr($number, 2);
        }
        if ($number !== '') {
            $query->where('phone_number', 'like', '%'.$number.'%');
        }

        return $query;
    }

    public function forBroadcast(Request $request, WhatsappBroadcast $broadcast): array
    {
        $dlrFilters = $this->filters($request);
        $dlrRows = $this->query($broadcast, $dlrFilters)->orderBy('id')
            ->paginate(25, ['*'], 'dlr_page')->appends(array_filter($dlrFilters, fn ($value) => $value !== ''))
            ->fragment('delivery-report');
        $dlrCounts = array_fill_keys(array_keys(WhatsappBroadcastRecipient::REPORT_LABELS), 0);
        $counts = $broadcast->recipientEntries()->select('delivery_status')->selectRaw('COUNT(*) AS total')
            ->groupBy('delivery_status')->get();
        foreach ($counts as $row) {
            $dlrCounts[$row->reportStatus()] += (int) $row->total;
        }

        return compact('dlrRows', 'dlrCounts', 'dlrFilters');
    }
}
