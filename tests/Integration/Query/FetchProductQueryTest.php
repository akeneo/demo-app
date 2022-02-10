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
}
