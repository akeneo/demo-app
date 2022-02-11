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
        $result = $query->fetch('1111111304', 'en_US');

        $expected = new Product('1111111304', 'Sunglasses', [
            new ProductValue(
                'EAN',
                'pim_catalog_text',
                '1234567890316',
            ),
            new ProductValue(
                'Name',
                'pim_catalog_text',
                'Sunglasses',
            ),
            new ProductValue(
                'Description',
                'pim_catalog_textarea',
                '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer aliquam dui sit amet tellus varius lobortis. Morbi quis lacus tortor. Curabitur quis porttitor quam. Proin ultrices auctor lorem vitae fringilla. Suspendisse cursus sed erat sed molestie. Praesent placerat porttitor nisl, vel euismod lectus hendrerit vulputate. Phasellus suscipit sollicitudin leo, vitae posuere quam faucibus eu. Suspendisse quis sagittis ex.</p>',
            ),
        ]);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function itFetchesAProductWithPriceCollectionAndBooleanAndDateAndSimpleSelect(): void
    {
        $query = static::getContainer()->get(FetchProductQuery::class);
        $result = $query->fetch('10661721', 'en_US');

        $expected = new Product('10661721', 'Kodak i2600 for Govt', [
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
            new ProductValue(
                'tag',
                'pim_catalog_simpleselect',
                'tag2',
            ),
        ]);

        $this->assertEquals($expected, $result);
    }
}
