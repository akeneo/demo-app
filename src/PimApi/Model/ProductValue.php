<?php

declare(strict_types=1);

namespace App\PimApi\Model;

class ProductValue
{
    public function __construct(
        public readonly string $label,
        public readonly string $type,
        public readonly string|bool|int|float $value,
    ) {
    }
}
