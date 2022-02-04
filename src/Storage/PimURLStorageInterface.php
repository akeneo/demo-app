<?php

declare(strict_types=1);

namespace App\Storage;

interface PimURLStorageInterface
{
    public function getPimURL(): ?string;
}
