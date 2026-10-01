<?php

namespace Tests\Unit;

use App\Services\WhatsApp\RecipientParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RecipientParserTest extends TestCase
{
    public function test_formats_and_duplicates_are_normalized(): void
    {
        $result = (new RecipientParser)->parse("0812 3456 7890\n+62 (812) 3456-7890;6281234567891,+14155552671");
        $this->assertSame(['6281234567890', '6281234567891', '14155552671'], $result);
    }

    public function test_unique_recipient_limit_is_enforced(): void
    {
        $numbers = array_map(fn ($i) => '628'.str_pad((string) $i, 9, '0', STR_PAD_LEFT), range(1, 1001));
        $this->expectException(InvalidArgumentException::class);
        (new RecipientParser)->parse(implode("\n", $numbers));
    }

    public function test_duplicates_do_not_consume_the_unique_limit(): void
    {
        $this->assertCount(1, (new RecipientParser)->parse(str_repeat("081234567890\n", 1001)));
    }
}
