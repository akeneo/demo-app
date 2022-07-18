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
        $result = $query?->fetch('en_US', ['1004114', '10649473', '10655295']);

        $expected = [
            new Product('1004114', 'Kodak i1410', []),
            new Product('10649473', 'ION Film2SD Rapid Feeder', []),
            new Product('10655295', 'Kodak i2800 for Govt', []),
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function itReturnsAnEmptyListWhenThereIsNoProducts(): void
    {
        $this->mockPimAPIResponse(
            'get-products-empty-list.json',
            'https://example.com/api/rest/v1/products?search=%7B%22identifier%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%221111111171%22%2C%221111111172%22%2C%22braided-hat-m%22%5D%7D%5D%7D&locales=en_US&limit=10&with_count=false',
        );

        $query = static::getContainer()->get(FetchProductsQuery::class);
        $result = $query?->fetch('en_US', ['1111111171', '1111111172', 'braided-hat-m']);

        $expected = [];

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function itFetchesEmptyProducts(): void
    {
        $this->mockPimAPIResponse(
            'get-products-empty.json',
            'https://example.com/api/rest/v1/products?search=%7B%22identifier%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%22empty%22%5D%7D%5D%7D&locales=en_US&limit=10&with_count=false',
        );

        $query = static::getContainer()->get(FetchProductsQuery::class);
        $result = $query->fetch('en_US', ['empty']);

        $expected = [
            new Product('empty', '[empty]', []),
        ];

        $this->assertEquals($expected, $result);
    }
}
