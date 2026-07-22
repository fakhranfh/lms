<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StorageQuotaAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $threshold,
        public string $usedFormatted,
        public string $quotaFormatted,
        public float $percentage,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->threshold) {
            100 => 'Storage quota full — uploads blocked',
            90 => 'Storage at 90% — uploads may fail soon',
            default => 'Storage at 80% — consider an archival strategy',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.storage-quota-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
