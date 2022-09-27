<?php

declare(strict_types=1);

namespace App\Tests\Integration\Query;

use App\PimApi\Model\Product;
use App\Query\FetchProductsQuery;
use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;

class FetchProductsQueryTest extends AbstractIntegrationTest
{
    use MockPimApiTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpFakeAccessTokenStorage();
        $this->setUpFakePimUrlStorage();
        $this->mockDefaultPimAPIResponses();
    }

    /**
     * @test
     */
    public function itFetchesProducts(): void
    {
        $query = static::getContainer()->get(FetchProductsQuery::class);
        $result = $query?->fetch('en_US', '70313d30-8316-41c2-b298-8f9e7186fe9a');

        $expected = [
            new Product('5b8381e2-a97a-4120-87da-1ef8b9c53988', 'Kodak i1410', []),
            new Product('e5442b25-683d-4ea4-bab3-d31a240d3a1a', 'ION Film2SD Rapid Feeder', []),
            new Product('08e92c31-375a-414f-86ce-f146dc180727', 'Kodak i2800 for Govt', []),
            new Product('bee7ad24-f08b-4603-9d88-1a80f099640e', 'Canon imageFormula DR-C125', []),
            new Product('554ed26b-b179-4058-9ff8-4e4a660dbd8a', 'Kodak i2600 for Govt', []),
            new Product('032a9ced-134c-41eb-9bb2-ee2089e3496a', 'Kodak Scanner upgrade kit I640 TO I660', []),
            new Product('ab88bd1a-a944-49f8-b633-33a972a4efce', 'HP Scanjet G4050', []),
            new Product('4c40f7c5-416d-48af-8635-e4e5f0915092', 'HP Scanjet G4010', []),
            new Product('0a2fdc68-e5bf-4919-b1c1-f0b6b2048d2a', 'Avision AV-220C2M+', []),
            new Product('6df2b529-f09e-4cc2-86c2-aef3759ca7bf', 'Avision AV36', []),
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function itReturnsAnEmptyListWhenThereIsNoProducts(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-products-empty-list.json',
            'https://example.com/api/rest/v1/catalogs/catalog_store_us_products_empty_list/products?limit=10',
        );

        $query = static::getContainer()->get(FetchProductsQuery::class);
        $result = $query?->fetch('en_US', 'catalog_store_us_products_empty_list');

        $expected = [];

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function itFetchesEmptyProducts(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-products-empty.json',
            'https://example.com/api/rest/v1/catalogs/catalog_store_us_products_empty/products?limit=10',
        );

        $query = static::getContainer()->get(FetchProductsQuery::class);
        $result = $query->fetch('en_US', 'catalog_store_us_products_empty');

        $expected = [
            new Product('empty', '[empty]', []),
        ];

        $this->assertEquals($expected, $result);
    }
}
