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
                $this->fail('The CSV is empty.');
            }
            $headers = array_map(fn ($value) => trim((string) $value), $headers);
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if ($headers[0] !== 'msisdn' || count(array_unique($headers)) !== count($headers)) {
                $this->fail('The first column must be msisdn and column names must be unique.');
            }
            foreach ($headers as $header) {
                if ($header === '' || ! mb_check_encoding($header, 'UTF-8') || preg_match('/[{}\r\n]/', $header)) {
                    $this->fail('Column names are required, must use UTF-8, and must not contain braces.');
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
                    $this->fail("Record {$line}: the column count does not match the header.");
                }
                foreach ($values as $value) {
                    if (! mb_check_encoding($value, 'UTF-8')) {
                        $this->fail("Record {$line}: save the file as UTF-8 CSV.");
                    }
                }
                try {
                    $numbers = (new RecipientParser)->parse($values[0]);
                } catch (\InvalidArgumentException $e) {
                    $this->fail("Record {$line}: ".$e->getMessage());
                }
                if (count($numbers) !== 1 || preg_match('/[,;\r\n]/', $values[0])) {
                    $this->fail("Record {$line}: msisdn must contain a single number.");
                }
                $values[0] = $numbers[0];
                if (isset($seen[$values[0]])) {
                    $this->fail("Record {$line}: duplicate number. Use one record per number.");
                }
                $seen[$values[0]] = true;
                $rows[] = array_combine($headers, $values);

            }
            if (! $rows) {
                $this->fail('The CSV must contain at least one recipient record.');
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
                    $this->fail('Variable {{'.$key.'}} has no value in record '.($index + 1).'. Column names must match the template variables.');
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
