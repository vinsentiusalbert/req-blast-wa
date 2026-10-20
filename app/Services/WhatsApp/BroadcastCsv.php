<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappTemplate;
use Illuminate\Validation\ValidationException;

class BroadcastCsv
{
    public function parse(string $path): array
    {
        $stream = fopen($path, 'rb');
        try {
            $headers = fgetcsv($stream, 0, ',', '"', '');
            if (! $headers) {
                $this->fail('CSV kosong.');
            }
            $headers = array_map(fn ($value) => trim((string) $value), $headers);
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if ($headers[0] !== 'msisdn' || count(array_unique($headers)) !== count($headers)) {
                $this->fail('Kolom pertama wajib msisdn dan nama kolom tidak boleh duplikat.');
            }
            foreach ($headers as $header) {
                if ($header === '' || ! mb_check_encoding($header, 'UTF-8') || preg_match('/[{}\r\n]/', $header)) {
                    $this->fail('Nama kolom wajib diisi, menggunakan UTF-8, dan tidak mengandung kurung kurawal.');
                }
            }
            $rows = [];
            $seen = [];
            $line = 1;
            while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    $this->fail("Record {$line}: jumlah kolom berbeda dari header.");
                }
                foreach ($values as $value) {
                    if (! mb_check_encoding($value, 'UTF-8')) {
                        $this->fail("Record {$line}: simpan file sebagai CSV UTF-8.");
                    }
                }
                try {
                    $numbers = (new RecipientParser)->parse($values[0]);
                } catch (\InvalidArgumentException $e) {
                    $this->fail("Record {$line}: ".$e->getMessage());
                }
                if (count($numbers) !== 1 || preg_match('/[,;\r\n]/', $values[0])) {
                    $this->fail("Record {$line}: msisdn harus berisi satu nomor.");
                }
                $values[0] = $numbers[0];
                if (isset($seen[$values[0]])) {
                    $this->fail("Record {$line}: nomor duplikat. Gunakan satu record per nomor.");
                }
                $seen[$values[0]] = true;
                $rows[] = array_combine($headers, $values);

            }
            if (! $rows) {
                $this->fail('CSV harus memiliki minimal satu record penerima.');
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    public function validateVariables(WhatsappTemplate $template, array $rows): void
    {
        preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/u', ($template->header_type === 'TEXT' ? $template->header_text : '')."\n".$template->body, $matches);
        foreach ($matches[1] as $key) {
            $key = trim($key);
            foreach ($rows as $index => $row) {
                if (! array_key_exists($key, $row) || trim($row[$key]) === '') {
                    $this->fail('Variabel {{'.$key.'}} belum diisi pada record '.($index + 1).'. Nama kolom harus sama dengan variabel template.');
                }
            }
        }
    }

    public function render(?string $text, array $row): string
    {
        return preg_replace_callback('/\{\{\s*([^{}]+?)\s*\}\}/u', fn ($match) => $row[trim($match[1])] ?? $match[0], $text ?? '');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['recipients_csv' => $message]);
    }
}
