<?php

declare(strict_types=1);

namespace App\Tests\Integration\Query;

use App\Exception\CatalogDisabledException;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use App\Query\FetchMappedProductQuery;
use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;

class FetchMappedProductQueryTest extends AbstractIntegrationTest
{
    use MockPimApiTrait;

    private ?FetchMappedProductQuery $query = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpFakeAccessTokenStorage();
        $this->setUpFakePimUrlStorage();
        $this->mockDefaultPimAPIResponses();

        $this->query = static::getContainer()->get(FetchMappedProductQuery::class);
    }

    /**
     * @test
     */
    public function itFetchesAProduct(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-product-scanner.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/a5eed606-4f98-4d8c-b926-5b59f8fb0ee7',
        );

        $result = $this->query->fetch('8a8494d2-05cc-4b8f-942e-f5ea7591e89c', 'a5eed606-4f98-4d8c-b926-5b59f8fb0ee7');

        $expected = new Product('a5eed606-4f98-4d8c-b926-5b59f8fb0ee7', 'Kodak i2600 for Govt', [
            new ProductValue(
                label: 'mapped_properties.uuid',
                type: 'string',
                value: 'a5eed606-4f98-4d8c-b926-5b59f8fb0ee7',
            ),
            new ProductValue(
                label: 'mapped_properties.sku',
                type: 'string',
                value: '1234567890317',
            ),
            new ProductValue(
                label: 'mapped_properties.name',
                type: 'string',
                value: 'Kodak i2600 for Govt',
            ),
            new ProductValue(
                label: 'mapped_properties.type',
                type: 'string',
                value: 'scanner',
            ),
            new ProductValue(
                label: 'mapped_properties.body_html',
                type: 'string',
                value: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            ),
            new ProductValue(
                label: 'mapped_properties.main_image',
                type: 'string+uri',
                value: 'https://www.example.com/kodak-i2600.jpg',
            ),
            new ProductValue(
                label: 'mapped_properties.main_color',
                type: 'string',
                value: 'navy blue',
            ),
            new ProductValue(
                label: 'mapped_properties.colors',
                type: 'array<string>',
                value: ['grey', 'black', 'navy blue'],
            ),
            new ProductValue(
                label: 'mapped_properties.available',
                type: 'boolean',
                value: true,
            ),
            new ProductValue(
                label: 'mapped_properties.price',
                type: 'number',
                value: '269',
            ),
            new ProductValue(
                label: 'mapped_properties.publication_date',
                type: 'string',
                value: '2023-02-01T14:41:36+02:00',
            ),
            new ProductValue(
                label: 'mapped_properties.certification_number',
                type: 'string',
                value: '213-451-2154-124',
            ),
            new ProductValue(
                label: 'mapped_properties.size_letter',
                type: 'string',
                value: 'M',
            ),
            new ProductValue(
                label: 'mapped_properties.size_number',
                type: 'number',
                value: 36,
            ),
            new ProductValue(
                label: 'mapped_properties.weight',
                type: 'number',
                value: 1452,
            ),
        ]);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function itThrowDisabledCatalogExceptionWhenCatalogIsDisabledWithMessageInThePayload(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-product-catalog-disabled.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/disabled',
        );

        $this->expectException(CatalogDisabledException::class);
        $this->query->fetch('8a8494d2-05cc-4b8f-942e-f5ea7591e89c', 'disabled');
    }

    /**
     * @test
     */
    public function itFetchesAProductDespiteMissingFields(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-product-missing-fields.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/a5eed606-4f98-4d8c-b926-5b59f8fb0ee7',
        );

        $result = $this->query->fetch('8a8494d2-05cc-4b8f-942e-f5ea7591e89c', 'a5eed606-4f98-4d8c-b926-5b59f8fb0ee7');

        $expected = new Product('a5eed606-4f98-4d8c-b926-5b59f8fb0ee7', 'Kodak i2600 for Govt', [
            new ProductValue(
                label: 'mapped_properties.uuid',
                type: 'string',
                value: 'a5eed606-4f98-4d8c-b926-5b59f8fb0ee7',
            ),
            new ProductValue(
                label: 'mapped_properties.sku',
                type: 'string',
                value: '1234567890317',
            ),
            new ProductValue(
                label: 'mapped_properties.name',
                type: 'string',
                value: 'Kodak i2600 for Govt',
            ),
            new ProductValue(
                label: 'mapped_properties.type',
                type: 'string',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.body_html',
                type: 'string',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.main_image',
                type: 'string+uri',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.main_color',
                type: 'string',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.colors',
                type: 'array<string>',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.available',
                type: 'boolean',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.price',
                type: 'number',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.publication_date',
                type: 'string',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.certification_number',
                type: 'string',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.size_letter',
                type: 'string',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.size_number',
                type: 'number',
                value: null,
            ),
            new ProductValue(
                label: 'mapped_properties.weight',
                type: 'number',
                value: null,
            ),
        ]);

        $this->assertEquals($expected, $result);
    }
}
