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
            new Product('1111111171', 'Bag', [
                new ProductValue(
                    'EAN',
                    'pim_catalog_text',
                    '1234567890183',
                ),
                new ProductValue(
                    'Name',
                    'pim_catalog_text',
                    'Bag',
                ),
            ]),
            new Product('1111111172', 'Belt', [
                new ProductValue(
                    'EAN',
                    'pim_catalog_text',
                    '1234567890184',
                ),
                new ProductValue(
                    'Name',
                    'pim_catalog_text',
                    'Belt',
                ),
            ]),
            new Product('braided-hat-m', 'Braided hat ', [
                new ProductValue(
                    'EAN',
                    'pim_catalog_text',
                    '1234567890348',
                ),
                new ProductValue(
                    'Name',
                    'pim_catalog_text',
                    'Braided hat ',
                ),
                new ProductValue(
                    'Variant Name',
                    'pim_catalog_text',
                    'Braided hat battleship grey',
                ),
            ]),
        ];

        $this->assertEquals($expected, $result);
    }
}
