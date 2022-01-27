<?php

declare(strict_types=1);

namespace App\Dto\Product;

class Product
{
    public function __construct(
        private string $identifier,
        private string $label,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
