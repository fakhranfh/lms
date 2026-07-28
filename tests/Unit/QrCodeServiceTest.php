<?php

use App\Services\QrCodeService;

test('svg renders a valid svg document containing the given content encoded as a QR code', function () {
    $svg = (new QrCodeService)->svg('00020101021126...QRIS-payload...');

    expect($svg)->toContain('<svg')
        ->toContain('</svg>');
});
