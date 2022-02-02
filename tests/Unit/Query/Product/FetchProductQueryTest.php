<?php

namespace App\Tests\Unit\Query\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use App\PimApi\Model\Product;
use App\PimApi\Normalizer\ProductNormalizer;
use App\Query\Product\FetchProductQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchProductQueryTest extends TestCase
{
    private ProductApiInterface|MockObject $pimProductApi;
    private ProductNormalizer|MockObject $productNormalizer;
    private ?FetchProductQuery $fetchProductQuery;

    protected function setUp(): void
    {
        // mock PIM product API
        $this->pimProductApi = $this->getMockBuilder(ProductApiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // mock PIM API Client
        $pimApiClient = $this->getMockBuilder(AkeneoPimClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pimApiClient
            ->method('getProductApi')
            ->willReturn($this->pimProductApi)
        ;

        // mock Product Builder
        $this->productNormalizer = $this->getMockBuilder(ProductNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fetchProductQuery = new FetchProductQuery(
            $pimApiClient,
            $this->productNormalizer,
        );
    }

    protected function tearDown(): void
    {
        $this->fetchProductQuery = null;
    }

    /**
     * @test
     */
    public function itFindsFirstAvailableScopeAndReturnsProductDtoFromRawApiResponse(): void
    {
        $rawApiProduct = [
            'identifier' => 'product_001',
            'family' => 'family_1',
            'values' => [
                'name' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'Produit 1',
                    ],
                    [
                        'locale' => null,
                        'scope' => 'scope1',
                        'data' => 'Produit 1',
                    ],
                    [
                        'locale' => null,
                        'scope' => 'scope2',
                        'data' => 'Produit 1',
                    ],
                ],
            ],
        ];

        $this->pimProductApi
            ->method('get')
            ->with('product_001')
            ->willReturn($rawApiProduct);

        $expectedProduct1 = new Product('product_001', 'Produit 1');

        $this->productNormalizer
            ->expects($this->once())
            ->method('denormalizeFromApi')
            ->with($rawApiProduct, 'fr_FR', 'scope1')
            ->willReturn($expectedProduct1);

        $productResult = $this->fetchProductQuery->fetch('product_001', 'fr_FR');

        $this->assertEqualsCanonicalizing($expectedProduct1, $productResult);
    }
}
