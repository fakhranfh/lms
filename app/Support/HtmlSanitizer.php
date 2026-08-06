<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;

class HtmlSanitizer
{
    public static function forum(string $html): string
    {
        return Purifier::clean($html, 'forum');
    }
}
