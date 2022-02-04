<?php

declare(strict_types=1);

namespace App\Tests\Integration\Query;

use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
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

    public function testFetchesProducts(): void
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
}
