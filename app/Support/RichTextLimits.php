<?php

namespace App\Support;

final class RichTextLimits
{
    private const PLAIN_CHARS_PER_WORD = 24;

    private const HTML_OVERHEAD_FACTOR = 1.25;

    public const STORED_MAX_CHARS = 65535;

    public static function maxStoredCharsForWords(int $maxWords): int
    {
        $maxWords = max(1, $maxWords);
        $plain = (int) ceil($maxWords * self::PLAIN_CHARS_PER_WORD);
        $withHtml = (int) ceil($plain * self::HTML_OVERHEAD_FACTOR);

        return min(self::STORED_MAX_CHARS, max(2056, $withHtml));
    }
}
