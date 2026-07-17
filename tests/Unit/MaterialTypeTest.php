<?php

use App\Enums\MaterialType;

describe('MaterialType Enum', function () {
    test('all material types have valid labels', function () {
        foreach (MaterialType::cases() as $type) {
            expect($type->label())->toBeString()->not->toBeEmpty();
        }
    });

    test('video has correct max size', function () {
        expect(MaterialType::Video->maxSize())->toBe(500 * 1024 * 1024);
    });

    test('pdf has correct max size', function () {
        expect(MaterialType::PDF->maxSize())->toBe(50 * 1024 * 1024);
    });

    test('document has correct max size', function () {
        expect(MaterialType::Document->maxSize())->toBe(25 * 1024 * 1024);
    });

    test('audio has correct max size', function () {
        expect(MaterialType::Audio->maxSize())->toBe(100 * 1024 * 1024);
    });

    test('presentation has correct max size', function () {
        expect(MaterialType::Presentation->maxSize())->toBe(50 * 1024 * 1024);
    });

    test('image has correct max size', function () {
        expect(MaterialType::Image->maxSize())->toBe(25 * 1024 * 1024);
    });

    test('interactive has correct max size', function () {
        expect(MaterialType::Interactive->maxSize())->toBe(100 * 1024 * 1024);
    });

    test('video has correct allowed extensions', function () {
        $extensions = MaterialType::Video->allowedExtensions();
        expect($extensions)->toContain('mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv');
    });

    test('pdf has correct allowed extensions', function () {
        $extensions = MaterialType::PDF->allowedExtensions();
        expect($extensions)->toContain('pdf');
    });

    test('document has correct allowed extensions', function () {
        $extensions = MaterialType::Document->allowedExtensions();
        expect($extensions)->toContain('doc', 'docx', 'txt', 'rtf', 'odt');
    });

    test('audio has correct allowed extensions', function () {
        $extensions = MaterialType::Audio->allowedExtensions();
        expect($extensions)->toContain('mp3', 'wav', 'ogg', 'm4a', 'flac');
    });

    test('presentation has correct allowed extensions', function () {
        $extensions = MaterialType::Presentation->allowedExtensions();
        expect($extensions)->toContain('ppt', 'pptx', 'odp');
    });

    test('image has correct allowed extensions', function () {
        $extensions = MaterialType::Image->allowedExtensions();
        expect($extensions)->toContain('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
    });

    test('interactive has correct allowed extensions', function () {
        $extensions = MaterialType::Interactive->allowedExtensions();
        expect($extensions)->toContain('html', 'htm', 'json');
    });

    test('all material types return non-empty extension arrays', function () {
        foreach (MaterialType::cases() as $type) {
            expect($type->allowedExtensions())->not->toBeEmpty();
        }
    });

    test('all material types have positive max size', function () {
        foreach (MaterialType::cases() as $type) {
            expect($type->maxSize())->toBeGreaterThan(0);
        }
    });
});
