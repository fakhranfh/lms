<?php

use App\Support\RichTextAttachments;

test('extracts img src and a href urls', function () {
    $html = '<p><img src="https://example.test/a.png"></p><a href="https://example.test/b.pdf">b</a>';

    expect(RichTextAttachments::extractUrls($html))->toBe([
        'https://example.test/a.png',
        'https://example.test/b.pdf',
    ]);
});

test('extracts video chip urls from data-video-preview instead of href', function () {
    $html = '<a href="#" data-video-preview="https://example.test/video.mp4" class="rte-file-chip">video</a>';

    expect(RichTextAttachments::extractUrls($html))->toBe([
        'https://example.test/video.mp4',
    ]);
});

test('ignores a placeholder href of hash alone', function () {
    $html = '<a href="#">no url here</a>';

    expect(RichTextAttachments::extractUrls($html))->toBe([]);
});

test('returns unique urls', function () {
    $html = '<a href="https://example.test/a.pdf">one</a><a href="https://example.test/a.pdf">two</a>';

    expect(RichTextAttachments::extractUrls($html))->toBe([
        'https://example.test/a.pdf',
    ]);
});

test('returns empty array for null or empty html', function () {
    expect(RichTextAttachments::extractUrls(null))->toBe([]);
    expect(RichTextAttachments::extractUrls(''))->toBe([]);
});
