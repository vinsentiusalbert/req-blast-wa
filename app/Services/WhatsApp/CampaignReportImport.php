<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignReportImport
{
    public function import(WhatsappBroadcast $broadcast, string $path): int
    {
        $stream = fopen($path, 'rb');
        $rows = [];
        try {
            $headers = fgetcsv($stream, 0, ',', '"', '');
            if (! $headers) {
                $this->fail('The report CSV is empty.');
            }
            $headers = array_map(fn ($value) => trim((string) $value), $headers);
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if ($headers !== ['msisdn', 'status', 'failure_reason']) {
                $this->fail('Use the columns msisdn,status,failure_reason in that order.');
            }
            $line = 1;
            while (($cells = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $line++;
                if ($cells === [null]) {
                    continue;
                }
                if (count($cells) !== 3 || ! mb_check_encoding(implode('', $cells), 'UTF-8')) {
                    $this->fail("Record {$line}: use three columns and UTF-8 encoding.");
                }
                [$number, $status, $reason] = array_map('trim', $cells);
                $status = strtolower($status);
                if (! preg_match('/\A62[0-9]{6,13}\z/', $number) || ! in_array($status, ['pending', 'sent', 'failed'], true) || mb_strlen($reason) > 2000) {
                    $this->fail("Record {$line}: invalid MSISDN, status, or failure reason (maximum 2,000 characters).");
                }
                if (isset($rows[$number])) {
                    $this->fail("Record {$line}: duplicate MSISDN.");
                }
                $rows[$number] = ['status' => $status, 'failure_reason' => $status === 'failed' ? ($reason ?: null) : null];
            }
        } finally {
            fclose($stream);
        }
        if (! $rows) {
            $this->fail('The report must contain at least one recipient.');
        }

        return DB::transaction(function () use ($broadcast, $rows) {
            $current = WhatsappBroadcast::lockForUpdate()->findOrFail($broadcast->id);
            if (! in_array($current->status, ['approve', 'finish'], true)) {
                $this->fail('Reports can only be uploaded for approved or finished campaigns.');
            }
            // Validate every number before updating; never match recipients across campaigns.
            foreach (array_chunk(array_keys($rows), 500) as $numbers) {
                if ($current->approvedRecipients()->whereIn('msisdn', $numbers)->count() !== count($numbers)) {
                    $this->fail('The report contains an MSISDN that does not belong to this campaign. No results were changed.');
                }
            }
            foreach ($rows as $number => $values) {
                $recipient = $current->approvedRecipients()->where('msisdn', (string) $number)->lockForUpdate()->firstOrFail();
                // A status-only upload must not invent a delivery time or attempt count.
                if ($values['status'] !== $recipient->status) {
                    $values += ['sent_at' => null, 'failed_at' => null];
                }
                if ($values['status'] !== 'failed') {
                    $values['failed_at'] = null;
                }
                if ($values['status'] !== 'sent') {
                    $values['sent_at'] = null;
                }
                $recipient->forceFill($values)->save();
            }

            return count($rows);
        }, 3);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['report_csv' => $message]);
    }
}
