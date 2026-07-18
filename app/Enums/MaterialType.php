<?php

namespace App\Enums;

enum MaterialType: string
{
    case Video = 'Video';
    case PDF = 'PDF';
    case Document = 'Document';
    case Audio = 'Audio';
    case Presentation = 'Presentation';
    case Image = 'Image';
    case Interactive = 'Interactive';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::PDF => 'PDF',
            self::Document => 'Document',
            self::Audio => 'Audio',
            self::Presentation => 'Presentation',
            self::Image => 'Image',
            self::Interactive => 'Interactive',
        };
    }

    public function maxSize(): int
    {
        return match ($this) {
            self::Video => 500 * 1024 * 1024,        // 500 MB
            self::PDF => 50 * 1024 * 1024,           // 50 MB
            self::Document => 25 * 1024 * 1024,      // 25 MB
            self::Audio => 100 * 1024 * 1024,        // 100 MB
            self::Presentation => 50 * 1024 * 1024,  // 50 MB
            self::Image => 25 * 1024 * 1024,         // 25 MB
            self::Interactive => 100 * 1024 * 1024,  // 100 MB
        };
    }

    /**
     * @return array<int, string>
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Video => ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'],
            self::PDF => ['pdf'],
            self::Document => ['doc', 'docx', 'txt', 'rtf', 'odt'],
            self::Audio => ['mp3', 'wav', 'ogg', 'm4a', 'flac'],
            self::Presentation => ['ppt', 'pptx', 'odp'],
            self::Image => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            self::Interactive => ['html', 'htm', 'json'],
        };
    }

    /**
     * Get magic bytes (file signatures) for content validation
     * Returns hex strings that should appear at the start of the file
     *
     * @return array<int, string>
     */
    public function magicBytes(): array
    {
        return match ($this) {
            self::PDF => ['25504446'], // %PDF
            self::Image => [
                'ffd8ff', // JPEG
                '89504e47', // PNG
                '474946', // GIF
                '52494646', // RIFF (WebP, WAV)
            ],
            self::Audio => [
                '494433', // ID3 (MP3)
                'fffb', // MP3 (MPEG)
                'fffa', // MP3 (MPEG)
                '52494646', // RIFF (WAV)
                '4f6767', // OggS (OGG)
                '664c6143', // fLaC (FLAC)
            ],
            self::Video => [
                '66747970', // ftyp (MP4, MOV)
                '1a45dfa3', // WebM/Matroska
                '52494646', // RIFF (AVI)
            ],
            self::Document => [
                '504b0304', // ZIP (DOCX, ODT)
                'd0cf11e0', // OLE (DOC, legacy Office)
            ],
            self::Presentation => [
                '504b0304', // ZIP (PPTX, ODP)
                'd0cf11e0', // OLE (PPT)
            ],
            self::Interactive => [], // HTML/JSON are text-based, no strict magic bytes check
        };
    }

    /**
     * Get allowed MIME types for this material type (Layer 3 validation)
     *
     * @return array<int, string>
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::PDF => ['application/pdf'],
            self::Image => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/svg',
            ],
            self::Audio => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/mp4',
                'audio/flac',
                'audio/x-flac',
                'audio/x-wav',
            ],
            self::Video => [
                'video/mp4',
                'video/webm',
                'video/ogg',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-matroska',
                'video/3gpp',
            ],
            self::Document => [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'text/rtf',
                'application/vnd.oasis.opendocument.text',
            ],
            self::Presentation => [
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.oasis.opendocument.presentation',
            ],
            self::Interactive => [
                'text/html',
                'application/json',
                'text/plain',
            ],
        };
    }
}
