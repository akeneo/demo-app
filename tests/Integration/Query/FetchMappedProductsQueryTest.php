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

    private ?FetchMappedProductsQuery $query = null;

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
            new Product('5b8381e2-a97a-4120-87da-1ef8b9c53988', 'Sunglasses 2'),
            new Product('e5442b25-683d-4ea4-bab3-d31a240d3a1a', 'Sunglasses 3'),
            new Product('08e92c31-375a-414f-86ce-f146dc180727', 'Sunglasses 4'),
            new Product('bee7ad24-f08b-4603-9d88-1a80f099640e', 'Canon imageFormula DR-C125'),
            new Product('554ed26b-b179-4058-9ff8-4e4a660dbd8a', 'Kodak i2600 for Govt'),
            new Product('032a9ced-134c-41eb-9bb2-ee2089e3496a', 'Kodak Scanner upgrade kit I640 TO I660'),
            new Product('ab88bd1a-a944-49f8-b633-33a972a4efce', 'HP Scanjet G4050'),
            new Product('4c40f7c5-416d-48af-8635-e4e5f0915092', 'HP Scanjet G4010'),
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
