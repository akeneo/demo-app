<?php

namespace App\Tests\Unit\Query\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use App\Dto\Product\Product;
use App\Query\Product\FetchProductsQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchProductsQueryTest extends TestCase
{
    private PageInterface|MockObject $pimProductApiFirstPage;
    private FamilyApiInterface|MockObject $pimFamilyApi;
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

        // mock PIM family API
        $this->pimFamilyApi = $this->getMockBuilder(FamilyApiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // mock PIM API Client
        $pimApiClient = $this->getMockBuilder(AkeneoPimClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pimApiClient
            ->method('getProductApi')
            ->willReturn($pimProductApi);
        $pimApiClient
            ->method('getFamilyApi')
            ->willReturn($this->pimFamilyApi);

        $this->fetchProductsQuery = new FetchProductsQuery(
            $pimApiClient,
        );
    }

    protected function tearDown(): void
    {
        $this->fetchProductsQuery = null;
    }

    /**
     * @test
     */
    public function itReturnsProductsWithCorrectLabelForLocalizedLabelAttribute(): void
    {
        $productsMock = [
            [
                'identifier' => 'product_001',
                'family' => 'family_1',
                'values' => [
                    'name' => [
                        [
                            'locale' => 'en_US',
                            'scope' => null,
                            'data' => 'Product 1',
                        ],
                        [
                            'locale' => 'fr_FR',
                            'scope' => null,
                            'data' => 'Produit 1',
                        ],
                    ],
                ],
            ],
        ];

        $this->pimProductApiFirstPage
            ->method('getItems')
            ->willReturn($productsMock);

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedProduct1 = new Product('product_001', 'Produit 1');

        $productsResult = ($this->fetchProductsQuery)('fr_FR');

        $this->assertEqualsCanonicalizing([$expectedProduct1], $productsResult);
    }

    /**
     * @test
     */
    public function itReturnsProductsWithCorrectLabelForNotLocalizedLabelAttribute(): void
    {
        $productsMock = [
            [
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
            ],
        ];

        $this->pimProductApiFirstPage
            ->method('getItems')
            ->willReturn($productsMock);

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedProduct1 = new Product('product_001', 'Produit 1');

        $productsResult = ($this->fetchProductsQuery)('fr_FR');

        $this->assertEqualsCanonicalizing([$expectedProduct1], $productsResult);
    }

    /**
     * @test
     */
    public function itReturnsProductsWithIdentifierAsLabelForMissingLabelAttribute(): void
    {
        $productsMock = [
            [
                'identifier' => 'product_001',
                'family' => 'family_1',
                'values' => [
                    'name' => [],
                ],
            ],
        ];

        $this->pimProductApiFirstPage
            ->method('getItems')
            ->willReturn($productsMock);

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedProduct1 = new Product('product_001', '[product_001]');

        $productsResult = ($this->fetchProductsQuery)('fr_FR');

        $this->assertEqualsCanonicalizing([$expectedProduct1], $productsResult);
    }

    /**
     * @test
     */
    public function itReturnsOnlyTheRequestedNumberOfProducts(): void
    {
        $productsMock = [
            [
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
            ],
            [
                'identifier' => 'product_002',
                'family' => 'family_1',
                'values' => [
                    'name' => [
                        [
                            'locale' => null,
                            'scope' => null,
                            'data' => 'Produit 2',
                        ],
                    ],
                ],
            ],
        ];

        $this->pimProductApiFirstPage
            ->method('getItems')
            ->willReturn($productsMock);

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedProduct1 = new Product('product_001', 'Produit 1');

        $productsResult = ($this->fetchProductsQuery)('fr_FR', 1);

        $this->assertEqualsCanonicalizing([$expectedProduct1], $productsResult);
    }
}
