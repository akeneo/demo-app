<?php

declare(strict_types=1);

namespace App\Tests\Integration\Query;

use App\PimApi\Model\Product;
use App\Query\FetchMappedProductsQuery;
use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;

class FetchMappedProductsQueryTest extends AbstractIntegrationTest
{
    use MockPimApiTrait;

    private ?FetchMappedProductsQuery $query;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpFakeAccessTokenStorage();
        $this->setUpFakePimUrlStorage();
        $this->mockDefaultPimAPIResponses();

        $this->query = self::getContainer()->get(FetchMappedProductsQuery::class);
    }

    /**
     * @test
     */
    public function itFetchesMappedProduct(): void
    {
        $result = $this->query->fetch('8a8494d2-05cc-4b8f-942e-f5ea7591e89c');

        $this->assertEquals([
            new Product('a5eed606-4f98-4d8c-b926-5b59f8fb0ee7', 'Kodak i2600 for Govt'),
            new Product('16467667-9a29-48c1-90b3-8a169b83e8e6', 'Sunglasses'),
            new Product('5b8381e2-a97a-4120-87da-1ef8b9c53988', '5b8381e2-a97a-4120-87da-1ef8b9c53988'),
        ], $result);
    }

    /**
     * @test
     */
    public function itReturnsAnEmptyList(): void
    {
        $result = $this->query->fetch('70313d30-8316-41c2-b298-8f9e7186fe9a');

        $this->assertEmpty($result);
    }
}
