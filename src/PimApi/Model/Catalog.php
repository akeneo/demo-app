<?php

declare(strict_types=1);

namespace App\PimApi\Model;

class Catalog
{
    public const PRODUCT_VALUE_FILTERS_NAME = 'Catalog with product value filters';
    public const ATTRIBUTE_MAPPING_NAME = 'Catalog with attribute mapping';
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly bool $enabled,
    ) {
    }
}
