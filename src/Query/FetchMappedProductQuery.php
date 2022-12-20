<?php

declare(strict_types=1);

namespace App\Query;

use App\Exception\CatalogProductNotFoundException;
use App\PimApi\Exception\PimApiException;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use App\PimApi\PimCatalogApiClient;

/**
 * @phpstan-type RawMappedProduct array{
 *      uuid: string,
 *      title: string,
 *      description: string,
 *      code: string,
 * }
 */
final class FetchMappedProductQuery
{
    public function __construct(
        private readonly PimCatalogApiClient $catalogApiClient,
    ) {
    }

    public function fetch(string $catalogId, string $productUuid): Product
    {
        try {
            /** @var RawMappedProduct $rawMappedProduct */
            $rawMappedProduct = $this->catalogApiClient->getMappedProduct($catalogId, $productUuid);
        } catch (PimApiException $e) {
            throw new CatalogProductNotFoundException();
        }

        $label = !empty($rawMappedProduct['title']) ? $rawMappedProduct['title'] : $rawMappedProduct['uuid'];

        $values = [
            new ProductValue('mapped_properties.uuid', 'string', $rawMappedProduct['uuid']),
            new ProductValue('mapped_properties.title', 'string', $rawMappedProduct['title']),
            new ProductValue('mapped_properties.description', 'string', $rawMappedProduct['description']),
            new ProductValue('mapped_properties.code', 'string', $rawMappedProduct['code']),
        ];

        return new Product($productUuid, $label, $values);
    }
}
