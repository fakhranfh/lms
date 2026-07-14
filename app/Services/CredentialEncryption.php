<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class CredentialEncryption
{
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    public function decrypt(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }
}
