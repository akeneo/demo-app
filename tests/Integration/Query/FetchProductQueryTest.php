<?php

declare(strict_types=1);

namespace App\Tests\Integration\Query;

use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use App\Query\FetchProductQuery;
use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;

class FetchProductQueryTest extends AbstractIntegrationTest
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
    public function itFetchesAProduct(): void
    {
        $query = static::getContainer()->get(FetchProductQuery::class);
        $result = $query->fetch('catalog_store_us_id', '554ed26b-b179-4058-9ff8-4e4a660dbd8a', 'en_US');

        $expected = new Product('554ed26b-b179-4058-9ff8-4e4a660dbd8a', 'Kodak i2600 for Govt', [
            new ProductValue(
                'Price',
                'pim_catalog_price_collection',
                '100.00 EUR; 200.00 USD',
            ),
            new ProductValue(
                'Name',
                'pim_catalog_text',
                'Kodak i2600 for Govt',
            ),
            new ProductValue(
                'Description',
                'pim_catalog_textarea',
                '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer aliquam dui sit amet tellus varius lobortis. Morbi quis lacus tortor. Curabitur quis porttitor quam. Proin ultrices auctor lorem vitae fringilla. Suspendisse cursus sed erat sed molestie. Praesent placerat porttitor nisl, vel euismod lectus hendrerit vulputate. Phasellus suscipit sollicitudin leo, vitae posuere quam faucibus eu. Suspendisse quis sagittis ex.</p>',
            ),
            new ProductValue(
                'Release date',
                'pim_catalog_date',
                '08/18/2011',
            ),
            new ProductValue(
                'Color scanning',
                'pim_catalog_boolean',
                true,
            ),
        ]);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function itFetchesAnEmptyProduct(): void
    {
        $query = static::getContainer()->get(FetchProductQuery::class);
        $result = $query->fetch('catalog_store_us_id', 'empty', 'en_US');

        $expected = new Product('empty', '[empty]', []);

        $this->assertEquals($expected, $result);
    }
}
