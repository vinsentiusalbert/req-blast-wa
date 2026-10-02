<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class RecipientFileParser
{
    public function read(UploadedFile $file): string
    {
        return implode("\n", array_column($this->readRows($file), 'phone_number'));
    }

    public function readRows(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            throw new InvalidArgumentException('File harus berupa teks UTF-8.');
        }
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        $firstLine = fgets($stream);
        $delimiter = substr_count($firstLine ?: '', ';') > substr_count($firstLine ?: '', ',') ? ';' : ',';
        rewind($stream);
        $numbers = [];
        $column = null;
        $first = true;
        $variables = [];
        try {
            while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                if (count($row) === 1 && trim($row[0] ?? '') === '') {
                    continue;
                }
                if ($first) {
                    $first = false;
                    foreach ($row as $index => $heading) {
                        $heading = strtolower(trim($heading ?? ''));
                        if (preg_match('/^var[1-9][0-9]*$/D', $heading)) {
                            if (in_array($heading, $variables, true)) {
                                throw new InvalidArgumentException('Header variabel CSV tidak boleh duplikat.');
                            }
                            $variables[$index] = $heading;
                        }
                        if (in_array(strtolower(trim($heading ?? '')), ['msisdn', 'phone', 'phone_number', 'nomor', 'nomor_whatsapp', 'whatsapp', 'number', 'no_hp'], true)) {
                            $column = $index;
                        }
                    }
                    if ($column !== null) {
                        continue;
                    }
                }
                if ($column === null && count($row) !== 1) {
                    throw new InvalidArgumentException('CSV dengan beberapa kolom harus memiliki header msisdn, nomor, phone, atau nomor_whatsapp.');
                }
                $number = trim($row[$column ?? 0] ?? '');
                if ($number === '' || strpbrk($number, "\r\n,;") !== false) {
                    throw new InvalidArgumentException('Setiap baris CSV harus berisi satu nomor WhatsApp.');
                }
                $normalized = (new RecipientParser)->parse($number)[0];
                $values = [];
                foreach ($variables as $index => $heading) {
                    $values[$heading] = trim($row[$index] ?? '');
                }
                // Keep the first row when multiple formats refer to the same number.
                $numbers[$normalized] ??= ['phone_number' => $normalized, 'variables' => $values];
            }
        } finally {
            fclose($stream);
        }

        if ($numbers === []) {
            throw new InvalidArgumentException('Masukkan minimal satu nomor penerima.');
        }

        return array_values($numbers);
    }
}
