<?php

namespace App\Support\Telegram;

class TelegramMarkdown
{
    private const array ESCAPES = [
        '\\' => '\\\\',
        '_' => '\\_',
        '*' => '\\*',
        '[' => '\\[',
        ']' => '\\]',
        '(' => '\\(',
        ')' => '\\)',
        '~' => '\\~',
        '`' => '\\`',
        '>' => '\\>',
        '#' => '\\#',
        '+' => '\\+',
        '-' => '\\-',
        '=' => '\\=',
        '|' => '\\|',
        '{' => '\\{',
        '}' => '\\}',
        '.' => '\\.',
        '!' => '\\!',
    ];

    public static function escape(string $value): string
    {
        return strtr($value, self::ESCAPES);
    }
}
