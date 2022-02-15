<?php

declare(strict_types=1);

namespace App\Security;

class Encrypt
{
    public function __construct(
        private string $method,
        private string $password,
    ) {
    }

    public function __invoke(string $clearText): string
    {
        $encryptionKey = \hash_hkdf('sha256', $this->password, 0, 'aes-256-encryption');
        $authenticationKey = \hash_hkdf('sha256', $this->password, 0, 'sha-256-authentication');

        $iv = \openssl_random_pseudo_bytes(\openssl_cipher_iv_length($this->method) ?: 0);
        $cipherText = \openssl_encrypt($clearText, $this->method, $encryptionKey, 0, $iv);
        $hmac = \hash_hmac('sha256', $iv.$cipherText, $authenticationKey);

        return \base64_encode($iv.$hmac.$cipherText);
    }
}
