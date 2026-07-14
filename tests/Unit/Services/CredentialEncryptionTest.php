<?php

use App\Services\CredentialEncryption;

it('can encrypt and decrypt a string', function () {
    $encryption = new CredentialEncryption;
    $plaintext = 'my_secret_key_12345';

    $encrypted = $encryption->encrypt($plaintext);

    expect($encrypted)->not()->toBe($plaintext);
    expect($encryption->decrypt($encrypted))->toBe($plaintext);
});

it('encrypts different values to different ciphertexts', function () {
    $encryption = new CredentialEncryption;

    $encrypted1 = $encryption->encrypt('value1');
    $encrypted2 = $encryption->encrypt('value1');

    expect($encrypted1)->not()->toBe($encrypted2);
});
