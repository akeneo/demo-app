<?php

declare(strict_types=1);

namespace App\Service;

use App\PimApi\Model\Catalog;
use App\PimApi\PimCatalogApiClient;
use App\Storage\CatalogIdStorageInterface;

final class InitializeAppData
{
    public function __construct(
        private readonly CatalogIdStorageInterface $catalogIdStorage,
        private readonly PimCatalogApiClient $pimCatalogApiClient,
    ) {
    }

    public function __invoke(): void
    {
        $catalogs = $this->pimCatalogApiClient->getCatalogs();
        $valueFilterCatalog = $this->findCatalogWithName($catalogs, Catalog::PRODUCT_VALUE_FILTERS_NAME);
        $attributeMappingCatalog = $this->findCatalogWithName($catalogs, Catalog::ATTRIBUTE_MAPPING_NAME);

        if (null === $valueFilterCatalog) {
            $valueFilterCatalog = $this->pimCatalogApiClient->createCatalog(Catalog::PRODUCT_VALUE_FILTERS_NAME);
        }

        if (null === $attributeMappingCatalog) {
            $attributeMappingCatalog = $this->pimCatalogApiClient->createCatalog(Catalog::ATTRIBUTE_MAPPING_NAME);
            $this->pimCatalogApiClient->setProductMappingSchema($attributeMappingCatalog->id, $this->getProductMappingSchema());
        }

        /* @TODO to remove along with its storage once product list and product details pages stop using it */
        $this->catalogIdStorage->setCatalogId($valueFilterCatalog->id);
    }

    /**
     * @param array<Catalog> $catalogs
     */
    private function findCatalogWithName(array $catalogs, string $name): ?Catalog
    {
        foreach ($catalogs as $catalog) {
            if ($name === $catalog->name) {
                return $catalog;
            }
        }

        return null;
    }

    private function getProductMappingSchema(): string
    {
        return <<<'JSON_WRAP'
        {
           "$id":"https://example.com/product",
           "$schema":"https://api.akeneo.com/mapping/product/0.0.2/schema",
           "$comment":"We give you an example of product mapping schema!",
           "title":"Demo app - Product Mapping Schema",
           "description":"JSON Schema describing the structure of products expected by the Demo App",
           "type":"object",
           "properties":{
              "title":{
                 "title":"Title",
                 "type":"string",
                 "description": "Used in the product grid and displayed in the product page details"
              },
              "description":{
                 "title":"Description",
                 "type":"string",
                 "description": "Only displayed in the product page details"
              },
              "code":{
                 "title":"Code",
                 "type":"string",
                 "description": "Only displayed in the product page details"
              },
              "uuid":{
                 "title":"Product UUID",
                 "type":"string"
              }
           }
        }
        JSON_WRAP;
    }
}
