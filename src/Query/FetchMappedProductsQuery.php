<?php

declare(strict_types=1);

namespace App\Query;

use App\PimApi\Model\Product;
use App\PimApi\PimCatalogApiClient;

/**
 * @phpstan-import-type RawMappedProduct from PimCatalogApiClient
 */
final class FetchMappedProductsQuery
{
    public function __construct(private readonly PimCatalogApiClient $catalogApiClient)
    {
    }

    /**
     * @return array<Product>
     */
    public function fetch(string $catalogId): array
    {
        /** @var array<RawMappedProduct> $rawMappedProducts */
        $rawMappedProducts = $this->catalogApiClient->getMappedProducts($catalogId);

        $products = [];
        foreach ($rawMappedProducts as $rawMappedProduct) {
            $label = '' !== $rawMappedProduct['name']
                ? $rawMappedProduct['name']
                : $rawMappedProduct['uuid'];

            $products[] = new Product($rawMappedProduct['uuid'], $label);
        }

        return $products;
    }
}
