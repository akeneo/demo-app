<?php

declare(strict_types=1);

namespace App\Storage;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * **************************************************
 * * /!\ DO NOT USE in a production environment /!\ *
 * **************************************************
 *
 * This storage class is a simple implementation for the demo app purpose only.
 * Each access token must be related to the user connected to the app.
 */
class AccessTokenSessionStorage implements AccessTokenStorageInterface
{
    private const ACCESS_TOKEN_SESSION_KEY = 'akeneo_pim_access_token';

    public function __construct(
        private SessionInterface $session
    ) {
    }

    public function getAccessToken(): ?string
    {
        return $this->session->get(self::ACCESS_TOKEN_SESSION_KEY);
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->session->set(self::ACCESS_TOKEN_SESSION_KEY, $accessToken);
    }
}
