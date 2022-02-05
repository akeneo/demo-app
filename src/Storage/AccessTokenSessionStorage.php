<?php

declare(strict_types=1);

namespace App\Storage;

use Symfony\Component\HttpFoundation\RequestStack;

/*
 * **************************************************
 * * /!\ DO NOT USE in a production environment /!\ *
 * **************************************************
 *
 * This storage class is a simple implementation for the demo app purpose only.
 * Each access token should be stored securely in your storage solution, like a database.
 */
class AccessTokenSessionStorage implements AccessTokenStorageInterface
{
    private const ACCESS_TOKEN_SESSION_KEY = 'akeneo_pim_access_token';

    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public function getAccessToken(): ?string
    {
        return $this->requestStack->getSession()->get(self::ACCESS_TOKEN_SESSION_KEY);
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->requestStack->getSession()->set(self::ACCESS_TOKEN_SESSION_KEY, $accessToken);
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove(self::ACCESS_TOKEN_SESSION_KEY);
    }
}
