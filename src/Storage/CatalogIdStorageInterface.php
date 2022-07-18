<?php

declare(strict_types=1);

namespace App\Storage;

interface CatalogIdStorageInterface
{
    public function getCatalogId(): ?string;

    public function setCatalogId(string $catalogId): void;

    public function clear(): void;
}
