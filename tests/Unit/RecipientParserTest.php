<?php

namespace Tests\Unit;

use App\Services\WhatsApp\RecipientParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RecipientParserTest extends TestCase
{
    public function test_valid_numbers_are_deduplicated(): void
    {
        $result = (new RecipientParser)->parse("6281234567890\n6281234567890;6281234567891");
        $this->assertSame(['6281234567890', '6281234567891'], $result);
    }

    public function test_more_than_one_thousand_recipients_are_accepted(): void
    {
        $numbers = array_map(fn ($i) => '628'.str_pad((string) $i, 9, '0', STR_PAD_LEFT), range(1, 1001));
        $this->assertCount(1001, (new RecipientParser)->parse(implode("\n", $numbers)));
    }

    public function test_duplicates_do_not_consume_the_unique_limit(): void
    {
        $this->assertCount(1, (new RecipientParser)->parse(str_repeat("6281234567890\n", 1001)));
    }

    public function test_local_prefix_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RecipientParser)->parse('081234567890');
    }
}
