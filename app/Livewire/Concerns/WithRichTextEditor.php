<?php

namespace App\Livewire\Concerns;

use App\Services\R2StorageService;
use App\Services\RichTextAttachmentCleanupService;
use Livewire\WithFileUploads;

trait WithRichTextEditor
{
    use WithFileUploads;

    public $pendingRichTextFile = null;

    public function insertRichTextFile(R2StorageService $r2StorageService): string
    {
        $this->validate([
            'pendingRichTextFile' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,zip|max:10240',
        ]);

        $url = $r2StorageService->uploadPublicFile($this->pendingRichTextFile, 'temp/forum-attachments');

        $this->pendingRichTextFile = null;

        return $url;
    }

    public function deleteRichTextAttachment(string $url, RichTextAttachmentCleanupService $richTextAttachmentCleanupService): void
    {
        $richTextAttachmentCleanupService->deleteUrl($url);
    }

    /**
     * Moves any temp-staged attachments referenced in the given rich text
     * HTML to their permanent folder. Call this right before persisting
     * content so files stay staged (and easy to discard) until the form
     * is actually submitted.
     */
    protected function promoteRichTextAttachments(?string $html): string
    {
        return app(RichTextAttachmentCleanupService::class)->promoteTempAttachments($html);
    }
}
