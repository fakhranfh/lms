<?php

namespace App\Services;

use App\Support\RichTextAttachments;

/**
 * Deletes R2 files referenced inside rich text editor HTML content. Feature
 * agnostic: any Livewire component that stores rich text (forum posts,
 * session notes, course descriptions, ...) can inject this service to keep
 * uploaded attachments in sync with what's actually still referenced.
 */
class RichTextAttachmentCleanupService
{
    public function __construct(private R2StorageService $r2StorageService) {}

    /**
     * Deletes a single URL from R2, but only if it actually points to a file
     * managed by this application's R2 bucket. Safe to call with arbitrary,
     * user-influenced URLs.
     */
    public function deleteUrl(string $url): void
    {
        if (! $this->r2StorageService->isManagedUrl($url)) {
            return;
        }

        $this->r2StorageService->delete($url);
    }

    public function deleteFromHtml(?string $html): void
    {
        foreach (RichTextAttachments::extractUrls($html) as $url) {
            $this->deleteUrl($url);
        }
    }

    public function deleteRemoved(?string $oldHtml, ?string $newHtml): void
    {
        $removed = array_diff(
            RichTextAttachments::extractUrls($oldHtml),
            RichTextAttachments::extractUrls($newHtml)
        );

        foreach ($removed as $url) {
            $this->deleteUrl($url);
        }
    }
}
