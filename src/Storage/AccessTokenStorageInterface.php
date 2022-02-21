<?php

declare(strict_types=1);

namespace App\Storage;

interface AccessTokenStorageInterface
{
    public function getAccessToken(): ?string;

    public function setAccessToken(string $accessToken): void;

    public function clear(): void;
}
