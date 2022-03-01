<?php

declare(strict_types=1);

namespace App\Storage;

interface UserProfileStorageInterface
{
    public function getUserProfile(): ?string;

    public function setUserProfile(string $userProfile): void;
}
