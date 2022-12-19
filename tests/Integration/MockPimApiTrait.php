<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Component\HttpClient\Response\MockResponse;

trait MockPimApiTrait
{
    /**
     * @param array<array-key, mixed> $options
     */
    protected function mockPimAPIResponse(
        string $filename,
        string $url,
        string $method = 'GET',
        array $options = []
    ): void {
        assert($this instanceof AbstractIntegrationTest);

        $path = sprintf('%s/../Fixtures/responses/%s', __DIR__, $filename);
        if (!file_exists($path)) {
            throw new \LogicException(sprintf('File not found %s', $path));
        }

        $body = file_get_contents($path);

        $this->mockHttpResponse($method, $url, $options, new MockResponse($body));
    }

    protected function mockDefaultPimAPIResponses(): void
    {
        $this->mockPimAPIResponse(
            'get-product-sunglasses.json',
            'https://example.com/api/rest/v1/products/1111111304',
        );
        $this->mockPimAPIResponse(
            'get-product-scanners.json',
            'https://example.com/api/rest/v1/products/10661721',
        );
        $this->mockPimAPIResponse(
            'get-product-empty.json',
            'https://example.com/api/rest/v1/products/empty',
        );
        $this->mockPimAPIResponse(
            'get-family-accessories.json',
            'https://example.com/api/rest/v1/families/accessories',
        );
        $this->mockPimAPIResponse(
            'get-family-scanners.json',
            'https://example.com/api/rest/v1/families/scanners',
        );
        $this->mockPimAPIResponse(
            'get-attribute-image.json',
            'https://example.com/api/rest/v1/attributes/image',
        );
        $this->mockPimAPIResponse(
            'get-attribute-description.json',
            'https://example.com/api/rest/v1/attributes/description',
        );
        $this->mockPimAPIResponse(
            'get-attribute-ean.json',
            'https://example.com/api/rest/v1/attributes/ean',
        );
        $this->mockPimAPIResponse(
            'get-attribute-name.json',
            'https://example.com/api/rest/v1/attributes/name',
        );
        $this->mockPimAPIResponse(
            'get-attribute-weight.json',
            'https://example.com/api/rest/v1/attributes/weight',
        );
        $this->mockPimAPIResponse(
            'get-attribute-size.json',
            'https://example.com/api/rest/v1/attributes/size',
        );
        $this->mockPimAPIResponse(
            'get-attribute-color.json',
            'https://example.com/api/rest/v1/attributes/color',
        );
        $this->mockPimAPIResponse(
            'get-attribute-material.json',
            'https://example.com/api/rest/v1/attributes/material',
        );
        $this->mockPimAPIResponse(
            'get-attribute-collection.json',
            'https://example.com/api/rest/v1/attributes/collection',
        );
        $this->mockPimAPIResponse(
            'get-attribute-variation_name.json',
            'https://example.com/api/rest/v1/attributes/variation_name',
        );
        $this->mockPimAPIResponse(
            'get-locales.json',
            'https://example.com/api/rest/v1/locales?search=%7B%22enabled%22%3A%5B%7B%22operator%22%3A%22%3D%22%2C%22value%22%3Atrue%7D%5D%7D&limit=100&with_count=false',
        );
        $this->mockPimAPIResponse(
            'get-attribute-picture.json',
            'https://example.com/api/rest/v1/attributes/picture',
        );
        $this->mockPimAPIResponse(
            'get-attribute-release_date.json',
            'https://example.com/api/rest/v1/attributes/release_date',
        );
        $this->mockPimAPIResponse(
            'get-attribute-color_scanning.json',
            'https://example.com/api/rest/v1/attributes/color_scanning',
        );
        $this->mockPimAPIResponse(
            'get-families-accessories.json',
            'https://example.com/api/rest/v1/families?search=%7B%22code%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%22accessories%22%5D%7D%5D%7D&limit=100&with_count=false',
        );
        $this->mockPimAPIResponse(
            'get-families-scanners.json',
            'https://example.com/api/rest/v1/families?search=%7B%22code%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%22scanners%22%5D%7D%5D%7D&limit=100&with_count=false',
        );
        $this->mockPimAPIResponse(
            'get-attributes-image-ean-name-weight-description.json',
            'https://example.com/api/rest/v1/attributes?search=%7B%22code%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%22image%22%2C%22ean%22%2C%22name%22%2C%22weight%22%2C%22description%22%5D%7D%5D%7D&limit=100&with_count=false',
        );
        $this->mockPimAPIResponse(
            'get-attributes-price-picture-name-description-release_date-color_scanning-tag.json',
            'https://example.com/api/rest/v1/attributes?search=%7B%22code%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%22price%22%2C%22picture%22%2C%22name%22%2C%22description%22%2C%22release_date%22%2C%22color_scanning%22%2C%22tag%22%5D%7D%5D%7D&limit=100&with_count=false',
        );
        $this->mockPimAPIResponse(
            'get-attribute-options-tag.json',
            'https://example.com/api/rest/v1/attributes/tag/options?limit=100&with_count=false',
        );

        $this->mockPimAPIResponse(
            'get-catalogs.json',
            'https://example.com/api/rest/v1/catalogs',
        );
        $this->mockPimAPIResponse(
            'get-catalog-product-value-filters.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a',
        );
        $this->mockPimAPIResponse(
            'get-catalog-attribute-mapping.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
        );
        $this->mockPimAPIResponse(
            'get-catalog-disabled.json',
            'https://example.com/api/rest/v1/catalogs/ad1f6e7a-e6d9-495f-b568-f4f473803679',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-products.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products?limit=10',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-products-empty-list.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/mapped-products?limit=10',
        );

        $this->mockPimAPIResponse(
            'get-catalogs-product-identifiers-store-us.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/product-identifiers?limit=10',
        );
        $this->mockPimAPIResponse(
            'get-products-in-identifiers.json',
            'https://example.com/api/rest/v1/products?search=%7B%22identifier%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%221004114%22%2C%2210649473%22%2C%2210655295%22%5D%7D%5D%7D&locales=en_US&limit=10&with_count=false',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-products-scanners.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products?limit=10',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-product-scanner.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/554ed26b-b179-4058-9ff8-4e4a660dbd8a',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-product-sunglasses.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/16467667-9a29-48c1-90b3-8a169b83e8e6',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-product-empty.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/empty',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-product-scanner.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/a5eed606-4f98-4d8c-b926-5b59f8fb0ee7',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-product-catalog-disabled.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/disabled',
        );
    }
}
