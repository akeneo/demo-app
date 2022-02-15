<?php

declare(strict_types=1);

namespace App\Security;

class Decrypt
{
    public function __construct(
        private string $method,
        private string $password,
    ) {
    }

    public function __invoke(string $raw): string
    {
        $triplet = \base64_decode($raw);

        $encryptionKey = \hash_hkdf('sha256', $this->password, 0, 'aes-256-encryption');
        $authenticationKey = \hash_hkdf('sha256', $this->password, 0, 'sha-256-authentication');

        $ivlen = \openssl_cipher_iv_length($this->method) ?: 0;
        $iv = \substr($triplet, 0, $ivlen);
        $hmac = \substr($triplet, $ivlen, 64);
        $cipherText = \substr($triplet, $ivlen + 64);

        $compare = \hash_hmac('sha256', $iv.$cipherText, $authenticationKey);

        if ($hmac !== $compare) {
            throw new \Exception('invalid hmac', 401);
        }

        $clearText = \openssl_decrypt($cipherText, $this->method, $encryptionKey, 0, $iv);

        return $clearText ?: '';
    }
}
