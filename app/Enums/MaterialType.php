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
}
