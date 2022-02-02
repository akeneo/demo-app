<?php

declare(strict_types=1);

namespace App\Tests\Services\Storage;

use App\Storage\PimURLStorageInterface;

class PimURLInMemoryStorage implements PimURLStorageInterface
{
    public function __construct(
        private string $url
    ) {
    }

    public function getPimURL(): ?string
    {
        return $this->url;
    }
}
