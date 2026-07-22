<?php

use Illuminate\Support\Facades\Schema;

test('lessons.video_embed_url column has been dropped', function () {
    expect(Schema::hasColumn('lessons', 'video_embed_url'))->toBeFalse();
});
