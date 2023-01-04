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

    private ?FetchMappedProductQuery $query;

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
                label: 'mapped_properties.title',
                type: 'string',
                value: 'Kodak i2600 for Govt',
            ),
            new ProductValue(
                label: 'mapped_properties.description',
                type: 'string',
                value: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            ),
            new ProductValue(
                label: 'mapped_properties.code',
                type: 'string',
                value: '',
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
}
