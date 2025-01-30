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
 * Each user profile should be stored securely in your storage solution, like a database.
 */

class UserProfileSessionStorage implements UserProfileStorageInterface
{
    private const USER_PROFILE_SESSION_KEY = 'akeneo_pim_user_profile';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function getUserProfile(): ?string
    {
        return $this->requestStack->getSession()->get(self::USER_PROFILE_SESSION_KEY);
    }

    public function setUserProfile(string $userProfile): void
    {
        $this->requestStack->getSession()->set(self::USER_PROFILE_SESSION_KEY, $userProfile);
    }
}
