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
            $number = trim($entry);
            if ($number === '') {
                continue;
            }

            if (! preg_match('/^62[0-9]{6,13}$/D', $number)) {
                throw new InvalidArgumentException('Recipient number '.($index + 1).' must start with 62 and contain digits only (8–15 digits), for example 6281234567890.');
            }

            $recipients[$number] = $number;


        }

        if ($recipients === []) {
            throw new InvalidArgumentException('Enter at least one recipient number.');
        }

        return array_values($recipients);
    }
}
