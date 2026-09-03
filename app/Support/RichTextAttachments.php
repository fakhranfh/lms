<?php

namespace App\Support;

use DOMDocument;

class RichTextAttachments
{
    /**
     * Extracts every image and link URL (img[src], a[href]) referenced inside
     * a stored HTML string, regardless of the feature that produced it.
     *
     * @return array<int, string>
     */
    public static function extractUrls(?string $html): array
    {
        if (! $html) {
            return [];
        }

        $urls = [];

        libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        foreach ($document->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src');

            if ($src !== '') {
                $urls[] = $src;
            }
        }

        foreach ($document->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');

            if ($href !== '' && $href !== '#') {
                $urls[] = $href;
            }

            // Video chips (see rich-text-editor.js buildFileChip) keep their
            // real URL here instead of href, which is left as "#" so the
            // browser doesn't navigate away when the chip is clicked.
            $videoPreviewUrl = $anchor->getAttribute('data-video-preview');

            if ($videoPreviewUrl !== '') {
                $urls[] = $videoPreviewUrl;
            }
        }

        return array_values(array_unique($urls));
    }
}
