<?php

declare(strict_types=1);

namespace App\Service;

use App\PimApi\Model\Catalog;
use App\PimApi\PimCatalogApiClient;

final class InitializeAppData
{
    public function __construct(
        private readonly PimCatalogApiClient $pimCatalogApiClient,
    ) {
    }

    public function __invoke(): void
    {
        $catalogs = $this->pimCatalogApiClient->getCatalogs();
        $valueFilterCatalog = $this->findCatalogWithName($catalogs, Catalog::PRODUCT_VALUE_FILTERS_NAME);
        $attributeMappingCatalog = $this->findCatalogWithName($catalogs, Catalog::ATTRIBUTE_MAPPING_NAME);

        if (null === $valueFilterCatalog) {
            $this->pimCatalogApiClient->createCatalog(Catalog::PRODUCT_VALUE_FILTERS_NAME);
        }

        if (null === $attributeMappingCatalog) {
            $attributeMappingCatalog = $this->pimCatalogApiClient->createCatalog(Catalog::ATTRIBUTE_MAPPING_NAME);
            $this->pimCatalogApiClient->setProductMappingSchema($attributeMappingCatalog->id, $this->getProductMappingSchema());
        }
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
            "$id": "https://example.com/product",
            "$schema": "https://api.akeneo.com/mapping/product/0.0.13/schema",
            "$comment": "My schema !",
            "title": "Product Mapping",
            "description": "JSON Schema describing the structure of products expected by our application",
            "type": "object",
            "properties": {
                "uuid": {
                    "title": "Product UUID",
                    "type": "string"
                },
                "type": {
                    "title": "Product type",
                    "type": "string"
                },
                "sku": {
                    "title": "SKU (Stock Keeping Unit)",
                    "description": "Selling Partner SKU (stock keeping unit) identifier for the listing. \n SKU uniquely identifies a listing for a Selling Partner.",
                    "type": "string"
                },
                "name": {
                    "title": "Product name",
                    "type": "string"
                },
                "body_html": {
                    "title": "Description (textarea)",
                    "description": "Product description in raw HTML",
                    "type": "string",
                    "minLength": 0,
                    "maxLength": 255
                },
                "main_image": {
                    "title": "Main image",
                    "description": "Format: URI/link",
                    "type": "string",
                    "format": "uri"
                },
                "main_color": {
                    "title": "Main color",
                    "description": "The main color of the product, used by grid filters on your e-commerce website.",
                    "type": "string"
                },
                "colors": {
                    "title": "Colors",
                    "description": "List of colors separated by a comma.",
                    "type": "array",
                    "items": {
                        "type": "string",
                        "enum": ["blue", "red", "green", "yellow"]
                    }
                },
                "available": {
                    "title": "Is available",
                    "description": "Used to display when a product is out of stock on your e-commerce website.",
                    "type": "boolean"
                },
                "price": {
                    "title": "Price (€)",
                    "type": "number",
                    "minimum": 0,
                    "maximum": 10000
                },
                "publication_date": {
                    "title": "Publication date",
                    "description": "Format: ISO 8601 standard. \nUsed to filter products that must be published on your e-commerce website depending on the current date.",
                    "type": "string",
                    "format": "date-time"
                },
                "certification_number": {
                    "title": "Certification number",
                    "type": "string",
                    "pattern": "^([0-9]{5})-([0-9]):([0-9]{4})$"
                },
                "size_letter": {
                    "title": "Size (letter)",
                    "type": "string",
                    "enum": ["S", "M", "L", "XL"]
                },
                "size_number": {
                    "title": "Size",
                    "type": "number",
                    "enum": [36, 38, 40, 42]
                },
                "weight": {
                    "title": "Weight (grams)",
                    "type": "number",
                    "minimum": 0
                }
            },
            "required": [
                "sku", "name"
            ]
        }
        JSON_WRAP;
    }
}
