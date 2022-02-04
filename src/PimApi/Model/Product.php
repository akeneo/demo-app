<?php

declare(strict_types=1);

namespace App\PimApi\Model;

class Product
{
    /**
     * @param array<ProductValue> $attributes
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $label,
        public readonly array $attributes = [],
    ) {
    }
}
