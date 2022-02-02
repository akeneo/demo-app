<?php

namespace App\Tests\Unit\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use App\PimApi\Exception\NotSupportedAttributeTypeException;
use App\PimApi\Model\Attribute;
use App\PimApi\Model\Product;
use App\PimApi\Normalizer\AttributeNormalizer;
use App\PimApi\Normalizer\ProductNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductNormalizerTest extends TestCase
{
    private FamilyApiInterface|MockObject $pimFamilyApi;
    private AttributeNormalizer|MockObject $attributeNormalizer;
    private ?ProductNormalizer $productNormalizer;

    protected function setUp(): void
    {
        // mock Attribute Builder
        $this->attributeNormalizer = $this->getMockBuilder(AttributeNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();

        // mock PIM family API
        $this->pimFamilyApi = $this->getMockBuilder(FamilyApiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // mock PIM API Client
        $pimApiClient = $this->getMockBuilder(AkeneoPimClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pimApiClient
            ->method('getFamilyApi')
            ->willReturn($this->pimFamilyApi);

        $this->productNormalizer = new ProductNormalizer(
            $this->attributeNormalizer,
            $pimApiClient,
        );
    }

    protected function tearDown(): void
    {
        $this->productNormalizer = null;
    }

    /**
     * @test
     */
    public function itDenormalizesProductsWithCorrectLabelForLocalizedLabelAttribute(): void
    {
        $rawNameAttributeValues = [
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
        ];
        $rawPimApiProduct = [
            'identifier' => 'product_001',
            'family' => 'family_1',
            'values' => [
                'name' => $rawNameAttributeValues,
            ],
        ];

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedAttribute = new Attribute('name', 'pim_catalog_text', 'Produit 1');

        $this->attributeNormalizer
            ->expects($this->once())
            ->method('denormalizeFromApi')
            ->with('name', $rawNameAttributeValues, 'fr_FR', 'scope1')
            ->willReturn($expectedAttribute);

        $expectedProduct = new Product('product_001', 'Produit 1', [$expectedAttribute]);

        $productResult = $this->productNormalizer->denormalizeFromApi($rawPimApiProduct, 'fr_FR', 'scope1', true);

        $this->assertEqualsCanonicalizing($expectedProduct, $productResult);
    }

    /**
     * @test
     */
    public function itDenormalizesProductsWithCorrectLabelForNotLocalizedLabelAttribute(): void
    {
        $rawNameAttributeValues = [
            [
                'locale' => null,
                'scope' => null,
                'data' => 'Produit 1',
            ],
        ];
        $rawPimApiProduct = [
            'identifier' => 'product_001',
            'family' => 'family_1',
            'values' => [
                'name' => $rawNameAttributeValues,
            ],
        ];

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedAttribute = new Attribute('name', 'pim_catalog_text', 'Produit 1');

        $this->attributeNormalizer
            ->expects($this->once())
            ->method('denormalizeFromApi')
            ->with('name', $rawNameAttributeValues, 'fr_FR', 'scope1')
            ->willReturn($expectedAttribute);

        $expectedProduct = new Product('product_001', 'Produit 1', [$expectedAttribute]);

        $productResult = $this->productNormalizer->denormalizeFromApi($rawPimApiProduct, 'fr_FR', 'scope1', true);

        $this->assertEqualsCanonicalizing($expectedProduct, $productResult);
    }

    /**
     * @test
     */
    public function itDenormalizesProductsWithIdentifierAsLabelForMissingLabelAttribute(): void
    {
        $rawPimApiProduct = [
            'identifier' => 'product_001',
            'family' => 'family_1',
            'values' => [
                'name' => [],
            ],
        ];

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedAttribute = new Attribute('name', 'pim_catalog_text', 'Produit 1');

        $this->attributeNormalizer
            ->expects($this->once())
            ->method('denormalizeFromApi')
            ->with('name', [], 'fr_FR', 'scope1')
            ->willReturn($expectedAttribute);

        $expectedProduct = new Product('product_001', '[product_001]', [$expectedAttribute]);

        $productResult = $this->productNormalizer->denormalizeFromApi($rawPimApiProduct, 'fr_FR', 'scope1', true);

        $this->assertEqualsCanonicalizing($expectedProduct, $productResult);
    }

    /**
     * @test
     */
    public function itIgnoresUnsupportedAttributes(): void
    {
        $rawPimApiProduct = [
            'identifier' => 'product_001',
            'family' => 'family_1',
            'values' => [
                'someUnsupportedAttribute' => [],
                'name' => [],
            ],
        ];

        $familyMock = [
            'code' => 'family_1',
            'attribute_as_label' => 'name',
        ];

        $this->pimFamilyApi
            ->method('get')
            ->with('family_1')
            ->willReturn($familyMock);

        $expectedAttribute = new Attribute('name', 'pim_catalog_text', 'Produit 1');

        $this->attributeNormalizer
            ->expects($this->exactly(2))
            ->method('denormalizeFromApi')
            ->withConsecutive(
                ['someUnsupportedAttribute', [], 'fr_FR', 'scope1'],
                ['name', [], 'fr_FR', 'scope1'],
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new NotSupportedAttributeTypeException('someUnsupportedAttributeType')),
                $expectedAttribute,
            );

        $expectedProduct = new Product('product_001', '[product_001]', [$expectedAttribute]);

        $productResult = $this->productNormalizer->denormalizeFromApi($rawPimApiProduct, 'fr_FR', 'scope1', true);

        $this->assertEqualsCanonicalizing($expectedProduct, $productResult);
    }
}
