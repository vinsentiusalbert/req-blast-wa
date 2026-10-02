<?php

namespace App\Services\WhatsApp;

use InvalidArgumentException;

class RecipientParser
{
    /** @return list<string> */
    public function parse(string $input): array
    {
        $entries = preg_split('/[\r\n,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY);
        $recipients = [];

        foreach ($entries as $index => $entry) {
            $number = preg_replace('/[\s()\-]/', '', trim($entry));
            if ($number === '') {
                continue;
            }

            if (str_starts_with($number, '08')) {
                $number = '62'.substr($number, 1);
            } elseif (str_starts_with($number, '+')) {
                $number = substr($number, 1);
            }

            if (! preg_match('/^[1-9][0-9]{7,14}$/D', $number)) {
                throw new InvalidArgumentException('Nomor penerima ke-'.($index + 1).' tidak valid. Gunakan 08…, 62…, atau kode negara dengan tanda +.');
            }

            $recipients[$number] = $number;

        }

        if ($recipients === []) {
            throw new InvalidArgumentException('Masukkan minimal satu nomor penerima.');
        }

        return array_values($recipients);
    }
}
