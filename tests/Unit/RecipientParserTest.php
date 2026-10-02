<?php

namespace Tests\Unit;

use App\Services\WhatsApp\RecipientParser;
use PHPUnit\Framework\TestCase;

class RecipientParserTest extends TestCase
{
    public function test_formats_and_duplicates_are_normalized(): void
    {
        $result = (new RecipientParser)->parse("0812 3456 7890\n+62 (812) 3456-7890;6281234567891,+14155552671");
        $this->assertSame(['6281234567890', '6281234567891', '14155552671'], $result);
    }

    public function test_more_than_one_thousand_unique_recipients_are_accepted(): void
    {
        $numbers = array_map(fn ($i) => '628'.str_pad((string) $i, 9, '0', STR_PAD_LEFT), range(1, 1001));
        $this->assertSame($numbers, (new RecipientParser)->parse(implode("\n", $numbers)));
    }

    public function test_large_duplicate_lists_are_deduplicated(): void
    {
        $this->assertCount(1, (new RecipientParser)->parse(str_repeat("081234567890\n", 1001)));
    }
}
