<?php

declare(strict_types=1);

namespace App\Query;

use App\PimApi\Model\Product;
use App\PimApi\PimCatalogApiClient;

/**
 * @phpstan-type RawMappedProduct array{
 *      uuid: string,
 *      title: string,
 *      description: string,
 *      code: string,
 * }
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
        $rawMappedProducts = $this->catalogApiClient->getMappedProducts($catalogId);

        $products = [];
        foreach ($rawMappedProducts as $rawMappedProduct) {
            $label = isset($rawMappedProduct['title']) && '' !== $rawMappedProduct['title']
                ? $rawMappedProduct['title']
                : $rawMappedProduct['uuid'];

            $products[] = new Product($rawMappedProduct['uuid'], $label);
        }

        return $products;
    }
}
