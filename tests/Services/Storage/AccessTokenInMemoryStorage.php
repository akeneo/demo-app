<?php

declare(strict_types=1);

namespace App\Tests\Services\Storage;

use App\Storage\AccessTokenStorageInterface;

class AccessTokenInMemoryStorage implements AccessTokenStorageInterface
{
    public function __construct(
        private ?string $accessToken = null
    ) {
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }
}
