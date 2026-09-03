<?php

namespace Tests\Unit\Support\Telegram;

use App\Support\Telegram\TelegramMarkdown;
use PHPUnit\Framework\TestCase;

class TelegramMarkdownTest extends TestCase
{
    public function test_it_escapes_markdown_v2_special_characters(): void
    {
        $this->assertSame(
            'Milk \\(2%\\) \\- test\\.',
            TelegramMarkdown::escape('Milk (2%) - test.')
        );
    }
}
