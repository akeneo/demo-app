<?php

namespace App\Tests\Unit\Query\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use App\PimApi\Model\Product;
use App\PimApi\Normalizer\ProductNormalizer;
use App\Query\Product\FetchProductsQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchProductsQueryTest extends TestCase
{
    private PageInterface|MockObject $pimProductApiFirstPage;
    private ProductNormalizer|MockObject $productNormalizer;
    private ?FetchProductsQuery $fetchProductsQuery;

    protected function setUp(): void
    {
        // mock PIM product API
        $this->pimProductApiFirstPage = $this->getMockBuilder(PageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pimProductApi = $this->getMockBuilder(ProductApiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pimProductApi
            ->method('listPerPage')
            ->willReturn($this->pimProductApiFirstPage);

        // mock PIM API Client
        $pimApiClient = $this->getMockBuilder(AkeneoPimClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pimApiClient
            ->method('getProductApi')
            ->willReturn($pimProductApi);

        // mock Product Builder
        $this->productNormalizer = $this->getMockBuilder(ProductNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fetchProductsQuery = new FetchProductsQuery(
            $pimApiClient,
            $this->productNormalizer,
        );
    }

    protected function tearDown(): void
    {
        $this->fetchProductsQuery = null;
    }

    /**
     * @test
     */
    public function itFindsFirstAvailableScope(): void
    {
        $rawApiProduct1 = [
            'identifier' => 'product_001',
            'family' => 'family_1',
            'values' => [
                'name' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'Produit 1',
                    ],
                ],
            ],
        ];

        $rawApiProduct2 = [
            'identifier' => 'product_002',
            'family' => 'family_1',
            'values' => [
                'name' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'Produit 2',
                    ],
                    [
                        'locale' => null,
                        'scope' => 'scope1',
                        'data' => 'Produit 2',
                    ],
                    [
                        'locale' => null,
                        'scope' => 'scope2',
                        'data' => 'Produit 2',
                    ],
                ],
            ],
        ];

        $productsMock = [
            $rawApiProduct1,
            $rawApiProduct2,
        ];

        $this->pimProductApiFirstPage
            ->method('getItems')
            ->willReturn($productsMock);

        $this->productNormalizer
            ->expects($this->exactly(2))
            ->method('denormalizeFromApi')
            ->with($this->anything(), 'fr_FR', 'scope1');

        $this->fetchProductsQuery->fetch('fr_FR');
    }

    /**
     * @test
     */
    public function itReturnsProductsDtoFromRawApiResponse(): void
    {
        $rawApiProduct1 = [
            'identifier' => 'product_001',
            'family' => 'family_1',
            'values' => [
                'name' => [
                    [
                        'locale' => null,
                        'scope' => 'scope1',
                        'data' => 'Produit 1',
                    ],
                ],
            ],
        ];

        $rawApiProduct2 = [
            'identifier' => 'product_002',
            'family' => 'family_1',
            'values' => [
                'name' => [
                    [
                        'locale' => null,
                        'scope' => 'scope1',
                        'data' => 'Produit 2',
                    ],
                ],
            ],
        ];

        $productsMock = [
            $rawApiProduct1,
            $rawApiProduct2,
        ];

        $this->pimProductApiFirstPage
            ->method('getItems')
            ->willReturn($productsMock);

        $expectedProduct1 = new Product('product_001', 'Produit 1');
        $expectedProduct2 = new Product('product_002', 'Produit 2');

        $this->productNormalizer
            ->method('denormalizeFromApi')
            ->willReturnMap([
                [$rawApiProduct1, 'fr_FR', 'scope1', false, $expectedProduct1],
                [$rawApiProduct2, 'fr_FR', 'scope1', false, $expectedProduct2],
            ]);

        $productsResult = $this->fetchProductsQuery->fetch('fr_FR');

        $this->assertEqualsCanonicalizing([$expectedProduct1, $expectedProduct2], $productsResult);
    }
}
