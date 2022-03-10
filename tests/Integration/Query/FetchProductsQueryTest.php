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
        $result = $query->fetch('en_US');

        $expected = [
            new Product('1111111171', 'Bag', []),
            new Product('1111111172', 'Belt', []),
            new Product('braided-hat-m', 'Braided hat ', []),
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
            'https://example.com/api/rest/v1/products?search=%7B%22enabled%22%3A%5B%7B%22operator%22%3A%22%3D%22%2C%22value%22%3Atrue%7D%5D%7D&locales=en_US&limit=10&with_count=false',
        );

        $query = static::getContainer()->get(FetchProductsQuery::class);
        $result = $query->fetch('en_US');

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
            'https://example.com/api/rest/v1/products?search=%7B%22enabled%22%3A%5B%7B%22operator%22%3A%22%3D%22%2C%22value%22%3Atrue%7D%5D%7D&locales=en_US&limit=10&with_count=false',
        );

        $query = static::getContainer()->get(FetchProductsQuery::class);
        $result = $query->fetch('en_US');

        $expected = [
            new Product('empty', '[empty]', []),
        ];

        $this->assertEquals($expected, $result);
    }
}
