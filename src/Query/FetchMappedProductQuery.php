<?php

declare(strict_types=1);

namespace App\Query;

use App\Exception\CatalogProductNotFoundException;
use App\PimApi\Exception\PimApiException;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use App\PimApi\PimCatalogApiClient;

/**
 * @phpstan-import-type RawMappedProduct from PimCatalogApiClient
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
        } catch (PimApiException) {
            throw new CatalogProductNotFoundException();
        }

        $label = !empty($rawMappedProduct['name']) ? $rawMappedProduct['name'] : $rawMappedProduct['uuid'];

        $values = [
            new ProductValue('mapped_properties.uuid', 'string', $rawMappedProduct['uuid']),
            new ProductValue('mapped_properties.sku', 'string', $rawMappedProduct['sku']),
            new ProductValue('mapped_properties.name', 'string', $rawMappedProduct['name']),
            new ProductValue('mapped_properties.type', 'string', $rawMappedProduct['type'] ?? null),
            new ProductValue('mapped_properties.body_html', 'string', $rawMappedProduct['body_html'] ?? null),
            new ProductValue('mapped_properties.main_image', 'string+uri', $rawMappedProduct['main_image'] ?? null),
            new ProductValue('mapped_properties.main_color', 'string', $rawMappedProduct['main_color'] ?? null),
            new ProductValue('mapped_properties.colors', 'array<string>', $rawMappedProduct['colors'] ?? null),
            new ProductValue('mapped_properties.available', 'boolean', $rawMappedProduct['available'] ?? null),
            new ProductValue('mapped_properties.price', 'number', $rawMappedProduct['price'] ?? null),
            new ProductValue('mapped_properties.publication_date', 'string', $rawMappedProduct['publication_date'] ?? null),
            new ProductValue('mapped_properties.certification_number', 'string', $rawMappedProduct['certification_number'] ?? null),
            new ProductValue('mapped_properties.size_letter', 'string', $rawMappedProduct['size_letter'] ?? null),
            new ProductValue('mapped_properties.size_number', 'number', $rawMappedProduct['size_number'] ?? null),
            new ProductValue('mapped_properties.weight', 'number', $rawMappedProduct['weight'] ?? null),
        ];

        return new Product($productUuid, $label, $values);
    }
}
