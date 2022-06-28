<?php

declare(strict_types=1);

namespace App\PimApi\Model;

class Catalog
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly bool $enabled,
    ) {
    }
}
